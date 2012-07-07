<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

// Events: data, end, error, close
interface ReadableStreamInterface extends EventEmitterInterface
{
    public function isReadable();
    public function pause();
    public function resume();
    public function close();
    public function pipe(WritableStreamInterface $dest, array $options = array());
}
