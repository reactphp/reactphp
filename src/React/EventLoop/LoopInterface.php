<?php

namespace React\EventLoop;

interface LoopInterface
{
    public function addReadStream($stream, $listener);
    public function addWriteStream($stream, $listener);

    public function removeStream($stream);

    public function tick();
    public function run();
    public function stop();
}
