<?php

namespace React\EventLoop;

use Exception;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use SplObjectStorage;
use SplPriorityQueue;
use SplQueue;

/**
 * A stream_select() based event-loop with support for nextTick().
 */
class StreamSelectNextTickLoop extends AbstractNextTickLoop
{
    private $readStreams = [];
    private $readListeners = [];
    private $writeStreams = [];
    private $writeListeners = [];
    private $timerQueue;
    private $timerTimestamps;

    public function __construct()
    {
        $this->timerQueue = new SplPriorityQueue;
        $this->timerTimestamps = new SplObjectStorage;

        parent::__construct();
    }

    /**
     * Register a listener to be notified when a stream is ready to read.
     *
     * @param stream   $stream   The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addReadStream($stream, $listener)
    {
        $key = $this->streamKey($stream);

        if (!array_key_exists($key, $this->readStreams)) {
            $this->readStreams[$key] = $stream;
            $this->readListeners[$key] = $listener;
        }
    }

    /**
     * Register a listener to be notified when a stream is ready to write.
     *
     * @param stream   $stream   The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addWriteStream($stream, $listener)
    {
        $key = $this->streamKey($stream);

        if (!array_key_exists($key, $this->writeStreams)) {
            $this->writeStreams[$key] = $stream;
            $this->writeListeners[$key] = $listener;
        }
    }

    /**
     * Remove the read event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeReadStream($stream)
    {
        $key = $this->streamKey($stream);

        unset(
            $this->readStreams[$key],
            $this->readListeners[$key]
        );
    }

    /**
     * Remove the write event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeWriteStream($stream)
    {
        $key = $this->streamKey($stream);

        unset(
            $this->writeStreams[$key],
            $this->writeListeners[$key]
        );
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
     * Cancel a pending timer.
     *
     * @param TimerInterface $timer The timer to cancel.
     */
    public function cancelTimer(TimerInterface $timer)
    {
        $this->timerTimestamps->detach($timer);
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
        return $this->timerTimestamps->contains($timer);
    }

    /**
     * Flush any timer and IO events.
     *
     * @param boolean $blocking True if loop should block waiting for next event.
     */
    protected function flushEvents($blocking)
    {
        $this->flushTimerQueue();
        $this->waitForStreamActivity($blocking);
    }

    /**
     * Check if the loop has any pending timers or streams.
     *
     * @return boolean
     */
    protected function isEmpty()
    {
        return 0 === count($this->timerTimestamps)
            && 0 === count($this->readStreams)
            && 0 === count($this->writeStreams);
    }

    /**
     * Get the current time in microseconds.
     *
     * @return integer
     */
    protected function now()
    {
        return $this->toMicroSeconds(
            microtime(true)
        );
    }

    /**
     * Convert the given time to microseconds.
     *
     * @param integer|float $seconds
     *
     * @return integer
     */
    protected function toMicroSeconds($seconds)
    {
        return intval($seconds * 1000000);
    }

    /**
     * Emulate a stream_select() implementation that does not break when passed
     * empty stream arrays.
     *
     * @param array &$read An array of read streams to select upon.
     * @param array &$write An array of write streams to select upon.
     * @param integer|null $timeout Activity timeout in microseconds, or null to wait forever.
     */
    protected function streamSelect(array &$read, array &$write, $timeout)
    {
        if ($read || $write) {
            $except = null;

            return stream_select(
                $read,
                $write,
                $except,
                $timeout === null ? null : 0,
                $timeout
            );
        }

        usleep($timeout);

        return 0;
    }

    /**
     * Schedule a timer for execution.
     *
     * @param TimerInterface $timer
     */
    protected function scheduleTimer(TimerInterface $timer)
    {
        $executeAt = $this->now() + $this->toMicroSeconds(
            $timer->getInterval()
        );

        $this->timerQueue->insert($timer, -$executeAt);
        $this->timerTimestamps->attach($timer, $executeAt);
    }

    /**
     * Get the timer next schedule to tick, if any.
     *
     * @return TimerInterface|null
     */
    protected function nextActiveTimer()
    {
        while ($this->timerQueue->count()) {
            $timer = $this->timerQueue->top();

            if ($this->isTimerActive($timer)) {
                return $timer;
            } else {
                $this->timerQueue->extract();
            }
        }

        return null;
    }

    /**
     * Push callbacks for timers that are ready into the next-tick queue.
     */
    protected function flushTimerQueue()
    {
        $now = $this->now();

        while ($timer = $this->nextActiveTimer()) {

            $executeAt = $this->timerTimestamps[$timer];

            // The next time is in the future, exit the loop ...
            if ($executeAt > $now) {
                break;
            }

            $this->timerQueue->extract();

            call_user_func($timer->getCallback(), $timer);

            // Timer cancelled itself ...
            if (!$this->isTimerActive($timer)) {
                return;
            // Reschedule periodic timers ...
            } elseif ($timer->isPeriodic()) {
                $this->scheduleTimer($timer);
            // Cancel one-shot timers ...
            } else {
                $this->cancelTimer($timer);
            }
        }
    }

    protected function waitForStreamActivity($blocking)
    {
        // The $blocking flag takes precedence ...
        if (!$blocking) {
            $timeout = 0;

        // There is a pending timer, only block until it is due ...
        } elseif ($timer = $this->nextActiveTimer()) {
            $timeout = max(
                0,
                $this->timerTimestamps[$timer] - $this->now()
            );

        // The only possible event is stream activity, so wait forever ...
        } elseif ($this->readStreams || $this->writeStreams) {
            $timeout = null;

        // THere's nothing left to do ...
        } else {
            return;
        }

        $read = $this->readStreams;
        $write = $this->writeStreams;

        $this->streamSelect($read, $write, $timeout);

        $this->flushStreamEvents($read, $this->readListeners);
        $this->flushStreamEvents($write, $this->writeListeners);
    }

    protected function flushStreamEvents(array $streams, array &$listeners)
    {
        foreach ($streams as $stream) {
            $key = $this->streamKey($stream);

            if (!array_key_exists($key, $listeners)) {
                continue;
            }

            call_user_func($listeners[$key], $stream, $this);
        }
    }
}
