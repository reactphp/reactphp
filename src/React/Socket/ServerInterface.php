<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;

/** @event connection */
interface ServerInterface extends EventEmitterInterface
{
    public function listen($socket);
    public function getPort();
    public function shutdown();
}
