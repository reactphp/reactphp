<?php

namespace React\EventLoop;

use React\EventLoop\NextTick\NextTickQueue;
use React\EventLoop\Timer\Timer;
use SplQueue;

/**
 * Common functionality for event loops that implement NextTickLoopInterface.
 */
abstract class AbstractNextTickLoop implements NextTickLoopInterface
{
    private $nextTickQueue;
    private $explicitlyStopped;

    public function __construct()
    {
        $this->nextTickQueue = new NextTickQueue($this);
        $this->explicitlyStopped = false;
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
     */
    public function tick()
    {
        $this->tickLogic(false);
    }

    /**
     * Run the event loop until there are no more tasks to perform.
     */
    public function run()
    {
        $this->explicitlyStopped = false;

        while ($this->isRunning()) {
            $this->tickLogic(true);
        }
    }

    /**
     * Instruct a running event loop to stop.
     */
    public function stop()
    {
        $this->explicitlyStopped = true;
    }

    /**
     * Check if there is any pending work to do.
     *
     * @return boolean
     */
    protected function isRunning()
    {
        // The loop has been explicitly stopped and should exit ...
        if ($this->explicitlyStopped) {
            return false;

        // The next tick queue has items on it ...
        } elseif (!$this->nextTickQueue->isEmpty()) {
            return true;
        }

        return !$this->isEmpty();
    }

    /**
     * Perform the low-level tick logic.
     */
    protected function tickLogic($blocking)
    {
        $this->nextTickQueue->tick();

        $this->flushEvents(
            $blocking && $this->nextTickQueue->isEmpty()
        );
    }

    /**
     * Flush any timer and IO events.
     *
     * @param boolean $blocking True if loop should block waiting for next event.
     */
    abstract protected function flushEvents($blocking);

    /**
     * Check if the loop has any pending timers or streams.
     *
     * @return boolean
     */
    abstract protected function isEmpty();
}
