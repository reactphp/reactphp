<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\SocketClient\ConnectionManager;
use React\SocketClient\SecureConnectionManager;

class Factory
{
    public function create(LoopInterface $loop, Resolver $resolver)
    {
        $connectionManager = new ConnectionManager($loop, $resolver);
        $secureConnectionManager = new SecureConnectionManager($connectionManager, $loop);
        return new Client($loop, $connectionManager, $secureConnectionManager);
    }
}

