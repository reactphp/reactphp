<?php

namespace React\Stream;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\WritableStream;

class Buffer extends EventEmitter implements WritableStream
{
    public $stream;
    public $closed = false;
    public $listening = false;
    public $softLimit = 2048;
    private $loop;
    private $data = '';
    private $lastError = array(
        'number'  => '',
        'message' => '',
        'file'    => '',
        'line'    => '',
    );

    public function __construct($stream, LoopInterface $loop)
    {
        $this->stream = $stream;
        $this->loop = $loop;
    }

    public function write($data)
    {
        if ($this->closed) {
            return;
        }

        $this->data .= $data;

        if (!$this->listening) {
            $this->loop->addWriteStream($this->stream, array($this, 'handleWrite'));

            $this->listening = true;
        }

        $belowSoftLimit = strlen($this->data) < $this->softLimit;

        return $belowSoftLimit;
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->closed = true;

        if (!$this->listening) {
            $this->emit('close');
        }
    }

    public function close()
    {
        $this->closed = true;
        $this->listening = false;
        $this->data = '';

        $this->emit('close');
    }

    public function handleWrite()
    {
        set_error_handler(array($this, 'errorHandler'));

        $sent = fwrite($this->stream, $this->data);

        restore_error_handler();

        if (false === $sent) {
            $this->emit('error', array(new \ErrorException(
                $this->lastError['message'],
                0,
                $this->lastError['number'],
                $this->lastError['file'],
                $this->lastError['line']
            )));

            return;
        }

        $len = strlen($this->data);
        if ($len >= $this->softLimit && $len - $sent < $this->softLimit) {
            $this->emit('drain');
        }

        $this->data = substr($this->data, $sent);

        if (0 === strlen($this->data)) {
            $this->loop->removeWriteStream($this->stream);
            $this->listening = false;

            $this->emit('close');
        }
    }

    private function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $this->lastError['number']  = $errno;
        $this->lastError['message'] = $errstr;
        $this->lastError['file']    = $errfile;
        $this->lastError['line']    = $errline;
    }
}
