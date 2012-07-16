<?php

namespace React\Http;

use Evenement\EventEmitter;
use Guzzle\Parser\Message\MessageParser;

class RequestHeaderParser extends EventEmitter
{
    private $buffer = '';
    private $maxSize = 4096;

    public function feed($data)
    {
        if (strlen($this->buffer) + strlen($data) > $this->maxSize) {
            $this->emit('error', array(new \OverflowException("Maximum header size of {$this->maxSize} exceeded."), $this));

            return;
        }

        $this->buffer .= $data;

        if (false !== strpos($this->buffer, "\r\n\r\n")) {
            list($request, $bodyBuffer) = $this->parseRequest($this->buffer);

            $this->emit('headers', array($request, $bodyBuffer));
            $this->removeAllListeners();
        }
    }

    public function parseRequest($data)
    {
        list($headers, $bodyBuffer) = explode("\r\n\r\n", $data, 2);

        $parser = new MessageParser();
        $parsed = $parser->parseRequest($headers."\r\n\r\n");

        $parsedQuery = array();
        if ($parsed['request_url']['query']) {
            parse_str($parsed['request_url']['query'], $parsedQuery);
        }

        $request = new Request(
            $parsed['method'],
            $parsed['request_url']['path'],
            $parsedQuery,
            $parsed['version'],
            $parsed['headers']
        );

        return array($request, $bodyBuffer);
    }
}
