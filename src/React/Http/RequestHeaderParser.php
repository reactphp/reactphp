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
            $this->emit('error', array("Maximum header size of {$this->maxSize} exceeded."));
            return;
        }

        $this->buffer .= $data;

        if (false !== strpos($this->buffer, "\r\n\r\n")) {
            list($request, $bodyBuffer) = $this->parseRequest($this->buffer);

            $this->emit('headers', array($request, $bodyBuffer));
        }
    }

    public function parseRequest($data)
    {
        list($headers, $bodyBuffer) = explode("\r\n\r\n", $data, 2);

        $factory = new RequestFactory();
        $guzzleRequest = $factory->fromMessage($headers."\r\n\r\n");

        $request = new Request(
            $guzzleRequest->getMethod(),
            $guzzleRequest->getPath(),
            $guzzleRequest->getQuery()->getAll(),
            $guzzleRequest->getProtocolVersion(),
            $guzzleRequest->getHeaders()->getAll()
        );

        return array($request, $bodyBuffer);
    }
}
