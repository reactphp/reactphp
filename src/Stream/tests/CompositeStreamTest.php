<?php

namespace React\Tests\Stream;

use React\Stream\CompositeStream;
use React\Stream\ReadableStream;
use React\Stream\WritableStream;

/**
 * @covers React\Stream\CompositeStream
 */
class CompositeStreamTest extends TestCase
{
    /** @test */
    public function itShouldForwardWritableCallsToWritableStream()
    {
        $readable = $this->getMock('React\Stream\ReadableStreamInterface');
        $writable = $this->getMock('React\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('write')
            ->with('foo');
        $writable
            ->expects($this->once())
            ->method('isWritable');

        $composite = new CompositeStream($readable, $writable);
        $composite->write('foo');
        $composite->isWritable();
    }

    /** @test */
    public function itShouldForwardReadableCallsToReadableStream()
    {
        $readable = $this->getMock('React\Stream\ReadableStreamInterface');
        $readable
            ->expects($this->once())
            ->method('isReadable');
        $readable
            ->expects($this->once())
            ->method('pause');
        $readable
            ->expects($this->once())
            ->method('resume');
        $writable = $this->getMock('React\Stream\WritableStreamInterface');

        $composite = new CompositeStream($readable, $writable);
        $composite->isReadable();
        $composite->pause();
        $composite->resume();
    }

    /** @test */
    public function endShouldDelegateToWritableWithData()
    {
        $readable = $this->getMock('React\Stream\ReadableStreamInterface');
        $writable = $this->getMock('React\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('end')
            ->with('foo');

        $composite = new CompositeStream($readable, $writable);
        $composite->end('foo');
    }

    /** @test */
    public function closeShouldCloseBothStreams()
    {
        $readable = $this->getMock('React\Stream\ReadableStreamInterface');
        $readable
            ->expects($this->once())
            ->method('close');
        $writable = $this->getMock('React\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('close');

        $composite = new CompositeStream($readable, $writable);
        $composite->close();
    }

    /** @test */
    public function itShouldReceiveForwardedEvents()
    {
        $readable = new ReadableStream();
        $writable = new WritableStream();

        $composite = new CompositeStream($readable, $writable);
        $composite->on('data', $this->expectCallableOnce());
        $composite->on('drain', $this->expectCallableOnce());

        $readable->emit('data', array('foo'));
        $writable->emit('drain');
    }

    /** @test */
    public function itShouldHandlePipingCorrectly()
    {
        $readable = $this->getMock('React\Stream\ReadableStreamInterface');
        $writable = $this->getMock('React\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('write')
            ->with('foo');

        $composite = new CompositeStream($readable, $writable);

        $input = new ReadableStream();
        $input->pipe($composite);
        $input->emit('data', array('foo'));
    }

    /** @test */
    public function itShouldForwardPauseAndResumeUpstreamWhenPipedTo()
    {
        $readable = $this->getMock('React\Stream\ReadableStreamInterface');
        $writable = $this->getMock('React\Stream\WritableStream', array('write'));
        $writable
            ->expects($this->once())
            ->method('write')
            ->will($this->returnValue(false));

        $composite = new CompositeStream($readable, $writable);

        $input = $this->getMock('React\Stream\ReadableStream', array('pause', 'resume'));
        $input
            ->expects($this->once())
            ->method('pause');
        $input
            ->expects($this->once())
            ->method('resume');

        $input->pipe($composite);
        $input->emit('data', array('foo'));
        $writable->emit('drain');
    }

    /** @test */
    public function itShouldForwardPipeCallsToReadableStream()
    {
        $readable = new ReadableStream();
        $writable = $this->getMock('React\Stream\WritableStreamInterface');
        $composite = new CompositeStream($readable, $writable);

        $output = $this->getMock('React\Stream\WritableStreamInterface');
        $output
            ->expects($this->once())
            ->method('write')
            ->with('foo');

        $composite->pipe($output);
        $readable->emit('data', array('foo'));
    }
}
