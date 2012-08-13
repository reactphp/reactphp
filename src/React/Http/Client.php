<?php

namespace React\Http;

use React\EventLoop\LoopInterface;
use React\Http\Client\ConnectionManager;
use Guzzle\Http\Message\Request as GuzzleRequest;
use React\Http\Client\Request as ClientRequest;

class Client
{
    private $loop;

    private $connectionManager;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function request($method, $url, array $headers = array())
    {
        $guzzleRequest = new GuzzleRequest($method, $url, $headers);
        $connectionManager = $this->getConnectionManager();
        return new ClientRequest($this->loop, $connectionManager, $guzzleRequest);
    }

    public function setConnectionManager(ConnectionManagerInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function getConnectionManager()
    {
        if (null === $connectionManager = $this->connectionManager) {
            return $this->connectionManager = new ConnectionManager($this->loop);
        }
        return $connectionManager;
    }
}

