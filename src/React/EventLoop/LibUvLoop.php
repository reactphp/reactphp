<?php

namespace React\EventLoop;

class LibUvLoop implements LoopInterface
{
    public $loop;
    private $events = array();
    private $timers = array();
    private $suspended = false;
    public $listeners = array();

    public function __construct()
    {
        $this->loop = uv_loop_new();
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
        uv_poll_stop($this->events[(int) $stream]);
        unset($this->listeners[(int) $stream]['read']);
        if (!isset($this->listeners[(int) $stream]['read'])
            && !isset($this->listeners[(int) $stream]['write'])) {
            unset($this->events[(int) $stream]);
        }
    }

    public function removeWriteStream($stream)
    {
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
            error_log("Unknown resource handle passed. something wrong");
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
        $listener = $this->wrapStreamListener();
        uv_poll_start($event, $currentFlag | $flags, $listener);
    }

    private function wrapStreamListener()
    {
            
       $loop = $this;
        return function ($poll, $status, $event, $stream) use ($loop) {
            if ($status < 0) {
                if (isset($loop->listeners[(int) $stream]['read'])) {
                    call_user_func(array($this, 'removeReadStream'), $stream);
                }
                if (isset($loop->writeListeners[(int) $stream]['write'])) {
                    call_user_func(array($this, 'removeWriteStream'), $stream);
                }
                return;
            }
            if ($event & \UV::READABLE && isset($loop->listeners[(int) $stream]['read'])) {
                call_user_func($loop->listeners[(int) $stream]['read'], $stream);
            }
            if ($event & \UV::WRITABLE && isset($loop->listeners[(int) $stream]['write'])) {
                call_user_func($loop->listeners[(int) $stream]['write'], $stream);
            }
        };
    }

    public function addTimer($interval, $callback)
    {
        return $this->createTimer($interval, $callback, 0);
    }

    public function addPeriodicTimer($interval, $callback)
    {
        return $this->createTimer($interval, $callback, 1);
    }

    public function cancelTimer($signature)
    {
        uv_timer_stop($this->timers[$signature]);
        unset($this->timers[$signature]);
    }

    private function createTimer($interval, $callback, $periodic)
    {
        $timer = uv_timer_init($this->loop);
        $signature = (int) $timer;
        $callback = $this->wrapTimerCallback($timer, $callback, $periodic);
        uv_timer_start($timer, 0, $interval * 1000, $callback);
        $this->timers[$signature] = $timer;

        return $signature;
    }

    private function wrapTimerCallback($timer, $callback, $periodic)
    {
        $loop = $this;

        return function ($timer, $status) use ($timer, $callback, $periodic, $loop) {
            call_user_func($callback, (int) $timer, $loop);
            if (!$periodic) {
                uv_timer_stop($timer);
            }
        };
    }

    public function tick()
    {
        uv_run_once($this->loop);
    }

    public function run()
    {
       if ($this->suspended === true) {
           return;
       }
       while (uv_run_once($this->loop)) {
           if ($this->suspended === true) {
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
