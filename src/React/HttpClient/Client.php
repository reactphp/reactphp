<?php

namespace React\HttpClient;

use Guzzle\Http\Message\Request as GuzzleRequest;
use React\EventLoop\LoopInterface;
use React\HttpClient\Request as ClientRequest;
use React\SocketClient\ConnectorInterface;

class Client
{
    private $loop;

    private $connector;

    private $secureConnector;

    public function __construct(LoopInterface $loop, ConnectorInterface $connector, ConnectorInterface $secureConnector)
    {
        $this->loop = $loop;
        $this->connector = $connector;
        $this->secureConnector = $secureConnector;
    }

    public function request($method, $url, array $headers = array())
    {
        $guzzleRequest = new GuzzleRequest($method, $url, $headers);
        $connector = $this->getConnectorForScheme($guzzleRequest->getScheme());
        return new ClientRequest($this->loop, $connector, $guzzleRequest);
    }

    public function setConnector(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    public function getConnector()
    {
        return $this->connector;
    }

    public function setSecureConnector(ConnectorInterface $connector)
    {
        $this->secureConnector = $connector;
    }

    public function getSecureConnector()
    {
        return $this->secureConnector;
    }

    private function getConnectorForScheme($scheme)
    {
        if ('https' === $scheme) {
            return $this->getSecureConnector();
        } else {
            return $this->getConnector();
        }
    }
}

