<?php

namespace React\Tests\Stream;

use React\Stream\ReadableStream;

class ReadableStreamTest extends TestCase
{
    /** @test */
    public function itShouldBeReadableByDefault()
    {
        $readable = new ReadableStream();
        $this->assertTrue($readable->isReadable());
    }

    /** @test */
    public function pauseShouldDoNothing()
    {
        $readable = new ReadableStream();
        $readable->pause();
    }

    /** @test */
    public function resumeShouldDoNothing()
    {
        $readable = new ReadableStream();
        $readable->resume();
    }

    /** @test */
    public function closeShouldClose()
    {
        $readable = new ReadableStream();
        $readable->close();

        $this->assertFalse($readable->isReadable());
    }

    /** @test */
    public function doubleCloseShouldWork()
    {
        $readable = new ReadableStream();
        $readable->close();
        $readable->close();

        $this->assertFalse($readable->isReadable());
    }
}
