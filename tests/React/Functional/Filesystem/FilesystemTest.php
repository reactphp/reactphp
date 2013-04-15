<?php

namespace React\Functional\Filesystem;

use React\Functional\FunctionalTestCase;
use React\Filesystem\LibeioFilesystem;

class FilesystemTest extends FunctionalTestCase
{
    /** @test */
    public function mkdirShouldCreateDirectory()
    {
        $loop = $this->getEventLoop();
        $dirname = __DIR__.'/le_dir';
        if (is_dir($dirname)) {
            rmdir($dirname);
        }
        $this->assertFalse(is_dir($dirname));

        $fs = new LibeioFilesystem($loop);
        $fs->mkdir($dirname, 0755)
            ->then(
                $this->getSuccessfulCallbackThatStopsLoop($loop),
                $this->getErroredCallbackThatStopsLoop($loop)
            );

        $loop->run();
        $this->assertTrue(is_dir($dirname));
        rmdir($dirname);
    }

    /** @test */
    public function mkdirOnNonAccessibleDirectoryMustFail()
    {
        $loop = $this->getEventLoop();
        $dirname = __DIR__ . '/le_dir_that_should_not_exist/a_sub_dir';
        if (is_dir($dirname)) {
            rmdir($dirname);
        }
        $this->assertFalse(is_dir($dirname));

        $fs = new LibeioFilesystem($loop);
        $fs->mkdir($dirname, 0755)
            ->then(
                $this->getSuccessfulCallbackThatStopsLoop($loop),
                $this->getErroredCallbackThatStopsLoop($loop)
            );

        $loop->run();
        $this->assertFalse(is_dir($dirname));
    }

    /** @test */
    public function statShouldReturnHashWithInfo()
    {
        $filename = __DIR__.'/../../Fixtures/hello.txt';
        $capturedStatData = null;

        $callback = function ($stat) use (&$capturedStatData) {
            $capturedStatData = $stat;
        };

        $loop = $this->getEventLoop();

        $fs = new LibeioFilesystem($loop);
        $fs->stat($filename)
            ->then(
                $this->getSuccessfulCallbackThatStopsLoop($loop, $callback),
                $this->getErroredCallbackThatStopsLoop($loop)
            );

        $loop->run();

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
        $filename = __DIR__.'/../../Fixtures/hello.txt';
        $fileContents = null;

        $callback = function($data) use (&$fileContents) {
            $fileContents = $data;
        };

        $loop = $this->getEventLoop();

        $fs = new LibeioFilesystem($loop);
        $fs->readFile($filename)
            ->then(
                $this->getSuccessfulCallbackThatStopsLoop($loop, $callback),
                $this->getErroredCallbackThatStopsLoop($loop)
            );

        $loop->run();

        $this->assertSame("hello\n", $fileContents);
    }
}
