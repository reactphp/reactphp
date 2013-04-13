<?php

namespace React\Tests\Filesystem;

use React\Tests\Socket\TestCase;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\FlagConverter\LibUvFlagConverter;

class LibUvFlagConverterTest extends TestCase
{
    public function testThatFlagsMatch()
    {
        $converter = new LibUvFlagConverter();
        $uvFlags = \UV::O_CREAT | \UV::O_APPEND | \UV::O_RDONLY | \UV::O_WRONLY;
        $fsFlags = FilesystemInterface::FLAG_APPEND | FilesystemInterface::FLAG_CREAT
                    | FilesystemInterface::FLAG_RDONLY | FilesystemInterface::FLAG_WRONLY;
        $this->assertEquals($uvFlags, $converter->convertFlags($fsFlags));
    }
}
