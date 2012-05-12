<?php

namespace React\Tests\Socket\Stub;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class ConnectionStub extends EventEmitter implements ConnectionInterface
{
    private $data = '';

    public function write($data)
    {
        $this->data .= $data;
    }

    public function end()
    {
    }

    public function handleData($socket)
    {
    }

    public function getData()
    {
        return $this->data;
    }

    public function getRemoteAddress()
    {
        return '127.0.0.1';
    }
}
