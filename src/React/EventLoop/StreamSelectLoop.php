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
        $streamID = (int) $stream;

        if (!isset($this->readStreams[$streamID])) {
            $this->readStreams[$streamID] = $stream;
            $this->readListeners[$streamID] = array();
        }

        $this->readListeners[$streamID][] = $listener;
    }

    public function addWriteStream($stream, $listener)
    {
        $streamID = (int) $stream;

        if (!isset($this->writeStreams[$streamID])) {
            $this->writeStreams[$streamID] = $stream;
            $this->writeListeners[$streamID] = array();
        }

        $this->writeListeners[$streamID][] = $listener;
    }

    public function removeReadStream($stream)
    {
        $streamID = (int) $stream;

        unset($this->readStreams[$streamID]);
        unset($this->readListeners[$streamID]);
    }

    public function removeWriteStream($stream)
    {
        $streamID = (int) $stream;

        unset($this->writeStreams[$streamID]);
        unset($this->writeListeners[$streamID]);
    }

    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    public function tick()
    {
        $read = $this->readStreams ?: null;
        $write = $this->writeStreams ?: null;
        $excepts = null;

        if (!$read && !$write) {
            return false;
        }

        if (($ready = stream_select($read, $write, $except, 0, $this->timeout)) > 0) {
            if ($read) {
                foreach ($read as $stream) {
                    foreach ($this->readListeners[(int) $stream] as $listener) {
                        if (call_user_func($listener, $stream) === false) {
                            $this->removeReadStream($stream);
                            break;
                        }
                    }
                }
            }
            if ($write) {
                foreach ($write as $stream) {
                    foreach ($this->writeListeners[(int) $stream] as $listener) {
                        if (call_user_func($listener, $stream) === false) {
                            $this->removeWriteStream($stream);
                            break;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function run()
    {
        // @codeCoverageIgnoreStart
        while ($this->tick() === true) {
            // NOOP
        }
        // @codeCoverageIgnoreEnd
    }
}
