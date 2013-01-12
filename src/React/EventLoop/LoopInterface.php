<?php

namespace React\EventLoop;

use React\EventLoop\Timer\TimerInterface;

interface LoopInterface
{
    public function addReadStream($stream, $listener);
    public function addWriteStream($stream, $listener);

    public function removeReadStream($stream);
    public function removeWriteStream($stream);
    public function removeStream($stream);

    public function addTimer($interval, $callback);
    public function addPeriodicTimer($interval, $callback);
    public function cancelTimer(TimerInterface $timer);
    public function isTimerActive(TimerInterface $timer);

    public function tick();
    public function run();
    public function stop();
}
