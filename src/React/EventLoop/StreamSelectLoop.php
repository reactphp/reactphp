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

    public function onReadable($stream, callable $listener)
    {
        $id = (int) $stream;

        if (isset($this->readListeners[$id])) {
            throw new \RuntimeException(sprintf('Stream %s already has a read listener.', $id));
        }

        $this->readListeners[$id] = $listener;
    }

    public function enableRead($stream)
    {
        $id = (int) $stream;
        $this->readStreams[$id] = $stream;
    }

    public function disableRead($stream)
    {
        $id = (int) $stream;
        unset($this->readStreams[$id]);
    }

    public function onWritable($stream, callable $listener)
    {
        $id = (int) $stream;

        if (isset($this->writeListeners[$id])) {
            throw new \RuntimeException(sprintf('Stream %s already has a write listener.', $id));
        }

        $this->writeListeners[$id] = $listener;
    }

    public function enableWrite($stream)
    {
        $id = (int) $stream;
        $this->writeStreams[$id] = $stream;
    }

    public function disableWrite($stream)
    {
        $id = (int) $stream;
        unset($this->writeStreams[$id]);
    }

    public function remove($stream)
    {
        $id = (int) $stream;

        unset(
            $this->readStreams[$id],
            $this->readListeners[$id],
            $this->writeStreams[$id],
            $this->writeListeners[$id]
        );
    }

    public function addTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);
        $this->timers->add($timer);

        return $timer;
    }

    public function addPeriodicTimer($interval, callable $callback)
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

    protected function runStreamSelect($block)
    {
        $read = $this->readStreams ?: null;
        $write = $this->writeStreams ?: null;
        $except = null;

        if (!$read && !$write) {
            if ($block) {
                $this->sleepOnPendingTimers();
            }

            return;
        }

        $timeout = $block ? $this->getNextEventTimeInMicroSeconds() : 0;

        if (stream_select($read, $write, $except, 0, $timeout) > 0) {
            if ($read) {
                foreach ($read as $stream) {
                    $id = (int) $stream;

                    if (!isset($this->readListeners[$id])) {
                        continue;
                    }

                    $listener = $this->readListeners[$id];
                    $listener($stream, $this);
                }
            }

            if ($write) {
                foreach ($write as $stream) {
                    $id = (int) $stream;

                    if (!isset($this->writeListeners[$id])) {
                        continue;
                    }

                    $listener = $this->writeListeners[$id];
                    $listener($stream, $this);
                }
            }
        }
    }

    protected function loop($block = true)
    {
        $this->timers->tick();
        $this->runStreamSelect($block);

        return $this->running;
    }

    public function tick()
    {
        return $this->loop(false);
    }

    public function run()
    {
        $this->running = true;
        while ($this->loop());
    }

    public function stop()
    {
        $this->running = false;
    }
}
