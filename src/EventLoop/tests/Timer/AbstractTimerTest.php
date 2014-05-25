<?php

namespace React\Tests\EventLoop\Timer;

use React\Tests\EventLoop\TestCase;
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

        $timer->cancel();

        usleep(1000);
        $loop->tick();
    }

    public function testAddPeriodicTimerCancelsItself()
    {
        $i = 0;

        $loop = $this->createLoop();

        $loop->addPeriodicTimer(0.001, function ($timer) use (&$i) {
            $i++;

            if ($i == 2) {
                $timer->cancel();
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

    public function testIsTimerActive()
    {
        $loop = $this->createLoop();

        $timer = $loop->addPeriodicTimer(0.001, function () {});

        $this->assertTrue($loop->isTimerActive($timer));

        $timer->cancel();

        $this->assertFalse($loop->isTimerActive($timer));
    }

    public function testMinimumIntervalOneMicrosecond()
    {
        $loop = $this->createLoop();

        $timer = $loop->addTimer(0, function () {});

        $this->assertEquals(0.000001, $timer->getInterval());
    }
}
