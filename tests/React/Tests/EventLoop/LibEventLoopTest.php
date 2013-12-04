<?php

namespace React\Tests\EventLoop;

use React\EventLoop\LibEventLoop;

class LibEventLoopTest extends AbstractLoopTest
{
    private $fifoPath;

    public function createLoop()
    {
        if ('Linux' === PHP_OS && !extension_loaded('posix')) {
            $this->markTestSkipped('libevent tests skipped on linux due to linux epoll issues.');
        }

        if (!function_exists('event_base_new')) {
            $this->markTestSkipped('libevent tests skipped because ext-libevent is not installed.');
        }

        return new LibEventLoop();
    }

    public function tearDown()
    {
        if (file_exists($this->fifoPath)) {
            unlink($this->fifoPath);
        }
    }

    public function createStream()
    {
        if ('Linux' !== PHP_OS) {
            return parent::createStream();
        }

        $this->fifoPath = tempnam(sys_get_temp_dir(), 'react-');

        unlink($this->fifoPath);

        // Use a FIFO on linux to get around lack of support for disk-based file
        // descriptors when using the EPOLL back-end.
        posix_mkfifo($this->fifoPath, 0600);

        $stream = fopen($this->fifoPath, 'r+');

        return $stream;
    }

    public function writeToStream($stream, $content)
    {
        if ('Linux' !== PHP_OS) {
            return parent::writeToStream($stream, $content);
        }

        fwrite($stream, $content);
    }
}
