<?php

namespace React\EventLoop;

use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use React\EventLoop\Timer\Timers;

/**
 * A stream_select() based event-loop with support for nextTick().
 */
class StreamSelectNextTickLoop extends AbstractNextTickLoop
{
    private $readStreams = [];
    private $readListeners = [];
    private $writeStreams = [];
    private $writeListeners = [];
    private $timers;

    public function __construct()
    {
        $this->timers = new Timers;

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
        $key = (int) $stream;

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
        $key = (int) $stream;

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
        $key = (int) $stream;

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
        $key = (int) $stream;

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
        $this->timers->add($timer);

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
        $this->timers->add($timer);

        return $timer;
    }

    /**
     * Cancel a pending timer.
     *
     * @param TimerInterface $timer The timer to cancel.
     */
    public function cancelTimer(TimerInterface $timer)
    {
        $this->timers->cancel($timer);
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
     * Flush any timer and IO events.
     *
     * @param boolean $blocking True if loop should block waiting for next event.
     */
    protected function tickLogic($blocking)
    {
        $this->timers->tick();

        $this->waitForStreamActivity($blocking);
    }

    /**
     * Check if the loop has any pending timers or streams.
     *
     * @return boolean
     */
    protected function isEmpty()
    {
        return $this->timers->isEmpty()
            && 0 === count($this->readStreams)
            && 0 === count($this->writeStreams);
    }

    protected function waitForStreamActivity($blocking)
    {
        // The $blocking flag takes precedence ...
        if (!$blocking) {
            $timeout = 0;

        // There is a pending timer, only block until it is due ...
        } elseif ($scheduledAt = $this->timers->getFirst()) {
            $timeout = max(0, $scheduledAt - $this->timers->getTime());

        // The only possible event is stream activity, so wait forever ...
        } elseif ($this->readStreams || $this->writeStreams) {
            $timeout = null;

        // There's nothing left to do ...
        } else {
            return;
        }

        $read  = $this->readStreams;
        $write = $this->writeStreams;

        $this->streamSelect($read, $write, $timeout);

        // Invoke callbacks for read-ready streams ...
        foreach ($read as $stream) {
            $key = (int) $stream;

            if (array_key_exists($key, $this->readListeners)) {
                call_user_func($this->readListeners[$key], $stream, $this);
            }
        }

        // Invoke callbacks for write-ready streams ...
        foreach ($write as $stream) {
            $key = (int) $stream;

            if (array_key_exists($key, $this->writeListeners)) {
                call_user_func($this->writeListeners[$key], $stream, $this);
            }
        }
    }

    /**
     * Emulate a stream_select() implementation that does not break when passed
     * empty stream arrays.
     *
     * @param array        &$read   An array of read streams to select upon.
     * @param array        &$write  An array of write streams to select upon.
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
}
