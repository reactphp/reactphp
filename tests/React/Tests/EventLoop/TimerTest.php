<?php

namespace React\Tests\EventLoop;

use React\Tests\Socket\TestCase;
use React\EventLoop\StreamSelectLoop;
use React\EventLoop\Timer\Timers;

class TimerTest extends TestCase
{
    private function createLoop()
    {
        return new StreamSelectLoop();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\EventLoop\StreamSelectLoop::addTimer
     * @covers React\EventLoop\Timer\Timers
     */
    public function testAddTimer()
    {
        // usleep is intentionally high

        $loop = $this->createLoop();

        $loop->addTimer(0.001, $this->expectCallableOnce());
        usleep(1000);
        $loop->tick();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\EventLoop\StreamSelectLoop::addTimer
     * @covers React\EventLoop\Timer\Timers
     */
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

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\EventLoop\StreamSelectLoop::addTimer
     * @covers React\EventLoop\Timer\Timers
     */
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

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\EventLoop\StreamSelectLoop::addTimer
     * @covers React\EventLoop\Timer\Timers
     */
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
}
