<?php

namespace React\EventLoop;

interface LoopInterface
{
    function addReadStream($stream, $listener);
    function addWriteStream($stream, $listener);

    function removeReadStream($stream);
    function removeWriteStream($stream);
    function removeStream($stream);

    function addTimer($interval, $callback);
    function addPeriodicTimer($interval, $callback);
    function cancelTimer($signature);

    function tick();
    function run();
    function stop();
}
