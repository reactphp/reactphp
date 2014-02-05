<?php

namespace React\EventLoop\Tick;

use React\EventLoop\LoopInterface;

class NextTickQueue
{
    private $eventLoop;
    private $queue;

    /**
     * @param LoopInterface $eventLoop The event loop passed as the first parameter to callbacks.
     */
    public function __construct(LoopInterface $eventLoop)
    {
        $this->eventLoop = $eventLoop;
        $this->queue = [];
    }

    /**
     * Add a callback to be invoked on the next tick of the event loop.
     *
     * Callbacks are guaranteed to be executed in the order they are enqueued,
     * before any timer or stream events.
     *
     * @param callable $listener The callback to invoke.
     */
    public function add(callable $listener)
    {
        $this->queue[] = $listener;
    }

    /**
     * Flush the callback queue.
     */
    public function tick()
    {
        while ($this->queue) {
            $callback = array_shift($this->queue);
            $callback($this->eventLoop);
        }
    }

    /**
     * Check if the next tick queue is empty.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return !$this->queue;
    }
}
