<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;

interface ConnectionInterface extends EventEmitterInterface
{
    public function __construct($socket, LoopInterface $loop);
    public function write($data);
    public function end();
    public function handleData($socket);
}