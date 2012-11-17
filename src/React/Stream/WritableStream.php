<?php

namespace React\Stream;

use Evenement\EventEmitter;

class WritableStream extends EventEmitter implements WritableStreamInterface
{
    public $closed = false;
    private $pipeSource;

    public function __construct()
    {
        $this->on('pipe', array($this, 'handlePipeEvent'));
    }

    public function handlePipeEvent($source)
    {
        $this->pipeSource = $source;
    }

    public function write($data)
    {
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->close();
    }

    public function isWritable()
    {
        return !$this->closed;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->emit('end', array($this));
        $this->emit('close', array($this));
        $this->removeAllListeners();
    }
}
