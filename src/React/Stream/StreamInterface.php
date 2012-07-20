<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

// This class exists because ReadableStreamInterface and WritableStreamInterface
//  both need close methods.
// In PHP <= 5.3.8 a class can not implement 2 interfaces with coincidental matching methods
interface StreamInterface extends EventEmitterInterface
{
    public function close();
}
