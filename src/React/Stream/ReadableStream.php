<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

// Events: data, end, error, close
interface ReadableStream extends EventEmitterInterface
{
    public function isReadable();
    public function pause();
    public function resume();
    public function close();
    public function pipe(WritableStream $dest, array $options = array());
}
