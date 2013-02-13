<?php

namespace React\Filesystem;

interface FilesystemInterface
{
  public function mkdir($dirname, $permissions = 0755);
  public function open($path, $flags = UV::O_WRONLY, $mode = 0644);
  public function write($fd, $buffer, $offset);
  public function read($fd, $length);
  public function close($fd);
  public function stat($filename);
  public function readfile($filename);
}
