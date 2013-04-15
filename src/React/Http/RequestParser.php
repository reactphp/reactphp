<?php

namespace React\Http;

use React\Stream\WritableStream;
use Guzzle\Parser\Message\MessageParser;

// TODO: validate state transitions

/**
 * @event request
 * @event connection-end
 */
class RequestParser extends WritableStream
{
    const STATE_INIT = 0; // waiting for headers
    const STATE_CONSUMING_HEADERS = 1;
    const STATE_CONSUMING_CHUNKED_BODY = 2;
    const STATE_CONSUMING_RAW_BODY = 3;
    const STATE_REQUEST_END = 4;
    const STATE_CONNECTION_END = 5;

    private $state = self::STATE_INIT;
    private $buffer = '';

    private $request;
    private $keepAlive;
    private $chunkedEncoding;
    private $contentLength;
    private $contentRemaining;

    public function write($data)
    {
        $this->buffer .= $data;

        $this->poll();
    }

    private function poll()
    {
        if ($this->isState(static::STATE_INIT)) {
            if ($this->hasHeaders()) {
                $this->consumeHeaders();
            }
        }

        if ($this->isState(static::STATE_CONSUMING_CHUNKED_BODY)) {
            $this->consumeChunkedBody();
        }

        if ($this->isState(static::STATE_CONSUMING_RAW_BODY)) {
            $this->consumeRawBody();
        }

        if ($this->isState(static::STATE_REQUEST_END)) {
            $this->checkForConnectionEnd();
        }
    }

    private function hasHeaders()
    {
        return false !== strpos($this->buffer, "\r\n\r\n");
    }

    private function consumeHeaders()
    {
        $this->setState(static::STATE_CONSUMING_HEADERS);

        list($headerData, $rest) = explode("\r\n\r\n", $this->buffer, 2);
        $this->buffer = $rest;

        $this->request = $this->createRequest($headerData);
        $headers = $this->request->getHeaders();

        $this->keepAlive = !(isset($headers['Connection']) && 'close' === $headers['Connection']);
        $this->chunkedEncoding = isset($headers['Transfer-Encoding']) && 'chunked' === $headers['Transfer-encoding'];
        $this->contentLength = isset($headers['Content-Length']) ? $headers['Content-Length'] : 0;
        $this->remainingLength = $this->contentLength;

        $this->emit('request', [$this->request]);

        $newState = $this->chunkedEncoding
            ? static::STATE_CONSUMING_CHUNKED_BODY
            : static::STATE_CONSUMING_RAW_BODY;
        $this->setState($newState);
    }

    private function consumeChunkedBody()
    {
        if (0 === strlen($this->buffer)) {
            return;
        }

        if (false === strpos($this->buffer, "\r\n")) {
            return;
        }

        list($hexLength, $rest) = explode("\r\n", $this->buffer);
        $length = hexdec($hexLength);

        // extra 4 for crlfcrlf
        if (0 === $length && strlen($rest) < 4) {
            return;
        }

        if (0 === $length) {
            $this->request->close();
            $this->setState(static::STATE_REQUEST_END);
            return;
        }

        // extra 2 for crlf
        if (strlen($rest) < ($length + 2)) {
            return;
        }

        // extra 2 for crlf
        $chunk = substr($rest, 0, $length);
        $this->buffer = (string) substr($rest, $length + 2);

        $this->request->emit('data', [$chunk]);

        // TODO: prevent stackoverflow by not using recursion
        $this->consumeChunkedBody();
    }

    private function consumeRawBody()
    {
        if (0 === strlen($this->buffer)) {
            return;
        }

        if (0 === $this->contentRemaining) {
            $this->request->close();
            $this->setState(static::STATE_REQUEST_END);
            return;
        }

        $chunk = substr($this->buffer, 0, $this->contentRemaining);
        $this->contentRemaining -= strlen($chunk);
        $this->buffer = (string) substr($this->buffer, strlen($chunk));

        $this->request->emit('data', [$chunk]);

        // TODO: prevent stackoverflow by not using recursion
        $this->consumeRawBody();
    }

    private function checkForConnectionEnd()
    {
        if ($this->keepAlive) {
            $this->reset();
            $this->setState(static::STATE_INIT);
            $this->poll();
            return;
        }

        $this->setState(static::STATE_CONNECTION_END);
        $this->emit('connection-end');
        $this->removeAllListeners();
    }

    private function createRequest($headerData)
    {
        $parser = new MessageParser();
        $parsed = $parser->parseRequest("$headerData\r\n\r\n");

        $parsedQuery = array();
        if ($parsed['request_url']['query']) {
            parse_str($parsed['request_url']['query'], $parsedQuery);
        }

        return new Request(
            $parsed['method'],
            $parsed['request_url']['path'],
            $parsedQuery,
            $parsed['version'],
            $parsed['headers']
        );
    }

    private function reset()
    {
        $this->request = null;
        $this->keepAlive = null;
        $this->chunkedEncoding = null;
        $this->contentLength = null;
        $this->contentRemaining = null;
    }

    private function isState($state)
    {
        return $state === $this->state;
    }

    private function setState($state)
    {
        $this->state = $state;
    }
}
