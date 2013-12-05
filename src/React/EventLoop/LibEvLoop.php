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
    private $timerEvents;
    private $readListeners = [];
    private $writeListeners = [];
    private $readEvents = [];
    private $writeEvents = [];
    private $running;

    public function __construct()
    {
        $this->loop = new EventLoop();
        $this->nextTickQueue = new NextTickQueue($this);
        $this->timerEvents = new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function onReadable($stream, callable $listener)
    {
        $key = (int) $stream;

        if (isset($this->readListeners[$key])) {
            throw new \RuntimeException(sprintf('Stream %s already has a read listener.', $key));
        }

        $this->readListeners[$key] = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function enableRead($stream)
    {
        $callback = function () use ($stream) {
            $key = (int) $stream;

            if (isset($this->readListeners[$key])) {
                call_user_func($this->readListeners[$key], $stream, $this);
            }
        };

        $event = new IOEvent($callback, $stream, IOEvent::READ);
        $this->loop->add($event);

        $this->readEvents[(int) $stream] = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function disableRead($stream)
    {
        $key = (int) $stream;

        if (isset($this->readEvents[$key])) {
            $this->readEvents[$key]->stop();
            unset($this->readEvents[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onWritable($stream, callable $listener)
    {
        $key = (int) $stream;

        if (isset($this->writeListeners[$key])) {
            throw new \RuntimeException(sprintf('Stream %s already has a write listener.', $key));
        }

        $this->writeListeners[$key] = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function enableWrite($stream)
    {
        $callback = function () use ($stream) {
            $key = (int) $stream;

            if (isset($this->writeListeners[$key])) {
                call_user_func($this->writeListeners[$key], $stream, $this);
            }
        };

        $event = new IOEvent($callback, $stream, IOEvent::WRITE);
        $this->loop->add($event);

        $this->writeEvents[(int) $stream] = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function disableWrite($stream)
    {
        $key = (int) $stream;

        if (isset($this->writeEvents[$key])) {
            $this->writeEvents[$key]->stop();
            unset($this->writeEvents[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($stream)
    {
        $this->disableRead($stream);
        $this->disableWrite($stream);

        $key = (int) $stream;

        unset(
            $this->readListeners[$key],
            $this->writeListeners[$key]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);

        $callback = function () use ($timer) {
            call_user_func($timer->getCallback(), $timer);

            if ($this->isTimerActive($timer)) {
                $this->cancelTimer($timer);
            }
        };

        $event = new TimerEvent($callback, $timer->getInterval());
        $this->timerEvents->attach($timer, $event);
        $this->loop->add($event);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);

        $callback = function () use ($timer) {
            call_user_func($timer->getCallback(), $timer);
        };

        $event = new TimerEvent($callback, $interval, $interval);
        $this->timerEvents->attach($timer, $event);
        $this->loop->add($event);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTimer(TimerInterface $timer)
    {
        if (isset($this->timerEvents[$timer])) {
            $this->loop->remove($this->timerEvents[$timer]);
            $this->timerEvents->detach($timer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timerEvents->contains($timer);
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

            if (!$this->readEvents && !$this->writeEvents && !$this->timerEvents->count()) {
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
}
