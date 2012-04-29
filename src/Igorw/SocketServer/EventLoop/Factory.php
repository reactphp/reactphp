<?php

namespace Igorw\SocketServer\EventLoop;

use Igorw\SocketServer\EventLoop\StreamSelectLoop;
use Igorw\SocketServer\EventLoop\LibEventLoop;

class Factory
{
    public static function create()
    {
        if (function_exists('event_base_new')) {
            return new LibEventLoop();
        }

        return new StreamSelectLoop();
    }
}
