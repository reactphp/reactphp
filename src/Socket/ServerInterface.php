<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;

/** @event connection */
interface ServerInterface extends EventEmitterInterface
{
    public function listen($address);
    public function getAddress();
    public function shutdown();
}
