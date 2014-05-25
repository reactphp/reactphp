<?php

namespace React\Tests\Stream;

use React\Stream\ReadableStream;
use React\Stream\ThroughStream;

/**
 * @covers React\Stream\ThroughStream
 */
class ThroughStreamTest extends TestCase
{
    /** @test */
    public function itShouldEmitAnyDataWrittenToIt()
    {
        $through = new ThroughStream();
        $through->on('data', $this->expectCallableOnceWith('foo'));
        $through->write('foo');
    }

    /** @test */
    public function pipingStuffIntoItShouldWork()
    {
        $readable = new ReadableStream();

        $through = new ThroughStream();
        $through->on('data', $this->expectCallableOnceWith('foo'));

        $readable->pipe($through);
        $readable->emit('data', array('foo'));
    }

    /** @test */
    public function endShouldCloseTheStream()
    {
        $through = new ThroughStream();
        $through->on('data', $this->expectCallableNever());
        $through->end();

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    /** @test */
    public function endShouldWriteDataBeforeClosing()
    {
        $through = new ThroughStream();
        $through->on('data', $this->expectCallableOnceWith('foo'));
        $through->end('foo');

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    /** @test */
    public function itShouldBeReadableByDefault()
    {
        $through = new ThroughStream();
        $this->assertTrue($through->isReadable());
    }

    /** @test */
    public function itShouldBeWritableByDefault()
    {
        $through = new ThroughStream();
        $this->assertTrue($through->isWritable());
    }

    /** @test */
    public function pauseShouldDelegateToPipeSource()
    {
        $input = $this->getMock('React\Stream\ReadableStream', array('pause'));
        $input
            ->expects($this->once())
            ->method('pause');

        $through = new ThroughStream();
        $input->pipe($through);

        $through->pause();
    }

    /** @test */
    public function resumeShouldDelegateToPipeSource()
    {
        $input = $this->getMock('React\Stream\ReadableStream', array('resume'));
        $input
            ->expects($this->once())
            ->method('resume');

        $through = new ThroughStream();
        $input->pipe($through);

        $through->resume();
    }

    /** @test */
    public function closeShouldClose()
    {
        $through = new ThroughStream();
        $through->close();

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    /** @test */
    public function doubleCloseShouldWork()
    {
        $through = new ThroughStream();
        $through->close();
        $through->close();

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    /** @test */
    public function pipeShouldPipeCorrectly()
    {
        $output = $this->getMock('React\Stream\WritableStreamInterface');
        $output
            ->expects($this->once())
            ->method('write')
            ->with('foo');

        $through = new ThroughStream();
        $through->pipe($output);
        $through->write('foo');
    }

    protected function expectCallableOnceWith($arg)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($arg);

        return $mock;
    }
}
