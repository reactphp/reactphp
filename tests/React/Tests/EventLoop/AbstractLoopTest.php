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

    public function createStream()
    {
        return fopen('php://temp', 'r+');
    }

    public function writeToStream($stream, $content)
    {
        fwrite($stream, $content);
        rewind($stream);
    }

    public function testAddReadStream()
    {
        $input = $this->createStream();

        $this->loop->onReadable($input, $this->expectCallableExactly(2));
        $this->loop->enableRead($input);

        $this->writeToStream($input, "foo\n");
        $this->loop->tick();

        $this->writeToStream($input, "bar\n");
        $this->loop->tick();
    }

    public function testAddWriteStream()
    {
        $input = $this->createStream();

        $this->loop->onWritable($input, $this->expectCallableExactly(2));
        $this->loop->enableWrite($input);
        $this->loop->tick();
        $this->loop->tick();
    }

    public function testDisableReadStreamInstantly()
    {
        $input = $this->createStream();

        $this->loop->onReadable($input, $this->expectCallableNever());
        $this->loop->enableRead($input);
        $this->loop->disableRead($input);

        $this->writeToStream($input, "bar\n");
        $this->loop->tick();
    }

    public function testDisableReadStreamAfterReading()
    {
        $input = $this->createStream();

        $this->loop->onReadable($input, $this->expectCallableOnce());
        $this->loop->enableRead($input);

        $this->writeToStream($input, "foo\n");
        $this->loop->tick();

        $this->loop->disableRead($input);

        $this->writeToStream($input, "bar\n");
        $this->loop->tick();
    }

    public function testDisableWriteStreamInstantly()
    {
        $input = $this->createStream();

        $this->loop->onWritable($input, $this->expectCallableNever());
        $this->loop->enableWrite($input);
        $this->loop->disableWrite($input);
        $this->loop->tick();
    }

    public function testDisableWriteStreamAfterWriting()
    {
        $input = $this->createStream();

        $this->loop->onWritable($input, $this->expectCallableOnce());
        $this->loop->enableWrite($input);
        $this->loop->tick();

        $this->loop->disableWrite($input);
        $this->loop->tick();
    }

    public function testDisableStreamForReadOnly()
    {
        $input = $this->createStream();

        $this->loop->onReadable($input, $this->expectCallableNever());
        $this->loop->enableRead($input);

        $this->loop->onWritable($input, $this->expectCallableOnce());
        $this->loop->enableWrite($input);

        $this->loop->disableRead($input);

        $this->writeToStream($input, "foo\n");
        $this->loop->tick();
    }

    public function testDisableStreamForWriteOnly()
    {
        $input = $this->createStream();

        $this->writeToStream($input, "foo\n");

        $this->loop->onReadable($input, $this->expectCallableOnce());
        $this->loop->enableRead($input);

        $this->loop->onWritable($input, $this->expectCallableNever());
        $this->loop->enableWrite($input);

        $this->loop->disableWrite($input);

        $this->loop->tick();
    }

    public function testRemoveStreamInstantly()
    {
        $input = $this->createStream();

        $this->loop->onReadable($input, $this->expectCallableNever());
        $this->loop->onWritable($input, $this->expectCallableNever());
        $this->loop->remove($input);

        $this->writeToStream($input, "bar\n");
        $this->loop->tick();
    }

    public function testRemoveStreamAfterActivity()
    {
        $input = $this->createStream();

        $this->loop->onReadable($input, $this->expectCallableOnce());
        $this->loop->enableRead($input);

        $this->loop->onWritable($input, $this->expectCallableOnce());
        $this->loop->enableWrite($input);

        $this->writeToStream($input, "bar\n");
        $this->loop->tick();

        $this->loop->remove($input);

        $this->writeToStream($input, "bar\n");
        $this->loop->tick();
    }

    public function testToggleAndRemoveInvalid()
    {
        // Toggle read or write notifications and remove a valid stream from the
        // event loop that was never added in the first place
        $stream = $this->createStream();

        $this->loop->enableWrite($stream);
        $this->loop->enableRead($stream);

        $this->loop->disableWrite($stream);
        $this->loop->disableRead($stream);

        $this->loop->remove($stream);
    }

    /** @test */
    public function emptyRunShouldSimplyReturn()
    {
        $this->assertRunFasterThan(0.005);
    }

    /** @test */
    public function runShouldReturnWhenNoMoreFds()
    {
        $input = $this->createStream();

        $loop = $this->loop;
        $this->loop->onReadable($input, function ($stream, $loop) {
            $loop->remove($stream);
        });

        $this->loop->enableRead($input);
        $this->writeToStream($input, "foo\n");

        $this->assertRunFasterThan(0.005);
    }

    /** @test */
    public function stopShouldStopRunningLoop()
    {
        $input = $this->createStream();

        $this->loop->onReadable($input, function ($stream, $loop) {
            $loop->stop();
        });

        $this->loop->enableRead($input);
        $this->writeToStream($input, "foo\n");

        $this->assertRunFasterThan(0.005);
    }

    public function testIgnoreRemovedCallback()
    {
        // two independent streams, both should be readable right away
        $stream1 = $this->createStream();
        $stream2 = $this->createStream();

        $this->loop->onReadable($stream1, function ($stream, $loop) use ($stream2) {
            // stream1 is readable, remove stream2 as well => this will invalidate its callback
            $loop->remove($stream);
            $loop->remove($stream2);
        });

        // this callback would have to be called as well, but the first stream already removed us
        $this->loop->onReadable($stream2, $this->expectCallableNever());

        $this->loop->enableRead($stream1);
        $this->loop->enableRead($stream2);

        $this->writeToStream($stream1, "foo\n");
        $this->writeToStream($stream2, "foo\n");

        $this->loop->run();
    }

    public function testNextTick()
    {
        $called = false;

        $callback = function ($loop) use (&$called) {
            $this->assertSame($this->loop, $loop);
            $called = true;
        };

        $this->loop->onNextTick($callback);

        $this->assertFalse($called);

        $this->loop->tick();

        $this->assertTrue($called);
    }

    public function testNextTickFiresBeforeIO()
    {
        $stream = $this->createStream();

        $this->loop->onWritable($stream, function () {
                echo 'stream' . PHP_EOL;
        });

        $this->loop->enableWrite($stream);

        $this->loop->onNextTick(function () {
            echo 'next-tick' . PHP_EOL;
        });

        $this->expectOutputString('next-tick' . PHP_EOL . 'stream' . PHP_EOL);

        $this->loop->tick();
    }

    public function testRecursiveNextTick()
    {
        $stream = $this->createStream();

        $this->loop->onWritable($stream, function () {
            echo 'stream' . PHP_EOL;
        });

        $this->loop->enableWrite($stream);

        $this->loop->onNextTick(function () {
            $this->loop->onNextTick(function () {
                echo 'next-tick' . PHP_EOL;
            });
        });

        $this->expectOutputString('next-tick' . PHP_EOL . 'stream' . PHP_EOL);

        $this->loop->tick();
    }

    public function testRunWaitsForNextTickEvents()
    {
        $stream = $this->createStream();

        $this->loop->onWritable($stream, function () use ($stream) {
            $this->loop->remove($stream);
            $this->loop->onNextTick(function () {
                echo 'next-tick' . PHP_EOL;
            });
        });

        $this->loop->enableWrite($stream);
        $this->expectOutputString('next-tick' . PHP_EOL);

        $this->loop->run();
    }

    public function testNextTickEventGeneratedByTimer()
    {
        $this->loop->addTimer(0.001, function () {
            $this->loop->onNextTick(function () {
                echo 'next-tick' . PHP_EOL;
            });
        });

        $this->expectOutputString('next-tick' . PHP_EOL);

        $this->loop->run();
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
