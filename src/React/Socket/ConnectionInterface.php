<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;

interface ConnectionInterface extends EventEmitterInterface
{
    function write($data);
    function end();
    function handleData($socket);
}
