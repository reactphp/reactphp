<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

// Events: data, end, error, close
interface ReadableStreamInterface extends StreamInterface
{
    public function isReadable();
    public function pause();
    public function resume();
    public function pipe(WritableStreamInterface $dest, array $options = array());
}
