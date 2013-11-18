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
     * Register a listener to be notified when a stream is ready to read.
     *
     * @param stream   $stream   The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addReadStream($stream, $listener)
    {
        $this->addStream($stream, $listener, IOEvent::READ);
    }

    /**
     * Register a listener to be notified when a stream is ready to write.
     *
     * @param stream   $stream   The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addWriteStream($stream, $listener)
    {
        $this->addStream($stream, $listener, IOEvent::WRITE);
    }

    /**
     * Remove the read event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeReadStream($stream)
    {
        if (isset($this->readEvents[(int) $stream])) {
            $this->readEvents[(int) $stream]->stop();
            unset($this->readEvents[(int) $stream]);
        }
    }

    /**
     * Remove the write event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeWriteStream($stream)
    {
        if (isset($this->writeEvents[(int) $stream])) {
            $this->writeEvents[(int) $stream]->stop();
            unset($this->writeEvents[(int) $stream]);
        }
    }

    /**
     * Remove all listeners for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    /**
     * Enqueue a callback to be invoked once after the given interval.
     *
     * The execution order of timers scheduled to execute at the same time is
     * not guaranteed.
     *
     * @param numeric  $interval The number of seconds to wait before execution.
     * @param callable $callback The callback to invoke.
     *
     * @return TimerInterface
     */
    public function addTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);
        $this->setupTimer($timer);

        return $timer;
    }

    /**
     * Enqueue a callback to be invoked repeatedly after the given interval.
     *
     * The execution order of timers scheduled to execute at the same time is
     * not guaranteed.
     *
     * @param numeric  $interval The number of seconds to wait before execution.
     * @param callable $callback The callback to invoke.
     *
     * @return TimerInterface
     */
    public function addPeriodicTimer($interval, $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);
        $this->setupTimer($timer);

        return $timer;
    }

    /**
     * Cancel a pending timer.
     *
     * @param TimerInterface $timer The timer to cancel.
     */
    public function cancelTimer(TimerInterface $timer)
    {
        if (isset($this->timers[$timer])) {
            $this->loop->remove($this->timers[$timer]);
            $this->timers->detach($timer);
        }
    }

    /**
     * Check if a given timer is active.
     *
     * @param TimerInterface $timer The timer to check.
     *
     * @return boolean True if the timer is still enqueued for execution.
     */
    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    /**
     * Schedule a callback to be invoked on the next tick of the event loop.
     *
     * Callbacks are guaranteed to be executed in the order they are enqueued,
     * before any timer or stream events.
     *
     * @param callable $listner The callback to invoke.
     */
    public function nextTick(callable $listener)
    {
        $this->nextTickQueue->add($listener);
    }

    /**
     * Perform a single iteration of the event loop.
     */
    public function tick()
    {
        $this->nextTickQueue->tick();

        $this->loop->run(EventLoop::RUN_ONCE | EventLoop::RUN_NOWAIT);
    }

    /**
     * Run the event loop until there are no more tasks to perform.
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
     * Instruct a running event loop to stop.
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
