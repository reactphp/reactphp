<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

interface ConnectionInterface extends ReadableStreamInterface, WritableStreamInterface
{
    public function getRemoteAddress();
}
