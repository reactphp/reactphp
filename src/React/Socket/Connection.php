<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

class Connection extends EventEmitter
{
    public $bufferSize = 4096;

    private $socket;
    private $loop;

    public function __construct($socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop = $loop;
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
        $this->emit('end');
        $this->loop->removeStream($this->socket);
        fclose($this->socket);
    }

    public function handleData($socket)
    {
        $data = @stream_socket_recvfrom($socket, $this->bufferSize);
        if ('' === $data || false === $data) {
            $this->close();
        } else {
            $this->emit('data', array($data));
        }
    }
}
