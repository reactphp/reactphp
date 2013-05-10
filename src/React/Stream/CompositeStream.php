<?php

namespace React\Stream;

use Evenement\EventEmitter;

class CompositeStream extends EventEmitter implements ReadableStreamInterface, WritableStreamInterface
{
    protected $readable;
    protected $writable;
    protected $pipeSource;

    public function __construct(ReadableStreamInterface $readable, WritableStreamInterface $writable)
    {
        $this->readable = $readable;
        $this->writable = $writable;

        Util::forwardEvents($this->readable, $this, array('data', 'end', 'error', 'close'));
        Util::forwardEvents($this->writable, $this, array('drain', 'error', 'close', 'pipe'));

        $this->readable->on('close', array($this, 'close'));
        $this->writable->on('close', array($this, 'close'));

        $this->on('pipe', array($this, 'handlePipeEvent'));
    }

    public function handlePipeEvent($source)
    {
        $this->pipeSource = $source;
    }

    public function isReadable()
    {
        return $this->readable->isReadable();
    }

    public function pause()
    {
        if ($this->pipeSource) {
            $this->pipeSource->pause();
        }

        $this->readable->pause();
    }

    public function resume()
    {
        if ($this->pipeSource) {
            $this->pipeSource->resume();
        }

        $this->readable->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function isWritable()
    {
        return $this->writable->isWritable();
    }

    public function write($data)
    {
        return $this->writable->write($data);
    }

    public function end($data = null)
    {
        $this->writable->end($data);
    }

    public function close()
    {
        $this->pipeSource = null;

        $this->readable->close();
        $this->writable->close();
    }
}
