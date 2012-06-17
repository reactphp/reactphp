<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

class Buffer extends EventEmitter
{
    public $socket;
    public $closed = false;
    public $listening = false;
    public $chunkSize = 4096;
    private $loop;
    private $data = '';

    public function __construct($socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop = $loop;
    }

    public function write($data)
    {
        if ($this->closed) {
            return;
        }

        $this->data .= $data;

        if (!$this->listening) {
            $this->loop->addWriteStream($this->socket, array($this, 'handleWrite'));

            $this->listening = true;
        }
    }

    public function end()
    {
        $this->closed = true;

        if (!$this->listening) {
            $this->emit('end');
        }
    }

    public function handleWrite()
    {
        $sent = @fwrite($this->socket, $this->data, $this->chunkSize);

        if (false === $sent) {
            $this->emit('error', array(new \RuntimeException('Unable to write to socket')));

            return;
        }

        $this->data = substr($this->data, $sent);

        if (0 === strlen($this->data)) {
            $this->loop->removeWriteStream($this->socket);
            $this->listening = false;

            $this->emit('end');
        }
    }
}
