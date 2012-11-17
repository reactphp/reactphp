<?php

namespace React\Stream;

use Evenement\EventEmitter;

class ThroughStream extends ReadableStream implements WritableStreamInterface
{
    private $pipeSource;

    public function __construct()
    {
        $this->on('pipe', array($this, 'handlePipeEvent'));
    }

    public function handlePipeEvent($source)
    {
        $this->pipeSource = $source;
    }

    public function filter($data)
    {
        return $data;
    }

    public function write($data)
    {
        $this->emit('data', array($this->filter($data)));
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

    public function pause()
    {
        if ($this->pipeSource) {
            $this->pipeSource->pause();
        }
    }

    public function resume()
    {
        if ($this->pipeSource) {
            $this->pipeSource->resume();
        }
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        parent::close();
        $this->pipeSource = null;
    }
}
