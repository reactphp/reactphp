<?php

namespace React\EventLoop\Timer;

interface TimerInterface
{
    public function getLoop();
    public function getInterval();
    public function getCallback();
    public function setData($data);
    public function getData();
    public function isPeriodic();
    public function isActive();
    public function cancel();
}
