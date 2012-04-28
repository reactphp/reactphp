<?php

namespace Igorw\SocketServer;

use Evenement\EventEmitter;

class Connection extends EventEmitter
{
    private $socket;
    private $server;

    public function __construct($socket, $server)
    {
        $this->socket = $socket;
        $this->server = $server;
    }

    public function isOpen()
    {
        return is_resource($this->socket);
    }

    public function write($data)
    {
        fwrite($this->socket, $data);
    }

    public function close()
    {
        $this->server->close($this->socket);
    }
}
