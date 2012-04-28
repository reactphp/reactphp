<?php

namespace Igorw\SocketServer\EventLoop;

class LibEventLoop implements LoopInterface
{
    private $base;
    private $events = array();

    public function __construct()
    {
        $this->base = event_base_new();
    }

    public function addReadStream($stream, $listener)
    {
        $event = event_new();
        event_set($event, $stream, EV_READ | EV_PERSIST, $this->createListener($stream, $listener));
        event_base_set($event, $this->base);
        event_add($event);

        $this->events[(int) $stream] = $event;
    }

    public function addWriteStream($stream, $listener)
    {
        $event = event_new();
        event_set($event, $stream, EV_READ | EV_PERSIST, $this->createListener($stream, $listener));
        event_base_set($event, $this->base);
        event_add($event);

        $this->events[(int) $stream] = $event;
    }

    public function removeStream($stream)
    {
        if (isset($this->events[(int) $stream])) {
            $event = $this->events[(int) $stream];
            event_del($event);

            unset($this->events[(int) $stream]);
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

    private function createListener($stream, $listener)
    {
        return function () use ($listener, $stream) {
            call_user_func($listener, $stream);
        };
    }
}
