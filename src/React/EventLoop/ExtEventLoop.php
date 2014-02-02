<?php

namespace React\EventLoop;

use Event;
use EventBase;
use React\EventLoop\Tick\FutureTickQueue;
use React\EventLoop\Tick\NextTickQueue;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use SplObjectStorage;

/**
 * An ext-event based event-loop.
 */
class ExtEventLoop implements LoopInterface
{
    private $eventBase;
    private $nextTickQueue;
    private $futureTickQueue;
    private $timerCallback;
    private $timerEvents;
    private $streamCallback;
    private $streamEvents = [];
    private $streamFlags = [];
    private $readListeners = [];
    private $writeListeners = [];
    private $running;

    public function __construct()
    {
        $this->eventBase = new EventBase();
        $this->nextTickQueue = new NextTickQueue($this);
        $this->futureTickQueue = new FutureTickQueue($this);
        $this->timerEvents = new SplObjectStorage();

        $this->createTimerCallback();
        $this->createStreamCallback();
    }

    /**
     * {@inheritdoc}
     */
    public function addReadStream($stream, callable $listener)
    {
        $key = (int) $stream;

        if (!isset($this->readListeners[$key])) {
            $this->readListeners[$key] = $listener;
            $this->subscribeStreamEvent($stream, Event::READ);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addWriteStream($stream, callable $listener)
    {
        $key = (int) $stream;

        if (!isset($this->writeListeners[$key])) {
            $this->writeListeners[$key] = $listener;
            $this->subscribeStreamEvent($stream, Event::WRITE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeReadStream($stream)
    {
        $key = (int) $stream;

        if (isset($this->readListeners[$key])) {
            unset($this->readListeners[$key]);
            $this->unsubscribeStreamEvent($stream, Event::READ);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeWriteStream($stream)
    {
        $key = (int) $stream;

        if (isset($this->writeListeners[$key])) {
            unset($this->writeListeners[$key]);
            $this->unsubscribeStreamEvent($stream, Event::WRITE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeStream($stream)
    {
        $key = (int) $stream;

        if (isset($this->streamEvents[$key])) {
            $this->streamEvents[$key]->free();

            unset(
                $this->streamFlags[$key],
                $this->streamEvents[$key],
                $this->readListeners[$key],
                $this->writeListeners[$key]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);

        $this->scheduleTimer($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);

        $this->scheduleTimer($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTimer(TimerInterface $timer)
    {
        if ($this->isTimerActive($timer)) {
            $this->timerEvents[$timer]->free();
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
    public function futureTick(callable $listener)
    {
        $this->futureTickQueue->add($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function tick()
    {
        $this->nextTickQueue->tick();

        $this->futureTickQueue->tick();

        // @-suppression: https://github.com/reactphp/react/pull/234#discussion-diff-7759616R226
        @$this->eventBase->loop(EventBase::LOOP_ONCE | EventBase::LOOP_NONBLOCK);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->running = true;

        while ($this->running) {
            $this->nextTickQueue->tick();

            $this->futureTickQueue->tick();

            $flags = EventBase::LOOP_ONCE;
            if (!$this->running || !$this->nextTickQueue->isEmpty() || !$this->futureTickQueue->isEmpty()) {
                $flags |= EventBase::LOOP_NONBLOCK;
            } elseif (!$this->streamEvents && !$this->timerEvents->count()) {
                break;
            }

            // @-suppression: https://github.com/reactphp/react/pull/234#discussion-diff-7759616R226
            @$this->eventBase->loop($flags);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->running = false;
    }

    /**
     * Schedule a timer for execution.
     *
     * @param TimerInterface $timer
     */
    private function scheduleTimer(TimerInterface $timer)
    {
        $flags = Event::TIMEOUT;

        if ($timer->isPeriodic()) {
            $flags |= Event::PERSIST;
        }

        $event = new Event($this->eventBase, -1, $flags, $this->timerCallback, $timer);
        $this->timerEvents[$timer] = $event;

        $event->add($timer->getInterval());
    }

    /**
     * Create a new ext-event Event object, or update the existing one.
     *
     * @param stream  $stream
     * @param integer $flag   Event::READ or Event::WRITE
     */
    private function subscribeStreamEvent($stream, $flag)
    {
        $key = (int) $stream;

        if (isset($this->streamEvents[$key])) {
            $event = $this->streamEvents[$key];
            $flags = ($this->streamFlags[$key] |= $flag);

            $event->del();
            $event->set($this->eventBase, $stream, Event::PERSIST | $flags, $this->streamCallback);
        } else {
            $event = new Event($this->eventBase, $stream, Event::PERSIST | $flag, $this->streamCallback);

            $this->streamEvents[$key] = $event;
            $this->streamFlags[$key] = $flag;
        }

        $event->add();
    }

    /**
     * Update the ext-event Event object for this stream to stop listening to
     * the given event type, or remove it entirely if it's no longer needed.
     *
     * @param stream  $stream
     * @param integer $flag   Event::READ or Event::WRITE
     */
    private function unsubscribeStreamEvent($stream, $flag)
    {
        $key = (int) $stream;

        $flags = $this->streamFlags[$key] &= ~$flag;

        if (0 === $flags) {
            $this->removeStream($stream);

            return;
        }

        $event = $this->streamEvents[$key];

        $event->del();
        $event->set($this->eventBase, $stream, Event::PERSIST | $flags, $this->streamCallback);
        $event->add();
    }

    /**
     * Create a callback used as the target of timer events.
     *
     * A reference is kept to the callback for the lifetime of the loop
     * to prevent "Cannot destroy active lambda function" fatal error from
     * the event extension.
     */
    private function createTimerCallback()
    {
        $this->timerCallback = function ($_, $_, $timer) {
            call_user_func($timer->getCallback(), $timer);

            if (!$timer->isPeriodic() && $this->isTimerActive($timer)) {
                $this->cancelTimer($timer);
            }
        };
    }

    /**
     * Create a callback used as the target of stream events.
     *
     * A reference is kept to the callback for the lifetime of the loop
     * to prevent "Cannot destroy active lambda function" fatal error from
     * the event extension.
     */
    private function createStreamCallback()
    {
        $this->streamCallback = function ($stream, $flags) {
            $key = (int) $stream;

            if (Event::READ === (Event::READ & $flags) && isset($this->readListeners[$key])) {
                call_user_func($this->readListeners[$key], $stream, $this);
            }

            if (Event::WRITE === (Event::WRITE & $flags) && isset($this->writeListeners[$key])) {
                call_user_func($this->writeListeners[$key], $stream, $this);
            }
        };
    }
}
