<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Stream\ReadableStream;
use React\Stream\WritableStream;
use React\Stream\Util;

class Request extends EventEmitter implements ReadableStream
{
    private $method;
    private $path;
    private $query;
    private $httpVersion;
    private $headers;

    public function __construct($method, $path, $query = array(), $httpVersion = '1.1', $headers = array())
    {
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->httpVersion = $httpVersion;
        $this->headers = $headers;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function pause()
    {
        $this->emit('pause');
    }

    public function resume()
    {
        $this->emit('resume');
    }

    public function close()
    {
        $this->emit('end');
        $this->removeAllListeners();
    }

    public function pipe(WritableStream $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $this;
    }
}
