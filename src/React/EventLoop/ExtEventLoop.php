<?php

namespace React\EventLoop;

use Event;
use EventBase;
use React\EventLoop\NextTick\NextTickQueue;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use SplObjectStorage;
use stdClass;

/**
 * An ext-event based event-loop.
 */
class ExtEventLoop implements LoopInterface
{
    private $eventBase;
    private $nextTickQueue;
    private $timerEvents;
    private $streamEvents;
    private $running;
    private $keepAlive;

    /**
     * @param EventBase|null $eventBase The libevent event base object.
     */
    public function __construct(EventBase $eventBase = null)
    {
        if (null === $eventBase) {
            $eventBase = new EventBase;
        }

        $this->eventBase = $eventBase;
        $this->nextTickQueue = new NextTickQueue($this);
        $this->timerEvents = new SplObjectStorage;
        $this->streamEvents = [];

        // Closures for cancelled timers and removed stream listeners are
        // kept in the keepAlive array until the flushEvents() is complete
        // to prevent the PHP fatal error caused by ext-event:
        // "Cannot destroy active lambda function"
        $this->keepAlive = [];
    }

    /**
     * Register a listener to be notified when a stream is ready to read.
     *
     * @param stream   $stream   The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addReadStream($stream, $listener)
    {
        $this->addStreamEvent($stream, Event::READ, $listener);
    }

    /**
     * Register a listener to be notified when a stream is ready to write.
     *
     * @param stream   $stream   The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addWriteStream($stream, $listener)
    {
        $this->addStreamEvent($stream, Event::WRITE, $listener);
    }

    /**
     * Remove the read event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeReadStream($stream)
    {
        $this->removeStreamEvent($stream, Event::READ);
    }

    /**
     * Remove the write event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeWriteStream($stream)
    {
        $this->removeStreamEvent($stream, Event::WRITE);
    }

    /**
     * Remove all listeners for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeStream($stream)
    {
        $key = (int) $stream;

        if (!isset($this->streamEvents[$key])) {
            return;
        }

        $entry = $this->streamEvents[$key];

        $entry->event->free();

        unset($this->streamEvents[$key]);

        $this->keepAlive[] = $entry->callback;
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

        $this->scheduleTimer($timer);

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

        $this->scheduleTimer($timer);

        return $timer;
    }

    /**
     * Cancel a pending timer.
     *
     * @param TimerInterface $timer The timer to cancel.
     */
    public function cancelTimer(TimerInterface $timer)
    {
        if ($this->isTimerActive($timer)) {
            $entry = $this->timerEvents[$timer];

            $this->timerEvents->detach($timer);

            $entry->event->free();

            $this->keepAlive[] = $entry->callback;
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
        return $this->timerEvents->contains($timer);
    }

    /**
     * Schedule a callback to be invoked on the next tick of the event loop.
     *
     * Callbacks are guaranteed to be executed in the order they are enqueued,
     * before any timer or stream events.
     *
     * @param callable $listener The callback to invoke.
     */
    public function nextTick(callable $listener)
    {
        $this->nextTickQueue->add($listener);
    }

    /**
     * Perform a single iteration of the event loop.
     *
     * @param boolean $blocking True if loop should block waiting for next event.
     */
    public function tick()
    {
        $this->nextTickQueue->tick();

        $this->eventBase->loop(EventBase::LOOP_ONCE | EventBase::LOOP_NONBLOCK);

        $this->keepAlive = [];
    }

    /**
     * Run the event loop until there are no more tasks to perform.
     */
    public function run()
    {
        $this->running = true;

        while ($this->running) {

            if (
                !$this->streamEvents
                && !$this->timerEvents->count()
                && $this->nextTickQueue->isEmpty()
            ) {
                break;
            }

            $this->nextTickQueue->tick();

            $this->eventBase->loop(EventBase::LOOP_ONCE);

            $this->keepAlive = [];

        }
    }

    /**
     * Instruct a running event loop to stop.
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
    protected function scheduleTimer(TimerInterface $timer)
    {
        $flags = Event::TIMEOUT;

        if ($timer->isPeriodic()) {
            $flags |= Event::PERSIST;
        }

        $entry = new stdClass;
        $entry->callback = function () use ($timer) {
            call_user_func($timer->getCallback(), $timer);

            // Clean-up one shot timers ...
            if ($this->isTimerActive($timer) && !$timer->isPeriodic()) {
                $this->cancelTimer($timer);
            }
        };

        $entry->event = new Event(
            $this->eventBase,
            -1,
            $flags,
            $entry->callback
        );

        $this->timerEvents->attach($timer, $entry);

        $entry->event->add($timer->getInterval());
    }

    /**
     * Create a new ext-event Event object, or update the existing one.
     *
     * @param stream   $stream
     * @param integer  $flag     Event::READ or Event::WRITE
     * @param callable $listener
     */
    protected function addStreamEvent($stream, $flag, $listener)
    {
        $key = (int) $stream;

        if (isset($this->streamEvents[$key])) {
            $entry = $this->streamEvents[$key];
        } else {
            $entry = new stdClass;
            $entry->event = null;
            $entry->flags = 0;
            $entry->listeners = [
                Event::READ => null,
                Event::WRITE => null,
            ];

            $entry->callback = function ($stream, $flags, $loop) use ($entry) {
                foreach ([Event::READ, Event::WRITE] as $flag) {
                    if (
                        $flag === ($flags & $flag) &&
                        is_callable($entry->listeners[$flag])
                    ) {
                        call_user_func(
                            $entry->listeners[$flag],
                            $stream,
                            $this
                        );
                    }
                }
            };

            $this->streamEvents[$key] = $entry;
        }

        $entry->listeners[$flag] = $listener;
        $entry->flags |= $flag;

        $this->configureStreamEvent($entry, $stream);

        $entry->event->add();
    }

    /**
     * Update the ext-event Event object for this stream to stop listening to
     * the given event type, or remove it entirely if it's no longer needed.
     *
     * @param stream  $stream
     * @param integer $flag   Event::READ or Event::WRITE
     */
    protected function removeStreamEvent($stream, $flag)
    {
        $key = (int) $stream;

        if (!isset($this->streamEvents[$key])) {
            return;
        }

        $entry = $this->streamEvents[$key];
        $entry->flags &= ~$flag;
        $entry->listeners[$flag] = null;

        if (0 === $entry->flags) {
            $this->removeStream($stream);
        } else {
            $this->configureStreamEvent($entry, $stream);
        }
    }

    /**
     * Create or update an ext-event Event object for the stream.
     */
    protected function configureStreamEvent($entry, $stream)
    {
        $flags = $entry->flags | Event::PERSIST;

        if ($entry->event) {
            $entry->event->del();
            $entry->event->set(
                $this->eventBase, $stream, $flags, $entry->callback
            );
            $entry->event->add();
        } else {
            $entry->event = new Event(
                $this->eventBase, $stream, $flags, $entry->callback
            );
        }
    }
}
