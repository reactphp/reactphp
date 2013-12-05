<?php

namespace React\EventLoop\Timer;

use React\EventLoop\LoopInterface;

class Timer implements TimerInterface
{
    const MIN_INTERVAL = 0.000001;

    protected $loop;
    protected $interval;
    protected $callback;
    protected $periodic;
    protected $data;

    public function __construct(LoopInterface $loop, $interval, callable $callback, $periodic = false, $data = null)
    {
        if ($interval < self::MIN_INTERVAL) {
            $interval = self::MIN_INTERVAL;
        }

        $this->loop = $loop;
        $this->interval = (float) $interval;
        $this->callback = $callback;
        $this->periodic = (bool) $periodic;
        $this->data = null;
    }

    public function getLoop()
    {
        return $this->loop;
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function isPeriodic()
    {
        return $this->periodic;
    }

    public function isActive()
    {
        return $this->loop->isTimerActive($this);
    }

    public function cancel()
    {
        $this->loop->cancelTimer($this);
    }
}
