<?php

namespace React\Filesystem;

interface FilesystemInterface
{
    public function mkdir($dirname, $permissions = 0755);
    public function open($path, $flags, $mode = 0644);
    public function read($fd, $length, $offset);
    public function close($fd);
    public function stat($filename);
    public function readFile($filename);
}
