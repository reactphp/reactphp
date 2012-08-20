<?php

namespace React\Http;

use Guzzle\Http\Message\Request as GuzzleRequest;
use React\EventLoop\LoopInterface;
use React\Http\Client\ConnectionManager;
use React\Http\Client\Request as ClientRequest;
use React\Http\Client\SecureConnectionManager;

class Client
{
    private $loop;

    private $connectionManager;

    private $secureConnectionManager;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
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
        if (null === $this->connectionManager) {
            $this->connectionManager = new ConnectionManager($this->loop);
        }
        return $this->connectionManager;
    }

    public function setSecureConnectionManager(ConnectionManagerInterface $connectionManager)
    {
        $this->secureConnectionManager = $connectionManager;
    }

    public function getSecureConnectionManager()
    {
        if (null === $this->secureConnectionManager) {
            $this->secureConnectionManager = new SecureConnectionManager($this->loop);
        }
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

