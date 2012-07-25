<?php

namespace React\Http\Client;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\Stream;

class Response extends EventEmitter
{
    private $loop;

    private $stream;

    private $protocol;

    private $version;

    private $code;

    private $reasonPhrase;

    private $headers;

    private $body;

    public function __construct(LoopInterface $loop, Stream $stream, $protocol, $version, $code, $reasonPhrase, $headers, $body)
    {
        $this->loop = $loop;
        $this->stream = $stream;
        $this->protocol = $protocol;
        $this->version = $version;
        $this->code = $code;
        $this->reasonPhrase = $reasonPhrase;
        $this->headers = $headers;
        $this->body = $body;

        $stream->on('data', array($this, 'handleData'));
    }
    
    public function getProtocol()
    {
        return $this->protocol;
    }
    
    public function getVersion()
    {
        return $this->version;
    }
    
    public function getCode()
    {
        return $this->code;
    }
    
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }
    
    public function getBody()
    {
        return $this->body;
    }

    public function handleData($data)
    {
        $this->data .= $data;
        $this->emit('data', array($data));
    }
}

