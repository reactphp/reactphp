<?php

namespace Igorw\EventLoop;

use Igorw\EventLoop\StreamSelectLoop;
use Igorw\EventLoop\LibEventLoop;

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
