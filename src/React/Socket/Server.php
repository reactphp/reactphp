<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

/** @event connection */
class Server extends RawServer
{
    public function listen($port, $host = '127.0.0.1')
    {
        if (strpos($host, ':') !== false) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        return parent::listen("tcp://$host:$port");
    }
}
