<?php

namespace React\EventLoop\Timer;

use React\EventLoop\LoopInterface;

class Timers
{
    const MIN_RESOLUTION = 0.001;

    private $loop;
    private $time;
    private $active = array();
    private $timers;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->timers = new \SplPriorityQueue();
    }

    public function updateTime()
    {
        return $this->time = microtime(true);
    }

    public function getTime()
    {
        return $this->time ?: $this->updateTime();
    }

    public function add($interval, $callback, $periodic = false)
    {
        if ($interval < self::MIN_RESOLUTION) {
            throw new \InvalidArgumentException('Timer events do not support sub-millisecond timeouts.');
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The callback must be a callable object.');
        }

        $interval = (float) $interval;

        $timer = (object) array(
            'interval' => $interval,
            'callback' => $callback,
            'periodic' => $periodic,
            'scheduled' => $interval + $this->getTime(),
        );

        $timer->signature = spl_object_hash($timer);
        $this->timers->insert($timer, -$timer->scheduled);
        $this->active[$timer->signature] = $timer;

        return $timer->signature;
    }

    public function cancel($signature)
    {
        unset($this->active[$signature]);
    }

    public function getFirst()
    {
        if ($this->timers->isEmpty()) {
            return null;
        }

        return $this->timers->top()->scheduled;
    }

    public function isEmpty()
    {
        return !$this->active;
    }

    public function tick()
    {
        $time = $this->updateTime();
        $timers = $this->timers;

        while (!$timers->isEmpty() && $timers->top()->scheduled < $time) {
            $timer = $timers->extract();

            if (isset($this->active[$timer->signature])) {
                call_user_func($timer->callback, $timer->signature, $this->loop);

                if ($timer->periodic === true) {
                    $timer->scheduled = $timer->interval + $time;
                    $timers->insert($timer, -$timer->scheduled);
                } else {
                    unset($this->active[$timer->signature]);
                }
            }
        }
    }
}
