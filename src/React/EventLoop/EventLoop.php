<?php

namespace React\EventLoop;

use SplObjectStorage;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;

class EventLoop implements LoopInterface
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
        $this->base = new \EventBase();
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
                if (($flags & \Event::READ) === \Event::READ && isset($readCallbacks[$id])) {
                    call_user_func($readCallbacks[$id], $stream, $loop);
                }

                if (($flags & \Event::WRITE) === \Event::WRITE && isset($writeCallbacks[$id])) {
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
        $this->addStreamEvent($stream, \Event::READ, 'read', $listener);
    }

    public function addWriteStream($stream, $listener)
    {
        $this->addStreamEvent($stream, \Event::WRITE, 'write', $listener);
    }

    protected function addStreamEvent($stream, $eventClass, $type, $listener)
    {
         $id = (int) $stream;

        if (($existing = isset($this->events[$id]))) {
            if (($this->flags[$id] & $eventClass) === $eventClass) {
                return;
            }
            $event = $this->events[$id];
            $event->del();
        } else {
            $event = new \Event($this->base, $stream, \Event::PERSIST, $this->callback, $this);
        }

        $flags = isset($this->flags[$id]) ? $this->flags[$id] | $eventClass : $eventClass;
        $event->set($this->base, $stream, $flags | \Event::PERSIST, $this->callback, $this);

        $event->add();

        $this->events[$id] = $event;
        $this->flags[$id] = $flags;
        $this->{"{$type}Callbacks"}[$id] = $listener;
    }

    public function removeReadStream($stream)
    {
        $this->removeStreamEvent($stream, \Event::READ, 'read');
    }

    public function removeWriteStream($stream)
    {
        $this->removeStreamEvent($stream, \Event::WRITE, 'write');
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

            $event->del();
            $event->free();
            unset($this->{"{$type}Callbacks"}[$id]);

            $event = new \Event($this->base, $stream, $flags | \Event::PERSIST, $this->callback, $this);
            $event->add();

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

            $event->del();
            $event->free();
        }
    }

    protected function addTimerInternal($interval, $callback, $periodic = false)
    {
        if ($interval < self::MIN_TIMER_RESOLUTION) {
            throw new \InvalidArgumentException('Timer events do not support sub-millisecond timeouts.');
        }

        $timer = new Timer($this, $interval, $callback, $periodic);
        if ($periodic == true)
            $flags = \Event::PERSIST;
        $resource = new \Event($this->base, -1, $flags |= \Event::TIMEOUT, $callback);
        $resource->add($interval);
        
        var_dump($resource);
        
        $timers = $this->timers;
        $timers->attach($timer, $resource);
        
        $callback = function () use ($timers, $timer, &$callback) {
            if (isset($timers[$timer])) {
                call_user_func($timer->getCallback(), $timer);

                if ($timer->isPeriodic() && isset($timers[$timer])) {
                    $timers[$timer]->add($timer->getInterval());
                } else {
                    $timer->cancel();
                }
            }
        };



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
            $resource->del();
            $resource->free();

            $this->timers->detach($timer);
        }
    }

    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    public function tick()
    {
        $this->base->loop(\EventBase::LOOP_ONCE | \EventBase::LOOP_NONBLOCK);
    }

    public function run()
    {
        $this->base->loop();
    }

    public function stop()
    {
        $this->base->stop();
    }
}
