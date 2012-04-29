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
        if (false === @fwrite($this->socket, $data)) {
            $this->emit('error', array('Unable to write to socket', $this));
        }
    }

    public function close()
    {
        $this->server->close($this->socket);
    }
}
