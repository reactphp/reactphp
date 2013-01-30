<?php

namespace React\Tests\Filesystem;

use React\Tests\Socket\TestCase;
use React\Filesystem\LibuvFilesystem;
use React\EventLoop;

class LibuvFilesystemTest extends TestCase
{
    public function testThatMkdirCreatesADirectory()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        
        $tests = $this;
        $directory = __DIR__ . '/test-mkdir';
        
        if (file_exists($directory)) {
            rmdir($directory);
        }
        
        $catchCreatedDir = null;
        
        $fs
            ->mkdir($directory)
            ->then(function ($createdDir) use (&$catchCreatedDir) {
                $catchCreatedDir = $createdDir;
            }, $tests->expectCallableNever());
        $loop->run();
        $tests->assertEquals($directory, $catchCreatedDir);
        $tests->assertTrue(file_exists($catchCreatedDir));
        $tests->assertTrue(is_dir($catchCreatedDir));
    }
    
    public function testThatMkdirOnAnExistingDirectoryShouldThrowAnException()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        
        $tests = $this;
        $directory = __DIR__ . '/test-mkdir';
        
        if (!file_exists($directory) || !is_dir($directory)) {
            mkdir($directory);
        }
        
        $fs
            ->mkdir($directory)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
    }
    
    /**
     * @dataProvider provideModes
     */
    public function testThatMkdirRespectsPermissions($mode)
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        
        $directory = __DIR__ . '/test-mkdir';
        
        if (file_exists($directory) && !is_dir($directory)) {
            unlink($directory);
        }
        elseif (file_exists($directory)) {
            rmdir($directory);
        }
        
        $catchCreatedDir = null;
        
        $fs
            ->mkdir($directory, $mode)
            ->then(function ($createdDir) use (&$catchCreatedDir) {
                $catchCreatedDir = $createdDir;
            }, $this->expectCallableNever());
        $loop->run();

        $this->assertNotNull($catchCreatedDir);
        $this->assertEquals(decoct($mode), $this->getFileMode($catchCreatedDir));
    }
    
     /**
     * @dataProvider provideModes
     */
    public function testThatOpenRespectsPermissions($mode)
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        
        $path = __DIR__ . '/test-open';
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
        $catchCreatedFile = null;
        
        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT, $mode)
            ->then(function ($result) use (&$catchCreatedFile, $path) {
                if ($result > 0){
                    $catchCreatedFile = $path;
                }
            }, $this->expectCallableNever());
        $loop->run();
        $this->assertNotNull($catchCreatedFile);
        $this->assertEquals(decoct($mode), $this->getFileMode($catchCreatedFile));
    }
    
    public function provideModes()
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
        $path = __DIR__ . '/test-open';
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT, 0664)
            ->then($tests->expectCallableOnce(), $this->expectCallableNever());
        $loop->run();
    }
   
    public function testThatOpenDoesNotOpenAnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
           
        $tests = $this;
        $path = __DIR__ . '/unexisting-file';
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
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
        $path = __DIR__ . '/test-file-create';
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        touch($path);
        chmod($path, "000");
        
        $fs
            ->open($path, \UV::O_WRONLY, 0064)
            ->then($tests->expectCallableNever(),
            $this->expectCallableOnce());
        $loop->run();
    }
    
    public function testThatWriteCanWriteInAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test-write';
        $testbuffer = "testwrite";
        $tests = $this;
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT)->then(function($result) use($fs, $testbuffer){
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), $testbuffer);
    }
    
    public function testThatWriteCannotWriteInAFileOpenForReadingOnly()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test-write';
        $testbuffer = "testwrite";
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
        $fs
            ->open($path, \UV::O_RDONLY | \UV::O_CREAT)->then(function($result) use($fs, $testbuffer){
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), '');
    }
      
    public function testThatStatDoesNotWorkOnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/stat';
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
        $fs->stat($path)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
        
    }
    
   public function testThatReadfileCanReadFromAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test-read';
        $testbuffer = "test";
        $tests = $this;
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
        file_put_contents($path, $testbuffer);
        $fs->readfile($path)
            ->then(function($result) use ($testbuffer, $tests){
                $tests->assertEquals($testbuffer, $result);
            }, $this->expectCallableNever());
        $loop->run();
    }
    
    public function testThatStatReturnsStatsOfAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test-stat';
        $testbuffer = "test";
        $tests = $this;
        
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        }
        elseif (file_exists($path)) {
            rmdir($path);
        }
        
        $catchResult = null;
        
        file_put_contents($path, $testbuffer);
        $fs->stat($path)
            ->then(function($result) use (&$catchResult){
                $catchResult = $result;
            }, $this->expectCallableNever());
        
        $fs->close(0);
        $loop->run();
        $tests->assertEquals(4, $catchResult['size']);
    }
    
}
