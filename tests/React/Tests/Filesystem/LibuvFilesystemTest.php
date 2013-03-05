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
        if (file_exists($directory)) {
            rmdir($directory);
        }
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
        if (file_exists($directory)) {
            rmdir($directory);
        }
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
        } elseif (file_exists($directory)) {
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
        if (file_exists($directory)) {
            rmdir($directory);
        }
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
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $catchCreatedFile = null;

        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT, $mode)
            ->then(function ($result) use (&$catchCreatedFile, $path) {
                if ($result > 0) {
                    $catchCreatedFile = $path;
                }
            }, $this->expectCallableNever());
        $loop->run();
        $this->assertNotNull($catchCreatedFile);
        $this->assertEquals(decoct($mode), $this->getFileMode($catchCreatedFile));
        if (file_exists($path)) {
            unlink($path);
        }
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
        } elseif (file_exists($path)) {
            rmdir($path);
        }

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
        $path = __DIR__ . '/unexisting-file';

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $fs
            ->open($path, \UV::O_WRONLY, 0664)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testThatOpenThrowsAnExceptionWhenPermissionsAreSetToNull()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $path = __DIR__ . '/test-file-create';

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }
        touch($path);
        chmod($path, "000");

        $fs
            ->open($path, \UV::O_WRONLY, 0064)
            ->then($tests->expectCallableNever(),
            $this->expectCallableOnce());
        $loop->run();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testThatWriteCanWriteInAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test-write';
        $testbuffer = "testwrite";

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $fs
            ->open($path, \UV::O_WRONLY | \UV::O_CREAT)->then(function($result) use ($fs, $testbuffer) {
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), $testbuffer);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testThatWriteCannotWriteInAFileOpenForReadingOnly()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test-write';
        $testbuffer = "testwrite";

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $fs
            ->open($path, \UV::O_RDONLY | \UV::O_CREAT)->then(function($result) use ($fs, $testbuffer) {
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), '');
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testThatStatDoesNotWorkOnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/stat';

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $fs->stat($path)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testThatReadCanReadFromAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test-read';
        $testbuffer = "testread";

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }

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
        if (file_exists($path)) {
            unlink($path);
        }
    }

   public function testThatReadfileCanReadFromAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test';
        $testbuffer = "test2";

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $buffer = null;

        file_put_contents($path, $testbuffer);
        $fs->readfile($path)
            ->then(function($result) use ($testbuffer, &$buffer) {
                $buffer= $result;
            }, $this->expectCallableNever());
        $loop->run();
        $this->assertEquals($testbuffer, $buffer);
        if (file_exists($path)) {
            unlink($path);
        }
    }

       public function testThatReadfileCannotReadAnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = __DIR__ . '/test';
        $testbuffer = "test2";

        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $buffer = null;

        $fs->readfile($path)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
        if (file_exists($path)) {
            unlink($path);
        }
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
        } elseif (file_exists($path)) {
            rmdir($path);
        }

        $catchResult = null;

        file_put_contents($path, $testbuffer);
        $fs->stat($path)
            ->then(function($result) use (&$catchResult) {
                $catchResult = $result;
            }, $this->expectCallableNever());

        $fs->close(0);
        $loop->run();
        $tests->assertEquals(4, $catchResult['size']);
        if (file_exists($path)) {
            unlink($path);
        }
    }

}
