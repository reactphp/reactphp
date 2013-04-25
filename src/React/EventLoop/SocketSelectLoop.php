<?php

namespace React\EventLoop;

class SocketSelectLoop extends AbstractSelectLoop
{

    protected function select(&$read, &$write, &$except, $utime)
    {
        return socket_select($read, $write, $except, 0, $utime);
    }
}
