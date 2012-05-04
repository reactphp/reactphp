<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;

interface ServerInterface extends EventEmitterInterface
{
    public function listen($port, $host = '127.0.0.1');
    public function getClient($socket);
    public function getClients();
    public function write($data);
    public function close($socket);
    public function getPort();
    public function shutdown();
}
