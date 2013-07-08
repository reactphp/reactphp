<?php

namespace React\EventLoop;

use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use React\EventLoop\Timer\Timers;

class StreamSelectLoop implements LoopInterface
{
    const QUANTUM_INTERVAL = 1000000;

    private $timers;
    private $running = false;
    private $readStreams = array();
    private $readListeners = array();
    private $writeStreams = array();
    private $writeListeners = array();

    public function __construct()
    {
        $this->timers = new Timers();
    }

    public function addReadStream($stream, $listener)
    {
        $id = (int) $stream;

        if (!isset($this->readStreams[$id])) {
            $this->readStreams[$id] = $stream;
            $this->readListeners[$id] = $listener;
        }
    }

    public function addWriteStream($stream, $listener)
    {
        $id = (int) $stream;

        if (!isset($this->writeStreams[$id])) {
            $this->writeStreams[$id] = $stream;
            $this->writeListeners[$id] = $listener;
        }
    }

    public function removeReadStream($stream)
    {
        $id = (int) $stream;

        unset(
            $this->readStreams[$id],
            $this->readListeners[$id]
        );
    }

    public function removeWriteStream($stream)
    {
        $id = (int) $stream;

        unset(
            $this->writeStreams[$id],
            $this->writeListeners[$id]
        );
    }

    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    public function addTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);
        $this->timers->add($timer);

        return $timer;
    }

    public function addPeriodicTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);
        $this->timers->add($timer);

        return $timer;
    }

    public function cancelTimer(TimerInterface $timer)
    {
        $this->timers->cancel($timer);
    }

    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    protected function getNextEventTimeInMicroSeconds()
    {
        $nextEvent = $this->timers->getFirst();

        if (null === $nextEvent) {
            return self::QUANTUM_INTERVAL;
        }

        $currentTime = microtime(true);
        if ($nextEvent > $currentTime) {
            return ($nextEvent - $currentTime) * 1000000;
        }

        return 0;
    }

    protected function sleepOnPendingTimers()
    {
        if ($this->timers->isEmpty()) {
            $this->running = false;
        } else {
            // We use usleep() instead of stream_select() to emulate timeouts
            // since the latter fails when there are no streams registered for
            // read / write events. Blame PHP for us needing this hack.
            usleep($this->getNextEventTimeInMicroSeconds());
        }
    }

    protected function runStreamSelect()
    {
        $read = $this->readStreams ?: null;
        $write = $this->writeStreams ?: null;
        $except = null;

        if (!$read && !$write) {
            $this->sleepOnPendingTimers();

            return;
        }

        if (stream_select($read, $write, $except, 0, $this->getNextEventTimeInMicroSeconds()) > 0) {
            if ($read) {
                foreach ($read as $stream) {
                    if (!isset($this->readListeners[(int) $stream])) {
                        continue;
                    }

                    $listener = $this->readListeners[(int) $stream];
                    call_user_func($listener, $stream, $this);
                }
            }

            if ($write) {
                foreach ($write as $stream) {
                    if (!isset($this->writeListeners[(int) $stream])) {
                        continue;
                    }

                    $listener = $this->writeListeners[(int) $stream];
                    call_user_func($listener, $stream, $this);
                }
            }
        }
    }

    public function tick()
    {
        $this->timers->tick();
        $this->runStreamSelect();

        return $this->running;
    }

    public function run()
    {
        $this->running = true;

        while ($this->tick()) {
            // NOOP
        }
    }

    public function stop()
    {
        $this->running = false;
    }
}
