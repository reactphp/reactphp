<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;

interface ServerInterface extends EventEmitterInterface
{
    function listen($port, $host = '127.0.0.1');
    function getPort();
    function shutdown();
}
