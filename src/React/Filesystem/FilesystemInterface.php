<?php

namespace React\Filesystem;

interface FilesystemInterface
{
    const FLAG_RDONLY = 0;
    const FLAG_WRONLY = 1;
    const FLAG_CREAT = 2;
    const FLAG_APPEND = 4;
    
    public function mkdir($dirname, $permissions);
    public function open($path, $flags, $mode);
    public function write($fd, $buffer, $offset);
    public function read($fd, $length);
    public function close($fd);
    public function stat($filename);
    public function readFile($filename);
}
