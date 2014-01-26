<?php

namespace React\EventLoop;

class Factory
{
    public static function create()
    {
        // @codeCoverageIgnoreStart
        if (function_exists('event_base_new')) {
            return new LibEventLoop();
        } else if (class_exists('libev\EventLoop')) {
            return new LibEvLoop;
        } else if (class_exists('EventBase')) {
            return new ExtEventLoop;
        }

        return new StreamSelectLoop();
        // @codeCoverageIgnoreEnd
    }
}
