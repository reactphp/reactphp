<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

class Buffer extends EventEmitter
{
    public $socket;
    public $closed = false;
    public $listening = false;
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
            $that = $this;
            $loop = $this->loop;
            $listener = function ($socket) use ($that, $loop) {
                $loop->removeWriteStream($that->socket);
                $that->listening = false;

                $that->handleWrite();
            };
            $this->loop->addWriteStream($this->socket, $listener);

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
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            return false;
        });

        $error = null;
        try {
            $sent = fwrite($this->socket, $this->data);
        } catch (\ErrorException $e) {
            $sent = false;
            $error = $e->getMessage();
        }

        restore_error_handler();

        if (false === $sent) {
            $error = $error ?: 'Unable to write to socket';
            $this->emit('error', array($error));
            return;
        }

        $this->data = substr($this->data, $sent);

        if (0 === strlen($this->data)) {
            $this->emit('end');
        }
    }
}
