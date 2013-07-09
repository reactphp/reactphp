<?php

namespace React\Tests\EventLoop;

use React\Tests\Socket\TestCase;

abstract class AbstractLoopTest extends TestCase
{
    protected $loop;

    public function setUp()
    {
        $this->loop = $this->createLoop();
    }

    abstract public function createLoop();

    public function testAddReadStream()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableExactly(2));

        fwrite($input, "foo\n");
        rewind($input);
        $this->loop->tick();

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testAddWriteStream()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addWriteStream($input, $this->expectCallableExactly(2));
        $this->loop->tick();
        $this->loop->tick();
    }

    public function testRemoveReadStreamInstantly()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableNever());
        $this->loop->removeReadStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testRemoveReadStreamAfterReading()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableOnce());

        fwrite($input, "foo\n");
        rewind($input);
        $this->loop->tick();

        $this->loop->removeReadStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testRemoveWriteStreamInstantly()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addWriteStream($input, $this->expectCallableNever());
        $this->loop->removeWriteStream($input);
        $this->loop->tick();
    }

    public function testRemoveWriteStreamAfterWriting()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addWriteStream($input, $this->expectCallableOnce());
        $this->loop->tick();

        $this->loop->removeWriteStream($input);
        $this->loop->tick();
    }

    public function testRemoveStreamInstantly()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableNever());
        $this->loop->addWriteStream($input, $this->expectCallableNever());
        $this->loop->removeStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testRemoveStream()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableOnce());
        $this->loop->addWriteStream($input, $this->expectCallableOnce());

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();

        $this->loop->removeStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
    }

    public function testRemoveInvalid()
    {
        $stream = fopen('php://temp', 'r+');

        // remove a valid stream from the event loop that was never added in the first place
        $this->loop->removeReadStream($stream);
        $this->loop->removeWriteStream($stream);
        $this->loop->removeStream($stream);
    }

    /** @test */
    public function emptyRunShouldSimplyReturn()
    {
        $this->assertRunFasterThan(0.005);
    }

    /** @test */
    public function runShouldReturnWhenNoMoreFds()
    {
        $input = fopen('php://temp', 'r+');

        $loop = $this->loop;
        $this->loop->addReadStream($input, function ($stream) use ($loop) {
            $loop->removeStream($stream);
        });

        fwrite($input, "foo\n");
        rewind($input);

        $this->assertRunFasterThan(0.005);
    }

    /** @test */
    public function stopShouldStopRunningLoop()
    {
        $input = fopen('php://temp', 'r+');

        $loop = $this->loop;
        $this->loop->addReadStream($input, function ($stream) use ($loop) {
            $loop->stop();
        });

        fwrite($input, "foo\n");
        rewind($input);

        $this->assertRunFasterThan(0.005);
    }

    public function testIgnoreRemovedCallback()
    {
        // two independent streams, both should be readable right away
        $stream1 = fopen('php://temp', 'r+');
        $stream2 = fopen('php://temp', 'r+');

        $loop = $this->loop;
        $loop->addReadStream($stream1, function ($stream) use ($loop, $stream2) {
            // stream1 is readable, remove stream2 as well => this will invalidate its callback
            $loop->removeReadStream($stream);
            $loop->removeReadStream($stream2);
        });
        $loop->addReadStream($stream2, function ($stream) use ($loop, $stream1) {
            // this callback would have to be called as well, but the first stream already removed us
            $loop->removeReadStream($stream);
            $loop->removeReadStream($stream1);
        });

        fwrite($stream1, "foo\n");
        rewind($stream1);
        fwrite($stream2, "foo\n");
        rewind($stream2);

        $loop->run();
    }

    private function assertRunFasterThan($maxInterval)
    {
        $start = microtime(true);

        $this->loop->run();

        $end = microtime(true);
        $interval = $end - $start;

        $this->assertLessThan($maxInterval, $interval);
    }
}
