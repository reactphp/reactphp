<?php

namespace React\Http;

use Evenement\EventEmitter;
use Guzzle\Http\Message\RequestFactory;
use React\Socket\ServerInterface as SocketServerInterface;

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

        $factory = new RequestFactory();
        $parsed = $factory->parseMessage($headers."\r\n\r\n");

        $parsedQuery = array();
        if (0 === strpos($parsed['parts']['query'], '?')) {
            $query = substr($parsed['parts']['query'], 1);
            parse_str($query, $parsedQuery);
        }

        $request = new Request(
            $parsed['method'],
            $parsed['parts']['path'],
            $parsedQuery,
            $parsed['protocol_version'],
            $parsed['headers']
        );

        return array($request, $bodyBuffer);
    }
}
