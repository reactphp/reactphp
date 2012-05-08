<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

class Connection extends EventEmitter implements ConnectionInterface
{
    public $bufferSize = 4096;
    public $socket;
    private $loop;

    public function __construct($socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop = $loop;
    }

    public function write($data)
    {
        $len = strlen($data);

        do {
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
                return false;
            });

            try {
                $sent = fwrite($this->socket, $data);
            } catch (\ErrorException $e) {
                $sent = false;
                $error = $e->getMessage();
            }

            restore_error_handler();

            if (false === $sent) {
                $error = $error ?: 'Unable to write to socket';
                $this->emit('error', array($error, $this));
                return;
            }
            $len -= $sent;
            $data = substr($data, $sent);
        } while ($len > 0);
    }

    public function end()
    {
        $this->emit('end', array($this));
        $this->loop->removeStream($this->socket);
        $this->removeAllListeners();
        fclose($this->socket);
    }

    public function handleData($socket)
    {
        $data = @stream_socket_recvfrom($socket, $this->bufferSize);
        if ('' === $data || false === $data) {
            $this->end();
        } else {
            $this->emit('data', array($data, $this));
        }
    }
}
