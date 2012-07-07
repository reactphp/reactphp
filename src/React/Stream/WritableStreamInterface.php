<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

// Events: drain, error, close, pipe
interface WritableStreamInterface extends EventEmitterInterface
{
    public function isWritable();
    public function write($data);
    public function end($data = null);
    public function close();
}
