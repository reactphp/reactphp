<?php

namespace React\HttpClient;

use Guzzle\Http\Message\Request as GuzzleRequest;
use React\EventLoop\LoopInterface;
use React\HttpClient\ConnectionManager;
use React\HttpClient\Request as ClientRequest;
use React\HttpClient\SecureConnectionManager;

class Client
{
    private $loop;

    private $connectionManager;

    private $secureConnectionManager;

    public function __construct(LoopInterface $loop, ConnectionManagerInterface $connectionManager, ConnectionManagerInterface $secureConnectionManager)
    {
        $this->loop = $loop;
        $this->connectionManager = $connectionManager;
        $this->secureConnectionManager = $secureConnectionManager;
    }

    public function request($method, $url, array $headers = array())
    {
        $guzzleRequest = new GuzzleRequest($method, $url, $headers);
        $connectionManager = $this->getConnectionManagerForScheme($guzzleRequest->getScheme());
        return new ClientRequest($this->loop, $connectionManager, $guzzleRequest);
    }

    public function setConnectionManager(ConnectionManagerInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function getConnectionManager()
    {
        return $this->connectionManager;
    }

    public function setSecureConnectionManager(ConnectionManagerInterface $connectionManager)
    {
        $this->secureConnectionManager = $connectionManager;
    }

    public function getSecureConnectionManager()
    {
        return $this->secureConnectionManager;
    }

    private function getConnectionManagerForScheme($scheme)
    {
        if ('https' === $scheme) {
            return $this->getSecureConnectionManager();
        } else {
            return $this->getConnectionManager();
        }
    }
}

