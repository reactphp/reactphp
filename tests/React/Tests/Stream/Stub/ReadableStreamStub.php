<?php

namespace React\Tests\Stream\Stub;

use Evenement\EventEmitter;
use React\Stream\ReadableStream;
use React\Stream\WritableStream;
use React\Stream\Util;

class ReadableStreamStub extends EventEmitter implements ReadableStream
{
    public $paused = false;

    // trigger data event
    public function write($data)
    {
        $this->emit('data', array($data));
    }

    // trigger error event
    public function error($error)
    {
        $this->emit('error', array($error));
    }

    // trigger end event
    public function end()
    {
        $this->emit('end', array());
    }

    public function pause()
    {
        $this->paused = true;
    }

    public function resume()
    {
        $this->paused = false;
    }

    public function close()
    {
        $this->emit('close');
    }

    public function pipe(WritableStream $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }
}
