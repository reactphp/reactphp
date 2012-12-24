<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

/**
 * @event drain
 * @event error
 * @event close
 * @event pipe
 */
interface WritableStreamInterface extends StreamInterface
{
    public function isWritable();
    public function write($data);
    public function end($data = null);
}
