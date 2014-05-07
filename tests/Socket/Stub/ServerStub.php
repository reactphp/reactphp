<?php

namespace React\Tests\Socket\Stub;

use Evenement\EventEmitter;
use React\Socket\ServerInterface;

class ServerStub extends EventEmitter implements ServerInterface
{
    public function listen($address)
    {
    }

    public function getAddress()
    {
        return 'tcp://127.0.0.1:80';
    }

    public function shutdown()
    {
    }
}
