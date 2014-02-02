<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\SocketClient\Connector;
use React\SocketClient\SecureConnector;

class Factory
{
    public function create(LoopInterface $loop, Resolver $resolver)
    {
        $connector = new Connector($loop, $resolver);
        $secureConnector = new SecureConnector($connector, $loop);
        return new Client($connector, $secureConnector);
    }
}

