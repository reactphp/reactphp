<?php

namespace React\Tests\EventLoop;

use React\EventLoop\ExtEventLoop;

class ExtEventLoopTest extends AbstractLoopTest
{
    use NextTickTestTrait;

    public function createLoop()
    {
        if ('Linux' === PHP_OS) {
            $this->markTestSkipped('libevent tests skipped on linux due to linux epoll issues.');
        }

        if (!extension_loaded('event')) {
            $this->markTestSkipped('ext-event tests skipped because ext-event is not installed.');
        }

        return new ExtEventLoop;
    }

    public function createStream()
    {
        // ext-event (as of 1.8.1) does not yet support in-memory temporary
        // streams. Setting maxmemory:0 and performing a write forces PHP to
        // back this temporary stream with a real file.
        //
        // This problem is mentioned at https://bugs.php.net/bug.php?id=64652&edit=3
        // but remains unresolved (despite that issue being closed).
        $stream = fopen('php://temp/maxmemory:0', 'r+');

        fwrite($stream, 'x');
        ftruncate($stream, 0);

        return $stream;
    }
}
