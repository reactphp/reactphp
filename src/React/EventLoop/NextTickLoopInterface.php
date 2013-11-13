<?php

namespace React\EventLoop;

use React\EventLoop\LoopInterface;

/**
 * An extended event loop that provides the nextTick() operation.
 */
interface NextTickLoopInterface extends LoopInterface
{
    /**
     * Schedule a callback to be invoked on the next tick of the event loop.
     *
     * Callbacks are guaranteed to be executed in the order they are enqueued,
     * before any timer or stream events.
     *
     * @param callable $listner The callback to invoke.
     */
    public function nextTick(callable $listener);
}
