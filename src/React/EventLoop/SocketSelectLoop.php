<?php

namespace React\EventLoop;

use InvalidArgumentException;

class SocketSelectLoop extends AbstractSelectLoop
{
    
    public function addReadStream($stream, $listener)
    {
        if (get_resource_type($stream) !== 'socket') {
            throw new InvalidArgumentException('Socket loop only accepts resources of type "socket"');
        }
        return parent::addReadStream($stream, $listener);
    }
    
    public function addWriteStream($stream, $listener)
    {
        if (get_resource_type($stream) !== 'socket') {
            throw new InvalidArgumentException('Socket loop only accepts resources of type "socket"');
        }
        return parent::addWriteStream($stream, $listener);
    }

    protected function select(&$read, &$write, &$except, $utime)
    {
        return socket_select($read, $write, $except, 0, $utime);
    }
}
