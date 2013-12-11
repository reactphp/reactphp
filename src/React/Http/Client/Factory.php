<?php

namespace React\Http\Client;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\Socket\Client\Connector;
use React\Socket\Client\SecureConnector;

class Factory
{
    public function create(LoopInterface $loop, Resolver $resolver)
    {
        $connector = new Connector($loop, $resolver);
        $secureConnector = new SecureConnector($connector, $loop);
        return new Client($connector, $secureConnector);
    }
}

