<?php

namespace React\EventLoop;

/**
 * @see https://github.com/m4rw3r/php-libev
 * @see https://gist.github.com/1688204
 */
class LibEvLoop implements LoopInterface
{
    private $loop;
    private $readEvents = array();
    private $writeEvents = array();
    private $timers = array();
    private $suspended = false;

    public function __construct()
    {
        $this->loop = new \libev\EventLoop();
    }

    public function addReadStream($stream, $listener)
    {
        $this->addStream($stream, $listener, \libev\IOEvent::READ);
    }

    public function addWriteStream($stream, $listener)
    {
        $this->addStream($stream, $listener, \libev\IOEvent::WRITE);
    }

    public function removeReadStream($stream)
    {
        $this->readEvents[(int)$stream]->stop();
        unset($this->readEvents[(int)$stream]);
    }

    public function removeWriteStream($stream)
    {
        $this->writeEvents[(int)$stream]->stop();
        unset($this->writeEvents[(int)$stream]);
    }

    public function removeStream($stream)
    {
        if (isset($this->readEvents[(int)$stream])) {
            $this->removeReadStream($stream);
        }

        if (isset($this->writeEvents[(int)$stream])) {
            $this->removeWriteStream($stream);
        }
    }

    private function addStream($stream, $listener, $flags)
    {
        $listener = $this->wrapStreamListener($stream, $listener, $flags);
        $event = new \libev\IOEvent($listener, $stream, $flags);
        $this->loop->add($event);

        if (($flags & \libev\IOEvent::READ) === $flags) {
            $this->readEvents[(int)$stream] = $event;
        } elseif (($flags & \libev\IOEvent::WRITE) === $flags) {
            $this->writeEvents[(int)$stream] = $event;
        }
    }

    private function wrapStreamListener($stream, $listener, $flags)
    {
        if (($flags & \libev\IOEvent::READ) === $flags) {
            $removeCallback = array($this, 'removeReadStream');
        } elseif (($flags & \libev\IOEvent::WRITE) === $flags) {
            $removeCallback = array($this, 'removeWriteStream');
        }

        return function ($event) use ($stream, $listener, $removeCallback) {
            if (feof($stream)) {
                call_user_func($removeCallback, $stream);

                return;
            }

            call_user_func($listener, $stream);
        };
    }

    public function addTimer($interval, $callback)
    {
        $dummyCallback = function () {};
        $timer = new \libev\TimerEvent($dummyCallback, $interval);

        return $this->createTimer($timer, $callback, false);
    }

    public function addPeriodicTimer($interval, $callback)
    {
        $dummyCallback = function () {};
        $timer = new \libev\TimerEvent($dummyCallback, $interval, $interval);

        return $this->createTimer($timer, $callback, true);
    }

    public function cancelTimer($signature)
    {
        $this->loop->remove($this->timers[$signature]);
        unset($this->timers[$signature]);
    }

    private function createTimer($timer, $callback, $periodic)
    {
        $signature = spl_object_hash($timer);
        $callback = $this->wrapTimerCallback($signature, $callback, $periodic);
        $timer->setCallback($callback);

        $this->timers[$signature] = $timer;
        $this->loop->add($timer);

        return $signature;
    }

    private function wrapTimerCallback($signature, $callback, $periodic)
    {
        $loop = $this;

        return function ($event) use ($signature, $callback, $periodic, $loop) {
            call_user_func($callback, $signature, $loop);

            if (!$periodic) {
                $loop->cancelTimer($signature);
            }
        };
    }

    public function tick()
    {
        $this->loop->run(\libev\EventLoop::RUN_ONCE);
    }

    public function run()
    {
        // @codeCoverageIgnoreStart
        if ($this->suspended) {
            $this->suspended = false;
            $this->loop->resume();
        } else {
            $this->loop->run();
        }
        // @codeCoverageIgnoreEnd
    }

    public function stop()
    {
        // @codeCoverageIgnoreStart
        $this->loop->suspend();
        $this->suspended = true;
        // @codeCoverageIgnoreEnd
    }
}
