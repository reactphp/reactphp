<?php

namespace React\Tests\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class ConnectionMock extends EventEmitter implements ConnectionInterface
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
}
