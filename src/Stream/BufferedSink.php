<?php

namespace React\Stream;

use React\Promise\Deferred;
use React\Promise\PromisorInterface;

class BufferedSink extends WritableStream implements PromisorInterface
{
    private $buffer = '';
    private $deferred;

    public function __construct()
    {
        $this->deferred = new Deferred();

        $this->on('pipe', array($this, 'handlePipeEvent'));
        $this->on('error', array($this, 'handleErrorEvent'));
    }

    public function handlePipeEvent($source)
    {
        Util::forwardEvents($source, $this, array('error'));
    }

    public function handleErrorEvent($e)
    {
        $this->deferred->reject($e);
    }

    public function write($data)
    {
        $this->buffer .= $data;
        $this->deferred->progress($data);
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        parent::close();
        $this->deferred->resolve($this->buffer);
    }

    public function promise()
    {
        return $this->deferred->promise();
    }

    public static function createPromise(ReadableStreamInterface $stream)
    {
        $sink = new static();
        $stream->pipe($sink);

        return $sink->promise();
    }
}
