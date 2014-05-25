<?php

namespace React\Tests\Http;

use Evenement\EventEmitter;
use React\Socket\ServerInterface;

class ServerStub extends EventEmitter implements ServerInterface
{
    public function listen($port, $host = '127.0.0.1')
    {
    }

    public function getPort()
    {
        return 80;
    }

    public function shutdown()
    {
    }
}
