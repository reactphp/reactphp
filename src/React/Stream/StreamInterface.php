<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;

interface StreamInterface extends EventEmitterInterface
{
    public function close();
}
