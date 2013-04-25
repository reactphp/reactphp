<?php

namespace React\EventLoop;

class StreamSelectLoop extends AbstractSelectLoop
{

    protected function select(&$read, &$write, &$except, $utime)
    {
        return stream_select($read, $write, $except, 0, $utime);
    }
}
