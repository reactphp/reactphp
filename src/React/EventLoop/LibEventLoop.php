<?php

namespace React\EventLoop;

use SplObjectStorage;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;

class LibEventLoop implements LoopInterface
{
    const MIN_TIMER_RESOLUTION = 0.001;

    private $base;
    private $callback;
    private $timers;

    private $events = array();
    private $flags = array();
    private $readCallbacks = array();
    private $writeCallbacks = array();

    public function __construct()
    {
        $this->base = event_base_new();
        $this->callback = $this->createLibeventCallback();
        $this->timers = new SplObjectStorage();
    }

    protected function createLibeventCallback()
    {
        $readCallbacks = &$this->readCallbacks;
        $writeCallbacks = &$this->writeCallbacks;

        return function ($stream, $flags, $loop) use (&$readCallbacks, &$writeCallbacks) {
            $id = (int) $stream;

            try {
                if (($flags & EV_READ) === EV_READ && isset($readCallbacks[$id])) {
                    call_user_func($readCallbacks[$id], $stream, $loop);
                }

                if (($flags & EV_WRITE) === EV_WRITE && isset($writeCallbacks[$id])) {
                    call_user_func($writeCallbacks[$id], $stream, $loop);
                }
            } catch (\Exception $ex) {
                // If one of the callbacks throws an exception we must stop the loop
                // otherwise libevent will swallow the exception and go berserk.
                $loop->stop();

                throw $ex;
            }
        };
    }

    public function addReadStream($stream, $listener)
    {
        $this->addStreamEvent($stream, EV_READ, 'read', $listener);
    }

    public function addWriteStream($stream, $listener)
    {
        $this->addStreamEvent($stream, EV_WRITE, 'write', $listener);
    }

    protected function addStreamEvent($stream, $eventClass, $type, $listener)
    {
        $id = (int) $stream;

        if ($existing = isset($this->events[$id])) {
            if (($this->flags[$id] & $eventClass) === $eventClass) {
                return;
            }
            $event = $this->events[$id];
            event_del($event);
        } else {
            $event = event_new();
        }

        $flags = isset($this->flags[$id]) ? $this->flags[$id] | $eventClass : $eventClass;
        event_set($event, $stream, $flags | EV_PERSIST, $this->callback, $this);

        if (!$existing) {
            // Set the base only if $event has been newly created or be ready for segfaults.
            event_base_set($event, $this->base);
        }

        event_add($event);

        $this->events[$id] = $event;
        $this->flags[$id] = $flags;
        $this->{"{$type}Callbacks"}[$id] = $listener;
    }

    public function removeReadStream($stream)
    {
        $this->removeStreamEvent($stream, EV_READ, 'read');
    }

    public function removeWriteStream($stream)
    {
        $this->removeStreamEvent($stream, EV_WRITE, 'write');
    }

    protected function removeStreamEvent($stream, $eventClass, $type)
    {
        $id = (int) $stream;

        if (isset($this->events[$id])) {
            $flags = $this->flags[$id] & ~$eventClass;

            if ($flags === 0) {
                // Remove if stream is not subscribed to any event at this point.
                return $this->removeStream($stream);
            }

            $event = $this->events[$id];

            event_del($event);
            event_free($event);
            unset($this->{"{$type}Callbacks"}[$id]);

            $event = event_new();
            event_set($event, $stream, $flags | EV_PERSIST, $this->callback, $this);
            event_base_set($event, $this->base);
            event_add($event);

            $this->events[$id] = $event;
            $this->flags[$id] = $flags;
        }
    }

    public function removeStream($stream)
    {
        $id = (int) $stream;

        if (isset($this->events[$id])) {
            $event = $this->events[$id];

            unset(
                $this->events[$id],
                $this->flags[$id],
                $this->readCallbacks[$id],
                $this->writeCallbacks[$id]
            );

            event_del($event);
            event_free($event);
        }
    }

    protected function addTimerInternal($interval, $callback, $periodic = false)
    {
        if ($interval < self::MIN_TIMER_RESOLUTION) {
            throw new \InvalidArgumentException('Timer events do not support sub-millisecond timeouts.');
        }

        $timer = new Timer($this, $interval, $callback, $periodic);
        $resource = event_new();

        $timers = $this->timers;
        $timers->attach($timer, $resource);

        $callback = function () use ($timers, $timer, &$callback) {
            if (isset($timers[$timer])) {
                call_user_func($timer->getCallback(), $timer);

                if ($timer->isPeriodic() && isset($timers[$timer])) {
                    event_add($timers[$timer], $timer->getInterval() * 1000000);
                } else {
                    $timer->cancel();
                }
            }
        };

        event_timer_set($resource, $callback);
        event_base_set($resource, $this->base);
        event_add($resource, $interval * 1000000);

        return $timer;
    }

    public function addTimer($interval, $callback)
    {
        return $this->addTimerInternal($interval, $callback);
    }

    public function addPeriodicTimer($interval, $callback)
    {
        return $this->addTimerInternal($interval, $callback, true);
    }

    public function cancelTimer(TimerInterface $timer)
    {
        if (isset($this->timers[$timer])) {
            $resource = $this->timers[$timer];
            event_del($resource);
            event_free($resource);

            $this->timers->detach($timer);
        }
    }

    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    public function tick()
    {
        event_base_loop($this->base, EVLOOP_ONCE | EVLOOP_NONBLOCK);
    }

    public function run()
    {
        event_base_loop($this->base);
    }

    public function stop()
    {
        event_base_loopexit($this->base);
    }
}
