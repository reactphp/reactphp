<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;

/** @event connection */
interface ServerInterface extends EventEmitterInterface
{
    public function listen($port, $host = '127.0.0.1');
    public function getPort();
    public function shutdown();
}
