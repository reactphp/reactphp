<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

// Events: drain, error, close, pipe
interface WritableStream extends EventEmitterInterface
{
    public function write($data);
    public function end($data = null);
    public function close();
}
