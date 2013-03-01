<?php

namespace React\Tests\EventLoop\Timer;

use React\Tests\Socket\TestCase;
use React\EventLoop\Timer\Timers;

abstract class AbstractTimerTest extends TestCase
{
    abstract public function createLoop();

    public function testAddTimer()
    {
        // usleep is intentionally high

        $loop = $this->createLoop();

        $loop->addTimer(0.001, $this->expectCallableOnce());
        usleep(1000);
        $loop->tick();
    }

    public function testAddPeriodicTimer()
    {
        $loop = $this->createLoop();

        $loop->addPeriodicTimer(0.001, $this->expectCallableExactly(3));
        usleep(1000);
        $loop->tick();
        usleep(1000);
        $loop->tick();
        usleep(1000);
        $loop->tick();
    }

    public function testAddPeriodicTimerWithCancel()
    {
        $loop = $this->createLoop();

        $timer = $loop->addPeriodicTimer(0.001, $this->expectCallableExactly(2));

        usleep(1000);
        $loop->tick();
        usleep(1000);
        $loop->tick();

        $loop->cancelTimer($timer);

        usleep(1000);
        $loop->tick();
    }

    public function testAddPeriodicTimerCancelsItself()
    {
        $i = 0;

        $loop = $this->createLoop();

        $loop->addPeriodicTimer(0.001, function ($timer, $loop) use (&$i) {
            $i++;

            if ($i == 2) {
                $loop->cancelTimer($timer);
            }
        });

        usleep(1000);
        $loop->tick();
        usleep(1000);
        $loop->tick();
        usleep(1000);
        $loop->tick();

        $this->assertSame(2, $i);
    }

    public function testAddNextTickCallback()
    {
        $loop = $this->createLoop();
        $loop->nextTick($this->expectCallableOnce());
        usleep(1000);
        $loop->tick();
        usleep(1000);
        $loop->tick();
        usleep(1000);
        $loop->tick();
    }
}
