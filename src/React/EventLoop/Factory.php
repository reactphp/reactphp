<?php

namespace React\EventLoop;

use React\EventLoop\StreamSelectLoop;
use React\EventLoop\LibEventLoop;

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
