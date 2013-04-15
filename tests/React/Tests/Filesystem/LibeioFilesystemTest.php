<?php

namespace React\Tests\Filesystem;

use React\Filesystem\LibeioFilesystem;
use React\Tests\Socket\TestCase;

class LibeioFilesystemTest extends TestCase
{
    public function setUp()
    {
        eio_init();
    }

    public function tearDown()
    {
        if (eio_nreqs()) {
            $this->fail(sprintf('Still had %s unfinished eio tasks.', eio_nreqs()));
        }
    }

    /** @test */
    public function mkdirShouldCreateDirectory()
    {
        $dirname = __DIR__.'/../../Fixtures/le_dir';
        if (is_dir($dirname)) {
            rmdir($dirname);
        }
        $this->assertFalse(is_dir($dirname));

        $callback = $this->createCallableMock();
        $callback
            ->expects($this->once())
            ->method('__invoke')
            ->with(0);

        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $fs = new LibeioFilesystem($loop);
        $fs->mkdir($dirname, 0755)
            ->then($callback, $this->getNoErrorCallbackTester());

        $this->handleReadEvents($fs, 1);

        $this->assertTrue(is_dir($dirname));
        rmdir($dirname);
    }

    /** @test */
    public function mkdirOnNonAccessibleDirectoryMustFail()
    {
        $dirname = __DIR__ . '/le_dir_that_should_not_exist/a_sub_dir';
        if (is_dir($dirname)) {
            rmdir($dirname);
        }
        $this->assertFalse(is_dir($dirname));

        $successCallback = $this->createCallableMock();
        $successCallback
            ->expects($this->never())
            ->method('__invoke');

        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $fs = new LibeioFilesystem($loop);
        $fs->mkdir($dirname, 0755)
            ->then($successCallback, $this->getErrorCallbackTester());

        $this->handleReadEvents($fs, 1);

        $this->assertFalse(is_dir($dirname));
    }

    /** @test */
    public function openReadAndCloseShouldGetFileContents()
    {
        $filename = __DIR__.'/../../Fixtures/hello.txt';

        $fileContents = $thatFd = null;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $fs = new LibeioFilesystem($loop);
        $fs->open($filename, 'w+', 0644)
            ->then(function ($fd) use ($fs, &$fileContents, &$thatFd) {
                $thatFd = $fd;
                return $fs->read($fd, 6, 0);
            }, $this->getNoErrorCallbackTester())
            ->then(function($data) use ($fs, &$thatFd, &$fileContents) {
                $fileContents = $data;
                $fs->close($thatFd);
            }, $this->getNoErrorCallbackTester());

        $this->handleReadEvents($fs, 3);

        $this->assertSame("hello\n", $fileContents);
    }

    /** @test */
    public function statShouldReturnHashWithInfo()
    {
        $filename = __DIR__.'/../../Fixtures/hello.txt';

        $capturedStatData = null;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $fs = new LibeioFilesystem($loop);
        $fs->stat($filename)
            ->then(function ($stat) use (&$capturedStatData) {
                $capturedStatData = $stat;
            }, $this->getNoErrorCallbackTester());

        $this->handleReadEvents($fs, 1);

        $this->assertNotNull($capturedStatData);
        $this->assertSame(6, $capturedStatData['size']);
        $this->assertNotNull($capturedStatData['atime']);
        $this->assertNotNull($capturedStatData['mtime']);
        $this->assertNotNull($capturedStatData['ctime']);
        $this->assertNotNull($capturedStatData['mode']);
    }

    /** @test */
    public function statOnNonExistentFileShouldFail()
    {
        $filename = __DIR__.'/../../Fixtures/nobody.here';

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $successCallback = $this->createCallableMock();
        $successCallback
            ->expects($this->never())
            ->method('__invoke');

        $fs = new LibeioFilesystem($loop);
        $fs->stat($filename)
            ->then($successCallback, $this->getErrorCallbackTester());

        $this->handleReadEvents($fs, 1);
    }

    /** @test */
    public function readFileShouldReadEntireFile()
    {
        $filename = __DIR__.'/../../Fixtures/hello.txt';

        $fileContents = null;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $fs = new LibeioFilesystem($loop);
        $fs->readFile($filename)
            ->then(function($data) use (&$fileContents) {
                $fileContents = $data;
            }, $this->getNoErrorCallbackTester());

        $this->handleReadEvents($fs, 4);

        $this->assertSame("hello\n", $fileContents);
    }

    /** @test */
    public function readFileOnNonExistentShouldFail()
    {
        $filename = __DIR__.'/../../Fixtures/nobody.here';

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $successCallback = $this->createCallableMock();
        $successCallback
            ->expects($this->never())
            ->method('__invoke');

        $fs = new LibeioFilesystem($loop);
        $fs->readFile($filename)
            ->then($successCallback, $this->getErrorCallbackTester());

        $this->handleReadEvents($fs, 4);
    }

    public function handleReadEvents(LibeioFilesystem $fs, $count)
    {
        foreach (range(1, $count) as $i) {
            usleep(5000);
            $fs->handleReadEvent();
        }
    }

    private function getErrorCallbackTester()
    {
        $errorCallback = $this->createCallableMock();
        $errorCallback
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('React\\Filesystem\\IoException'));

        return $errorCallback;
    }

    private function getNoErrorCallbackTester()
    {
        $errorCallback = $this->createCallableMock();
        $errorCallback
            ->expects($this->never())
            ->method('__invoke');

        return $errorCallback;
    }
}
