<?php

namespace React\Tests\EventLoop;

use React\EventLoop\StreamSelectNextTickLoop;

class StreamSelectNextTickLoopTest extends AbstractLoopTest
{
    use NextTickTestTrait;

    public function createLoop()
    {
        return new StreamSelectNextTickLoop;
    }

    public function testStreamSelectTimeoutEmulation()
    {
        $this->loop->addTimer(
            0.05,
            $this->expectCallableOnce()
        );

        $start = microtime(true);

        $this->loop->run();

        $end = microtime(true);
        $interval = $end - $start;

        $this->assertGreaterThan(0.04, $interval);
    }
}
