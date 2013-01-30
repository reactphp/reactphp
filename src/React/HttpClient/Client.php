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

    private function getConnectorForScheme($scheme)
    {
        return ('https' === $scheme) ? $this->secureConnector : $this->connector;
    }
}

