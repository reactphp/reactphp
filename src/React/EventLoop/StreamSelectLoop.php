<?php

namespace React\EventLoop;

class StreamSelectLoop implements LoopInterface
{
    private $timeout;

    private $readStreams = array();
    private $readListeners = array();

    private $writeStreams = array();
    private $writeListeners = array();

    // timeout = microseconds
    public function __construct($timeout = 1000000)
    {
        $this->timeout = $timeout;
    }

    public function addReadStream($stream, $listener)
    {
        $this->readStreams[] = $stream;

        if (!isset($this->readListeners[(int) $stream])) {
            $this->readListeners[(int) $stream] = array();
        }
        $this->readListeners[(int) $stream][] = $listener;
    }

    public function addWriteStream($stream, $listener)
    {
        $this->writeStreams[] = $stream;

        if (!isset($this->writeListeners[(int) $stream])) {
            $this->writeListeners[(int) $stream] = array();
        }
        $this->writeListeners[(int) $stream][] = $listener;
    }

    public function removeStream($stream)
    {
        if (false !== ($index = array_search($stream, $this->readStreams))) {
            unset($this->readStreams[$index]);
            unset($this->readListeners[(int) $stream]);
        }

        if (false !== ($index = array_search($stream, $this->writeStreams))) {
            unset($this->writeStreams[$index]);
            unset($this->writeListeners[(int) $stream]);
        }
    }

    public function tick()
    {
        $read = $this->readStreams ?: null;
        $write = $this->writeStreams ?: null;
        @stream_select($read, $write, $except = null, 0, $this->timeout);
        if ($read) {
            foreach ($read as $stream) {
                foreach ($this->readListeners[(int) $stream] as $listener) {
                    call_user_func($listener, $stream);
                }
            }
        }
        if ($write) {
            foreach ($write as $stream) {
                foreach ($this->writeListeners[(int) $stream] as $listeners) {
                    foreach ($listeners as $listener) {
                        call_user_func($listener, $stream);
                    }
                }
            }
        }
    }

    public function run()
    {
        // @codeCoverageIgnoreStart
        while (true) {
            $this->tick();
        }
        // @codeCoverageIgnoreEnd
    }
}
