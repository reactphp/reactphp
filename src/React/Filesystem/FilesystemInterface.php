<?php

namespace React\Filesystem;

interface FilesystemInterface
{
  public function mkdir($dirname, $permissions);
  public function open($path, $flags, $mode);
  public function write($fd, $buffer, $offset);
  public function read($fd, $length);
  public function close($fd);
  public function stat($filename);
  public function readFile($filename);
}
