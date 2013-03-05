<?php

namespace React\EventLoop;

class LibUvLoop implements LoopInterface
{
    public $loop;
    private $events = array();
    private $timers = array();
    private $suspended = false;

    public function __construct()
    {
        $this->loop = \uv_loop_new();
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
        \uv_poll_stop($this->events[(int) $stream]);
        unset($this->events[(int) $stream]);
    }

    public function removeWriteStream($stream)
    {
        \uv_poll_stop($this->events[(int) $stream]);
        unset($this->events[(int) $stream]);
    }

    public function removeStream($stream)
    {
        if (isset($this->events[(int) $stream])) {
            \uv_poll_stop($this->events[(int) $stream]);
        }
    }

    private function addStream($stream, $listener, $flags)
    {
        if (get_resource_type($stream) == "Unknown") {
            error_log("Unknown resource handle passed. something wrong");
            var_dump(debug_backtrace());

            return false;
        }
        $listener = $this->wrapStreamListener($stream, $listener, $flags);

        if (!isset($this->events[(int) $stream])) {
            $event = \uv_poll_init($this->loop, $stream);
            $this->events[(int) $stream] = $event;
        } else {
            $event = $this->events[(int) $stream];
        }
        \uv_poll_start($event, $flags, $listener);
    }

    private function wrapStreamListener($stream, $listener, $flags)
    {
        if (($flags & \UV::READABLE) === $flags) {
            $removeCallback = array($this, 'removeReadStream');
        } elseif (($flags & \UV::WRITABLE) === $flags) {
            $removeCallback = array($this, 'removeWriteStream');
        }

        return function ($poll, $status, $event, $stream) use ($listener, $removeCallback) {
            if ($status < 0) {
                call_user_func($removeCallback, $stream);

                return;
            }

            call_user_func($listener, $stream);
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
        \uv_timer_stop($this->timers[$signature]);
        unset($this->timers[$signature]);
    }

    private function createTimer($interval, $callback, $periodic)
    {
        $timer = \uv_timer_init($this->loop);
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
                \uv_timer_stop($timer);
            }
        };
    }

    public function tick()
    {
        \uv_run_once($this->loop);
    }

    public function run()
    {
        // @codeCoverageIgnoreStart
       if ($this->suspended == true) {
           return;
       }
       while (\uv_run_once($this->loop)) {
           if ($this->suspended == true) {
               return;
           }
       }
        // @codeCoverageIgnoreEnd
    }

    public function resume()
    {
        $this->suspended = false;
        $this->run();
    }

    public function stop()
    {
        // @codeCoverageIgnoreStart
        $this->suspended = true;
        // @codeCoverageIgnoreEnd
    }
}
