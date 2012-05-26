<?php

namespace React\Http;

use Evenement\EventEmitter;
use Guzzle\Http\Message\RequestFactory;

class RequestHeaderParser extends EventEmitter
{
    private $buffer = '';
    private $maxSize = 4096;

    public function feed($data)
    {
        if (mb_strlen($this->buffer, '8bit') + mb_strlen($data, '8bit') > $this->maxSize) {
            $this->emit('error', array(new \OverflowException("Maximum header size of {$this->maxSize} exceeded."), $this));

            return;
        }

        $this->buffer .= $data;

        if (false !== mb_strpos($this->buffer, "\r\n\r\n", 0, 'ASCII')) {
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
        if (0 === mb_strpos($parsed['parts']['query'], '?', 0, 'ASCII')) {
            $query = mb_substr($parsed['parts']['query'], 1, mb_strlen($parsed['parts']['query'], 'ASCII'), 'ASCII');
            mb_parse_str($query, $parsedQuery);
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
