<?php

namespace React\Http;

use React\Socket\ConnectionInterface;
use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;

class Request extends EventEmitter implements ReadableStreamInterface
{
    private $conn;
    private $closed = false;
    private $readable = true;
    private $method;
    private $path;
    private $query;
    private $httpVersion;
    private $headers;
    private $normalizedHeaders = array();
    private $connListeners;

    public function __construct(
        ConnectionInterface $conn,
        $method,
        $path,
        $query = array(),
        $httpVersion = '1.1',
        $headers = array()
    ) {
        $this->conn = $conn;
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->httpVersion = $httpVersion;
        $this->headers = $headers;
        
        foreach ($headers as $name => &$value) {
            $this->normalizedHeaders[strtolower($name)] = &$value;
        }
        
        $this->listenToConn();
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
    
    public function getHeader($name)
    {
        $name = strtolower($name);
        
        if (!array_key_exists($name, $this->normalizedHeaders)) {
            return null;
        }
        
        return $this->normalizedHeaders[$name];
    }

    public function expectsContinue()
    {
        return ('100-continue' === $this->getHeader('expect'));
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function pause()
    {
        $this->conn->pause();
        $this->emit('pause');
    }

    public function resume()
    {
        $this->conn->resume();
        $this->emit('resume');
    }

    public function close()
    {
        if ($this->closed) {
            $this->removeAllListeners();
            return;
        }
        
        $this->closed = true;
        
        $this->readable = false;
        $this->emit('end');
        $this->stopListeningToConn();
        $this->removeAllListeners();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }
    
    private function listenToConn()
    {
        $request = $this;
        
        $this->connListeners = array(
            'end'   => array($this, 'close'),
            'data'  => function ($data) use ($request) {
                $request->emit('data', array($data));
            },
        );
        
        foreach ($this->connListeners as $event => $listener) {
            $this->conn->on($event, $listener);
        }
    }
    
    private function stopListeningToConn()
    {
        foreach ($this->connListeners as $event => $listener) {
            $this->conn->removeListener($event, $listener);
        }
        
        $this->connListeners = array();
    }
}
