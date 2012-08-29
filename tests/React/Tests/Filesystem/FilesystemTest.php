<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Filesystem;
use React\Tests\Socket\TestCase;

class FilesystemTest extends TestCase
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
        $dirname = __DIR__.'/Fixtures/le_dir';
        if (is_dir($dirname)) {
            rmdir($dirname);
        }
        $this->assertFalse(is_dir($dirname));

        $callback = $this->createCallableMock();
        $callback
            ->expects($this->once())
            ->method('__invoke')
            ->with(null, 0);

        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $fs = new Filesystem($loop);
        $fs->mkdir($dirname, 0755, $callback);

        $this->handleReadEvents($fs, 1);

        $this->assertTrue(is_dir($dirname));
        rmdir($dirname);
    }

    /** @test */
    public function openReadAndCloseShouldGetFileContents()
    {
        $filename = __DIR__.'/Fixtures/hello.txt';

        $fileContents = null;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $fs = new Filesystem($loop);
        $fs->open($filename, 'w+', 0644, function ($err, $fd) use ($fs, &$fileContents) {
            $fs->read($fd, 6, 0, function ($err, $data) use ($fs, &$fileContents, $fd) {
                $fileContents = $data;
                $fs->close($fd);
            });
        });

        $this->handleReadEvents($fs, 3);

        $this->assertSame("hello\n", $fileContents);
    }

    /** @test */
    public function statShouldReturnHashWithInfo()
    {
        $filename = __DIR__.'/Fixtures/hello.txt';

        $capturedStatData = null;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $fs = new Filesystem($loop);
        $fs->stat($filename, function ($err, $stat) use (&$capturedStatData) {
            $capturedStatData = $stat;
        });

        $this->handleReadEvents($fs, 1);

        $this->assertNotNull($capturedStatData);
        $this->assertSame(6, $capturedStatData['size']);
        $this->assertNotNull($capturedStatData['atime']);
        $this->assertNotNull($capturedStatData['mtime']);
        $this->assertNotNull($capturedStatData['ctime']);
        $this->assertNotNull($capturedStatData['mode']);
    }

    /** @test */
    public function readFileShouldReadEntireFile()
    {
        $filename = __DIR__.'/Fixtures/hello.txt';

        $fileContents = null;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->atLeastOnce())
            ->method('addReadStream');

        $fs = new Filesystem($loop);
        $fs->readFile($filename, function ($err, $data) use ($fs, &$fileContents) {
            $fileContents = $data;
        });

        $this->handleReadEvents($fs, 4);

        $this->assertSame("hello\n", $fileContents);
    }

    public function handleReadEvents(Filesystem $fs, $count)
    {
        foreach (range(1, $count) as $i) {
            usleep(5000);
            $fs->handleReadEvent();
        }
    }
}
