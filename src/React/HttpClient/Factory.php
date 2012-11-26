<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;

class Factory
{
    public function create(LoopInterface $loop, Resolver $resolver)
    {
        $connectionManager = new ConnectionManager($loop, $resolver);
        $secureConnectionManager = new SecureConnectionManager($loop, $resolver);
        return new Client($loop, $connectionManager, $secureConnectionManager);
    }
}

