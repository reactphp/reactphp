<?php

namespace React\EventLoop;

use libev\EventLoop;
use libev\IOEvent;
use libev\TimerEvent;
use React\EventLoop\Tick\NextTickQueue;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use SplObjectStorage;

/**
 * @see https://github.com/m4rw3r/php-libev
 * @see https://gist.github.com/1688204
 */
class LibEvLoop implements LoopInterface
{
    private $loop;
    private $nextTickQueue;
    private $timers;
    private $readEvents = array();
    private $writeEvents = array();
    private $running;

    public function __construct()
    {
        $this->loop = new EventLoop;
        $this->nextTickQueue = new NextTickQueue($this);
        $this->timers = new SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function addReadStream($stream, $listener)
    {
        $this->addStream($stream, $listener, IOEvent::READ);
    }

    /**
     * {@inheritdoc}
     */
    public function addWriteStream($stream, $listener)
    {
        $this->addStream($stream, $listener, IOEvent::WRITE);
    }

    /**
     * {@inheritdoc}
     */
    public function removeReadStream($stream)
    {
        if (isset($this->readEvents[(int) $stream])) {
            $this->readEvents[(int) $stream]->stop();
            unset($this->readEvents[(int) $stream]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeWriteStream($stream)
    {
        if (isset($this->writeEvents[(int) $stream])) {
            $this->writeEvents[(int) $stream]->stop();
            unset($this->writeEvents[(int) $stream]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function addTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);
        $this->setupTimer($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);
        $this->setupTimer($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTimer(TimerInterface $timer)
    {
        if (isset($this->timers[$timer])) {
            $this->loop->remove($this->timers[$timer]);
            $this->timers->detach($timer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    /**
     * {@inheritdoc}
     */
    public function nextTick(callable $listener)
    {
        $this->nextTickQueue->add($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function tick()
    {
        $this->nextTickQueue->tick();

        $this->loop->run(EventLoop::RUN_ONCE | EventLoop::RUN_NOWAIT);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->running = true;

        while ($this->running) {

            $this->nextTickQueue->tick();

            if (
                !$this->readEvents
                && !$this->writeEvents
                && !$this->timers->count()
            ) {
                break;
            }

            $this->loop->run(EventLoop::RUN_ONCE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->running = false;
    }

    private function addStream($stream, $listener, $flags)
    {
        $listener = $this->wrapStreamListener($stream, $listener, $flags);
        $event = new IOEvent($listener, $stream, $flags);
        $this->loop->add($event);

        if (($flags & IOEvent::READ) === $flags) {
            $this->readEvents[(int) $stream] = $event;
        } elseif (($flags & IOEvent::WRITE) === $flags) {
            $this->writeEvents[(int) $stream] = $event;
        }
    }

    private function wrapStreamListener($stream, $listener, $flags)
    {
        if (($flags & IOEvent::READ) === $flags) {
            $removeCallback = array($this, 'removeReadStream');
        } elseif (($flags & IOEvent::WRITE) === $flags) {
            $removeCallback = array($this, 'removeWriteStream');
        }

        return function ($event) use ($stream, $listener, $removeCallback) {
            call_user_func($listener, $stream);
        };
    }

    private function setupTimer(TimerInterface $timer)
    {
        $dummyCallback = function () {};
        $interval = $timer->getInterval();

        if ($timer->isPeriodic()) {
            $libevTimer = new TimerEvent($dummyCallback, $interval, $interval);
        } else {
            $libevTimer = new TimerEvent($dummyCallback, $interval);
        }

        $libevTimer->setCallback(function () use ($timer) {
            call_user_func($timer->getCallback(), $timer);

            if (!$timer->isPeriodic()) {
                $timer->cancel();
            }
        });

        $this->timers->attach($timer, $libevTimer);
        $this->loop->add($libevTimer);

        return $timer;
    }
}
