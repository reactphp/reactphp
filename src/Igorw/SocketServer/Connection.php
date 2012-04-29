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
        $len = strlen($data);

        do {
            $sent = @fwrite($this->socket, $data);
            if (false === $sent) {
                $this->emit('error', array('Unable to write to socket', $this));
                return;
            }
            $len -= $sent;
            $data = substr($data, $sent);
        } while ($len > 0);
    }

    public function close()
    {
        $this->server->close($this->socket);
    }
}
