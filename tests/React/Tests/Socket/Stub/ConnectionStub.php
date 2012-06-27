<?php

namespace React\Tests\Socket\Stub;

use Evenement\EventEmitter;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStream;
use React\Stream\Util;

class ConnectionStub extends EventEmitter implements ConnectionInterface
{
    private $data = '';

    public function pause()
    {
    }

    public function resume()
    {
    }

    public function pipe(WritableStream $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $this;
    }

    public function write($data)
    {
        $this->data .= $data;

        return true;
    }

    public function end($data = null)
    {
    }

    public function close()
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
