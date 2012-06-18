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
    private $lastError = array(
        'number'  => ''
      , 'message' => ''
      , 'file'    => ''
      , 'line'    => ''
    );

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
        set_error_handler(array($this, 'errorHandler'));

        $sent = fwrite($this->socket, $this->data);

        restore_error_handler();

        if (false === $sent) {
            $this->emit('error', array(new \ErrorException(
                $this->lastError['message']
              , 0
              , $this->lastError['number']
              , $this->lastError['file']
              , $this->lastError['line']
            )));

            return;
        }

        $this->data = substr($this->data, $sent);

        if (0 === strlen($this->data)) {
            $this->loop->removeWriteStream($this->socket);
            $this->listening = false;

            $this->emit('end');
        }
    }

    private function errorHandler($errno, $errstr, $errfile, $errline) {
        $this->lastError['number']  = $errno;
        $this->lastError['message'] = $errstr;
        $this->lastError['file']    = $errfile;
        $this->lastError['line']    = $errline;
    }
}
