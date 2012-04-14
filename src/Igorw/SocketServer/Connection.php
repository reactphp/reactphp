<?php

namespace Igorw\SocketServer;

class Connection
{
    private $socket;
    private $server;

    public function __construct($socket, $server)
    {
        $this->socket = $socket;
        $this->server = $server;
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
