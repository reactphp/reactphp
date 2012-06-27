<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;
use React\Stream\ReadableStream;
use React\Stream\WritableStream;

interface ConnectionInterface extends ReadableStream, WritableStream
{
    public function getRemoteAddress();
}
