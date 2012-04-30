<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;

interface ServerInterface extends EventEmitterInterface
{
    public function __construct($host, $port, LoopInterface $loop);
    public function getClient($socket);
    public function getClients();
    public function write($data);
    public function close($socket);
    public function getPort();
    public function shutdown();
}
