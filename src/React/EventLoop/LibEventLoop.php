<?php

namespace React\EventLoop;

class LibEventLoop implements LoopInterface
{
    private $base;
    private $callback;

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
        $readCbks = &$this->readCallbacks;
        $writeCbks = &$this->writeCallbacks;

        return function($stream, $flags, $loop) use(&$readCbks, &$writeCbks) {
            $id = (int) $stream;

            try {
                if (($flags & EV_READ) === EV_READ && isset($readCbks[$id])) {
                    if (call_user_func($readCbks[$id], $stream, $loop) === false) {
                        $loop->removeReadStream($stream);
                    }
                }

                if (($flags & EV_WRITE) === EV_WRITE && isset($writeCbks[$id])) {
                    if (call_user_func($writeCbks[$id], $stream, $loop) === false) {
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

    protected function addStreamEvent($stream, $eventClass, $eventCallbacks, $listener)
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
        $this->{"{$eventCallbacks}Callbacks"}[$id] = $listener;
    }

    public function removeReadStream($stream)
    {
        $this->removeStreamEvent($stream, EV_READ, 'read');
    }

    public function removeWriteStream($stream)
    {
        $this->removeStreamEvent($stream, EV_WRITE, 'write');
    }

    protected function removeStreamEvent($stream, $eventClass, $eventCallbacks)
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
            event_set($event, $stream, $flags | EV_PERSIST, $this->callback, $this);
            event_add($event);

            $this->flags[$id] = $flags;
            unset($this->{"{$eventCallbacks}Callbacks"}[$id]);
        }
    }

    public function removeStream($stream)
    {
        $id = (int) $stream;

        if (isset($this->events[$id])) {
            $event = $this->events[$id];

            event_del($event);
            event_free($event);

            unset(
                $this->events[$id],
                $this->flags[$id],
                $this->readCallbacks[$id],
                $this->writeCallbacks[$id]
            );
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
}
