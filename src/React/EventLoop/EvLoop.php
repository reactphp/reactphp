<?php

namespace React\EventLoop;

use SplObjectStorage;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;

class EvLoop implements LoopInterface
{
    private $loop;
    private $timers;
    private $readEvents = array();
    private $writeEvents = array();

    public function __construct()
    {
        $this->loop = new \EvLoop();
        $this->timers = new SplObjectStorage();
    }

    public function addReadStream($stream, $listener)
    {
        $this->addStream($stream, $listener, \Ev::READ);
    }

    public function addWriteStream($stream, $listener)
    {
        $this->addStream($stream, $listener, \Ev::WRITE);
    }

    public function removeReadStream($stream)
    {
        $this->readEvents[(int)$stream]->stop();
        unset($this->readEvents[(int)$stream]);
    }

    public function removeWriteStream($stream)
    {
        $this->writeEvents[(int)$stream]->stop();
        unset($this->writeEvents[(int)$stream]);
    }

    public function removeStream($stream)
    {
        if (isset($this->readEvents[(int)$stream])) {
            $this->removeReadStream($stream);
        }

        if (isset($this->writeEvents[(int)$stream])) {
            $this->removeWriteStream($stream);
        }
    }

    private function addStream($stream, $listener, $flags)
    {
        $listener = $this->wrapStreamListener($stream, $listener, $flags);
        $event = $this->loop->io($stream, $flags, $listener);

        if (($flags & \Ev::READ) === $flags) {
            $this->readEvents[(int)$stream] = $event;
        } elseif (($flags & \Ev::WRITE) === $flags) {
            $this->writeEvents[(int)$stream] = $event;
        }
    }

    private function wrapStreamListener($stream, $listener, $flags)
    {
        if (($flags & \Ev::READ) === $flags) {
            $removeCallback = array($this, 'removeReadStream');
        } elseif (($flags & \Ev::WRITE) === $flags) {
            $removeCallback = array($this, 'removeWriteStream');
        }

        return function ($event) use ($stream, $listener, $removeCallback) {
            if (feof($stream)) {
                call_user_func($removeCallback, $stream);

                return;
            }

            call_user_func($listener, $stream);
        };
    }

    public function addTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);
        $this->setupTimer($timer);

        return $timer;
    }

    public function addPeriodicTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);
        $this->setupTimer($timer);

        return $timer;
    }

    public function cancelTimer(TimerInterface $timer)
    {
        if (isset($this->timers[$timer])) {
            $this->timers[$timer]->stop();
            $this->timers->detach($timer);
        }
    }

    private function setupTimer(TimerInterface $timer)
    {
        $dummyCallback = function () {};
        $interval = $timer->getInterval();

        if ($timer->isPeriodic()) {
            $libevTimer = $this->loop->timer($interval, $interval, $dummyCallback);
        } else {
            $libevTimer = $this->loop->timer($interval, $dummyCallback);
        }

        $libevTimer->setCallback(function () use ($timer) {
            call_user_func($timer->getCallback(), $timer);

            if (!$timer->isPeriodic()) {
                $timer->cancel();
            }
        });

        $this->timers->attach($timer, $libevTimer);

        return $timer;
    }

    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    public function tick()
    {
        $this->loop->run(\Ev::RUN_ONCE);
    }

    public function run()
    {
        $this->loop->run();
    }

    public function stop()
    {
        $this->loop->stop();
    }
}
