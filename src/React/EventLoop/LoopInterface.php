<?php

namespace React\EventLoop;

use React\EventLoop\Timer\TimerInterface;

interface LoopInterface
{
    public function onReadable($stream, callable $listener);
    public function enableRead($stream);
    public function disableRead($stream);

    public function onWritable($stream, callable $listener);
    public function enableWrite($stream);
    public function disableWrite($stream);

    public function remove($stream);

    public function addTimer($interval, callable $callback);
    public function addPeriodicTimer($interval, callable $callback);
    public function cancelTimer(TimerInterface $timer);
    public function isTimerActive(TimerInterface $timer);

    public function tick();
    public function run();
    public function stop();
}
