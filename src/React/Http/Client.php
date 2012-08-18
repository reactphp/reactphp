<?php

namespace React\Http;

use React\EventLoop\LoopInterface;
use React\Http\Client\ConnectionManager;
use React\Http\Client\SecureConnectionManager;
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

        if ('https' === $guzzleRequest->getScheme()) {
            $connectionManager = $this->getSecureConnectionManager();
        } else {
            $connectionManager = $this->getConnectionManager();
        }

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

}

