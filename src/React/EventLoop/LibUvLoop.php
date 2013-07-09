<?php

namespace React\EventLoop;

use SplObjectStorage;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;

class LibUvLoop implements LoopInterface
{
    public $loop;
    private $events = array();
    private $timers;
    private $suspended = false;
    private $listeners = array();

    public function __construct()
    {
        $this->loop = uv_loop_new();
        $this->timers = new SplObjectStorage();
    }

    public function addReadStream($stream, $listener)
    {
        $this->addStream($stream, $listener, \UV::READABLE);
    }

    public function addWriteStream($stream, $listener)
    {
        $this->addStream($stream, $listener, \UV::WRITABLE);
    }

    public function removeReadStream($stream)
    {
        if (!isset($this->events[(int) $stream])) {
            return;
        }

        uv_poll_stop($this->events[(int) $stream]);
        unset($this->listeners[(int) $stream]['read']);
        if (!isset($this->listeners[(int) $stream]['read'])
            && !isset($this->listeners[(int) $stream]['write'])) {
            unset($this->events[(int) $stream]);
        }
    }

    public function removeWriteStream($stream)
    {
        if (!isset($this->events[(int) $stream])) {
            return;
        }

        uv_poll_stop($this->events[(int) $stream]);
        unset($this->listeners[(int) $stream]['read']);
        if (!isset($this->listeners[(int) $stream]['read'])
            && !isset($this->listeners[(int) $stream]['write'])) {
            unset($this->events[(int) $stream]);
        }
    }

    public function removeStream($stream)
    {
        if (isset($this->events[(int) $stream])) {
            uv_poll_stop($this->events[(int) $stream]);
            unset($this->listeners[(int) $stream]['read']);
            unset($this->listeners[(int) $stream]['write']);
            unset($this->events[(int) $stream]);
        }
    }

    private function addStream($stream, $listener, $flags)
    {
        if (get_resource_type($stream) == "Unknown") {
            throw new \InvalidArgumentException("Unknown resource handle passed. something wrong");

            return false;
        }

        $currentFlag = 0;
        if (isset($this->listeners[(int) $stream]['read'])) {
            $currentFlag |= \UV::READABLE;
        }
        if (isset($this->listeners[(int) $stream]['write'])) {
            $currentFlag |= \UV::WRITABLE;
        }
        if (($flags & \UV::READABLE) === $flags) {
            $this->listeners[(int) $stream]['read'] = $listener;
        } elseif (($flags & \UV::WRITABLE) === $flags) {
            $this->listeners[(int) $stream]['write'] = $listener;
        }
        if (!isset($this->events[(int) $stream])) {
            $event = uv_poll_init($this->loop, $stream);
            $this->events[(int) $stream] = $event;
        } else {
            $event = $this->events[(int) $stream];
        }
        $listener = $this->createStreamListener();
        uv_poll_start($event, $currentFlag | $flags, $listener);
    }

    private function createStreamListener()
    {
        $loop = $this;

        $callback = function ($poll, $status, $event, $stream) use ($loop, &$callback) {
            if ($status < 0) {
                if (isset($loop->listeners[(int) $stream]['read'])) {
                    call_user_func(array($this, 'removeReadStream'), $stream);
                }
                if (isset($loop->writeListeners[(int) $stream]['write'])) {
                    call_user_func(array($this, 'removeWriteStream'), $stream);
                }

                return;
            }
            if (($event & \UV::READABLE) && isset($loop->listeners[(int) $stream]['read'])) {
                call_user_func($loop->listeners[(int) $stream]['read'], $stream);
            }
            if (($event & \UV::WRITABLE) && isset($loop->listeners[(int) $stream]['write'])) {
                call_user_func($loop->listeners[(int) $stream]['write'], $stream);
            }
        };

        return $callback;
    }

    public function addTimer($interval, $callback)
    {
        return $this->createTimer($interval, $callback, 0);
    }

    public function addPeriodicTimer($interval, $callback)
    {
        return $this->createTimer($interval, $callback, 1);
    }

    public function cancelTimer(TimerInterface $timer)
    {
        uv_timer_stop($this->timers[$timer]);
        uv_unref($this->timers[$timer]);
        $this->timers->detach($timer);
    }

    private function createTimer($interval, $callback, $periodic)
    {
        $timer = new Timer($this, $interval, $callback, $periodic);
        $ressource = uv_timer_init($this->loop);

        $timers = $this->timers;
        $timers->attach($timer, $ressource);

        $callback = $this->wrapTimerCallback($timer, $periodic);
        uv_timer_start($ressource, $interval * 1000, $interval * 1000, $callback);

        return $timer;
    }

    private function wrapTimerCallback($timer, $periodic)
    {
        return function () use ($timer, $periodic) {
            call_user_func($timer->getCallback(), $timer);
            if (!$periodic) {
                $timer->cancel();
            }
        };
    }

    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    public function tick()
    {
        uv_run_once($this->loop);
    }

    public function run()
    {
       if ($this->suspended) {
           return;
       }
       while (uv_run_once($this->loop)) {
           if ($this->suspended) {
               return;
           }
       }
    }

    public function resume()
    {
        $this->suspended = false;
        $this->run();
    }

    public function stop()
    {
        $this->suspended = true;
    }
}
