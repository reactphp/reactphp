<?php

namespace React\Tests\Stream;

use React\Stream\BufferedSink;
use React\Stream\ReadableStream;

/**
 * @covers React\Stream\BufferedSink
 */
class BufferedSinkTest extends TestCase
{
    /** @test */
    public function promiseShouldReturnPromise()
    {
        $sink = new BufferedSink();
        $contents = $sink->promise();

        $this->assertInstanceOf('React\Promise\PromiseInterface', $contents);
    }

    /** @test */
    public function endShouldResolvePromiseWithBufferContents()
    {
        $callback = $this->expectCallableOnceWith('foo');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->write('foo');
        $sink->end();
    }

    /** @test */
    public function closeWithEmptyBufferShouldResolveToEmptyString()
    {
        $callback = $this->expectCallableOnceWith('');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->close();
        $sink->close();
    }

    /** @test */
    public function closeTwiceShouldBeFine()
    {
        $callback = $this->expectCallableOnce();

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->close();
        $sink->close();
    }

    /** @test */
    public function resovedValueShouldContainMultipleWrites()
    {
        $callback = $this->expectCallableOnceWith('foobarbaz');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->write('foo');
        $sink->write('bar');
        $sink->write('baz');
        $sink->end();
    }

    /** @test */
    public function dataWrittenOnEndShouldBeBuffered()
    {
        $callback = $this->expectCallableOnceWith('foobar');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->write('foo');
        $sink->end('bar');
    }

    /** @test */
    public function errorsShouldRejectPromise()
    {
        $errback = $this->expectCallableOnceWith($this->callback(function ($e) {
            return $e instanceof \Exception && 'Shit happens' === $e->getMessage();
        }));

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($this->expectCallableNever(), $errback);

        $sink->emit('error', array(new \Exception('Shit happens')));
    }

    /** @test */
    public function writeShouldTriggerProgressOnPromise()
    {
        $callback = $this->createCallableMock();
        $callback
            ->expects($this->at(0))
            ->method('__invoke')
            ->with('foo');
        $callback
            ->expects($this->at(1))
            ->method('__invoke')
            ->with('bar');
        $callback
            ->expects($this->at(2))
            ->method('__invoke')
            ->with('baz');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then(null, null, $callback);

        $sink->write('foo');
        $sink->write('bar');
        $sink->end('baz');
    }

    /** @test */
    public function forwardedErrorsFromPipeShouldRejectPromise()
    {
        $errback = $this->expectCallableOnceWith($this->callback(function ($e) {
            return $e instanceof \Exception && 'Shit happens' === $e->getMessage();
        }));

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($this->expectCallableNever(), $errback);

        $readable = new ReadableStream();
        $readable->pipe($sink);
        $readable->emit('error', array(new \Exception('Shit happens')));
    }

    /** @test */
    public function pipeShouldSucceedAndResolve()
    {
        $callback = $this->expectCallableOnceWith('foobar');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $readable = new ReadableStream();
        $readable->pipe($sink);
        $readable->emit('data', array('foo'));
        $readable->emit('data', array('bar'));
        $readable->close();
    }

    /** @test */
    public function factoryMethodShouldImplicitlyPipeAndPromise()
    {
        $callback = $this->expectCallableOnceWith('foo');

        $readable = new ReadableStream();

        BufferedSink::createPromise($readable)
            ->then($callback);

        $readable->emit('data', array('foo'));
        $readable->close();
    }

    private function expectCallableOnceWith($value)
    {
        $callback = $this->createCallableMock();
        $callback
            ->expects($this->once())
            ->method('__invoke')
            ->with($value);

        return $callback;
    }
}
