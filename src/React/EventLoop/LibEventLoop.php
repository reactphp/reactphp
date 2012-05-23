<?php

namespace React\EventLoop;

class LibEventLoop implements LoopInterface
{
    const MIN_TIMER_RESOLUTION = 0.001;

    private $base;
    private $callback;

    private $timers = array();
    private $timersGc = array();

    private $events = array();
    private $flags = array();
    private $readCallbacks = array();
    private $writeCallbacks = array();

    public function __construct()
    {
        $this->base = event_base_new();
        $this->callback = $this->createLibeventCallback();
    }

    protected function createLibeventCallback()
    {
        $timersGc = &$this->timersGc;
        $readCallbacks = &$this->readCallbacks;
        $writeCallbacks = &$this->writeCallbacks;

        return function ($stream, $flags, $loop) use (&$timersGc, &$readCallbacks, &$writeCallbacks) {
            $id = (int) $stream;

            if ($timersGc) {
                foreach ($timersGc as $signature => $resource) {
                   event_free($resource);
                   unset($timersGc[$signature]);
                }
            }

            try {
                if (($flags & EV_READ) === EV_READ && isset($readCallbacks[$id])) {
                    if (call_user_func($readCallbacks[$id], $stream, $loop) === false) {
                        $loop->removeReadStream($stream);
                    }
                }

                if (($flags & EV_WRITE) === EV_WRITE && isset($writeCallbacks[$id])) {
                    if (call_user_func($writeCallbacks[$id], $stream, $loop) === false) {
                        $loop->removeWriteStream($stream);
                    }
                }
            } catch (\Exception $ex) {
                // If one of the callbacks throws an exception we must remove the stream
                // otherwise libevent will swallow the exception and go berserk.
                $loop->removeStream($stream);

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

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The callback must be a callable object.');
        }

        $timer = (object) array(
            'loop' => $this,
            'resource' => $resource = event_new(),
            'callback' => $callback,
            'interval' => $interval * 1000000,
            'periodic' => $periodic,
        );

        $timer->signature = spl_object_hash($timer);

        $callback = function () use ($timer) {
            call_user_func($timer->callback, $timer->signature, $timer->loop);

            if ($timer->periodic === true) {
                event_add($timer->resource, $timer->interval);
            }
        };

        event_timer_set($resource, $callback);
        event_base_set($resource, $this->base);
        event_add($resource, $interval * 1000000);

        $this->timers[$timer->signature] = $timer;

        return $timer->signature;
    }

    public function addTimer($interval, $callback)
    {
        return $this->addTimerInternal($interval, $callback);
    }

    public function addPeriodicTimer($interval, $callback)
    {
        return $this->addTimerInternal($interval, $callback, true);
    }

    public function cancelTimer($signature)
    {
        if (isset($this->timers[$signature])) {
            event_del($resource = $this->timers[$signature]->resource);
            $this->timersGc[$signature] = $resource;
            unset($this->timers[$signature]);
        }
    }

    public function tick()
    {
        event_base_loop($this->base, EVLOOP_ONCE | EVLOOP_NONBLOCK);
    }

    public function run()
    {
        // @codeCoverageIgnoreStart
        event_base_loop($this->base);
        // @codeCoverageIgnoreEnd
    }

    public function stop()
    {
        // @codeCoverageIgnoreStart
        event_base_loopexit($this->base);
        // @codeCoverageIgnoreEnd
    }
}
