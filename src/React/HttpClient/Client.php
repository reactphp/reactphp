<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\HttpClient\Request;
use React\SocketClient\ConnectorInterface;

class Client
{
    private $connectionManager;
    private $secureConnectionManager;

    public function __construct(ConnectorInterface $connector, ConnectorInterface $secureConnector)
    {
        $this->connector = $connector;
        $this->secureConnector = $secureConnector;
    }

    public function request($method, $url, array $headers = array())
    {
        $requestData = new RequestData($method, $url, $headers);
        $connectionManager = $this->getConnectorForScheme($requestData->getScheme());
        return new Request($connectionManager, $requestData);

    }

    private function getConnectorForScheme($scheme)
    {
        return ('https' === $scheme) ? $this->secureConnector : $this->connector;
    }
}

