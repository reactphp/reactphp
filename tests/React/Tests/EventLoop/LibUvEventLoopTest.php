<?php

namespace React\Tests\EventLoop;

use React\EventLoop\LibUvLoop;

class LibUvEventLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        if (!function_exists('uv_default_loop')) {
            $this->markTestSkipped('libuv tests skipped because ext-uv is not installed.');
        }

        return new LibUvLoop();
    }

    public function testLibEventConstructor()
    {
        $loop = new LibUvLoop();
    }

    public function testRemoveStream()
    {
        $this->markTestSkipped('testRemoveStream skipped since libuv does not handle read and write streams separately');
    }

    public function testLibUVRemoveStream()
    {
        $input = fopen('php://temp', 'r+');

        $this->loop->addReadStream($input, $this->expectCallableNever());
        $this->loop->addWriteStream($input, $this->expectCallableOnce());

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();

        $this->loop->removeStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $this->loop->tick();
        fclose($input);
    }
}
