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
        $address = AddressFactory::create($url);
        $requestData = new RequestData($method, $address, $headers);
        $connectionManager = $this->getConnectorForScheme($address);
        return new Request($connectionManager, $requestData);

    }

    private function getConnectorForScheme(HttpAddressInterface $address)
    {
        return ($address->isSecure()) ? $this->secureConnector : $this->connector;
    }
}

