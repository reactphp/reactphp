<?php

namespace React\EventLoop\Tick;

use React\EventLoop\LoopInterface;
use SplQueue;

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
        $this->queue = new SplQueue();
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
        $this->queue->enqueue($listener);
    }

    /**
     * Flush the callback queue.
     */
    public function tick()
    {
        while (!$this->queue->isEmpty()) {
            call_user_func(
                $this->queue->dequeue(),
                $this->eventLoop
            );
        }
    }

    /**
     * Check if the next tick queue is empty.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->queue->isEmpty();
    }
}
