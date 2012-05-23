<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;

interface ConnectionInterface extends EventEmitterInterface
{
    public function write($data);
    public function end();
    public function handleData($socket);
    public function getRemoteAddress();
}
