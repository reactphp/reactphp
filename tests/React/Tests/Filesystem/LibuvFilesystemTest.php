<?php

namespace React\Tests\Filesystem;

use React\Tests\Socket\TestCase;
use React\Filesystem\LibuvFilesystem;
use React\EventLoop;

class LibuvFilesystemTest extends TestCase
{
    private $testdir;
    
    public function setUp()
    {
        $this->testdir =  __DIR__ ."/filesystem-testdir";
        mkdir($this->testdir);
    }

   private function rmdir_recursive($dir) {
    foreach(scandir($dir) as $file) {
        if ('.' === $file || '..' === $file) continue;
        if (is_dir("$dir/$file")) $this->rmdir_recursive("$dir/$file");
        else unlink("$dir/$file");
    }
    rmdir($dir);
    } 
    
    public function tearDown()
    {
        $this->rmdir_recursive($this->testdir);
    }

    public function testThatMkdirCreatesADirectory()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $directory = $this->testdir . '/test-mkdir1';

        $callable = $this->createCallableMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($directory);
        
        $fs
            ->mkdir($directory)
            ->then($callable, $tests->expectCallableNever());
        $loop->run();
    }

    public function testThatMkdirOnAnExistingDirectoryShouldThrowAnException()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $directory = $this->testdir . '/test-mkdir2';

        mkdir($directory);
        
        $fs
            ->mkdir($directory)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
    }

    /**
     * @dataProvider provideFilePermissions
     */
    public function testThatMkdirRespectsPermissions($mode)
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $directory = $this->testdir . '/test-mkdir3';

        $callable = $this->createCallableMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($directory);
        
        $fs
            ->mkdir($directory, $mode)
            ->then($callable, $this->expectCallableNever());
        $loop->run();

        if (file_exists($directory)) {
            rmdir($directory);
        }
    }

    /**
     * @dataProvider provideFilePermissions
     */
    public function testThatOpenRespectsPermissions($mode)
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $path = $this->testdir . '/test-open1';
        
        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT, $mode)
            ->then($this->expectCallableOnce(), $this->expectCallableNever());
        $loop->run();
        $this->assertEquals(decoct($mode), $this->getFileMode($path));
        
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function provideFilePermissions()
    {
        return array(
            array(0755),
            array(0111),
            array(0555),
        );
    }

    private function getFileMode($file)
    {
        return substr(decoct(fileperms($file)), -4);
    }

    public function testThatOpenOpensAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $path = $this->testdir . '/test-open2';

        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT, 0664)
            ->then($tests->expectCallableOnce(), $this->expectCallableNever());
        $loop->run();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testThatOpenDoesNotOpenAnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $path = $this->testdir . '/unexisting-file';

        $fs
            ->open($path, \UV::O_WRONLY, 0664)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
    }

    public function testThatOpenThrowsAnExceptionWhenPermissionsAreSetToNull()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $path = $this->testdir . '/test-file-create';

        touch($path);
        chmod($path, 0000);

        $fs
            ->open($path, \UV::O_WRONLY, 0064)
            ->then($tests->expectCallableNever(),
            $this->expectCallableOnce());
        $loop->run();

        unlink($path);
    }

    public function testThatWriteCanWriteInAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-write1';
        $testbuffer = "testwrite";

        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT)->then(function($result) use ($fs, $testbuffer) {
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), $testbuffer);
    }

    public function testThatWriteCannotWriteInAFileOpenForReadingOnly()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-write2';
        $testbuffer = "testwrite";

        $fs
            ->open($path, \UV::O_RDONLY | \UV::O_CREAT)->then(function($result) use ($fs, $testbuffer) {
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), '');
    }

    public function testThatStatDoesNotWorkOnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-stat1';

        $fs->stat($path)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
    }

    public function testThatReadCanReadFromAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-read1';
        $testbuffer = "testread";

        $buffer = null;

        file_put_contents($path, $testbuffer);
        $fs
            ->open($path)->then(function($result) use ($fs, $testbuffer, &$buffer) {
                $fs->read($result, strlen($testbuffer))->then(function($result) use (&$buffer) {
                    $buffer = $result;
                });
            });
        $loop->run();
        $this->assertEquals($buffer, $testbuffer);
    }

    public function testThatReadfileCanReadFromAFile()
    {
         $loop = new EventLoop\LibUvLoop();
         $fs = new LibuvFilesystem($loop);
         $path = $this->testdir . '/test-readfile1';
         $testbuffer = "test2";

         $buffer = null;

         file_put_contents($path, $testbuffer);
         $fs->readFile($path)
             ->then(function($result) use ($testbuffer, &$buffer) {
                 $buffer= $result;
             }, $this->expectCallableNever());
         $loop->run();
         $this->assertEquals($testbuffer, $buffer);
    }

    public function testThatReadfileCannotReadAnUnexistingFile()
    {
         $loop = new EventLoop\LibUvLoop();
         $fs = new LibuvFilesystem($loop);
         $path = $this->testdir . '/test-readfile2';
         $testbuffer = "test2";

         $buffer = null;

         $fs->readFile($path)
             ->then($this->expectCallableNever(), $this->expectCallableOnce());
         $loop->run();
    }

    public function testThatStatReturnsStatsOfAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-stat2';
        $testbuffer = "test";
        $tests = $this;

        $catchResult = null;

        file_put_contents($path, $testbuffer);
        $fs->stat($path)
            ->then(function($result) use (&$catchResult) {
                $catchResult = $result;
            }, $this->expectCallableNever());

        $loop->run();
        $tests->assertEquals(4, $catchResult['size']);
    }
}