<?php

namespace React\Filesystem;

use React\EventLoop\LibUvLoop;
use React\Filesystem\FlagConverter\LibUvFlagConverter;
use React\Filesystem\Exception\IoException;
use React\Promise\Deferred;
use React\Promise\When;

class LibuvFilesystem implements FilesystemInterface
{
    private $loop;
    private $converter;
    
    public function __construct(LibUvLoop $loop)
    {
        if (!$loop instanceof LibUvLoop) {
            throw new \InvalidArgumentException('Argument 1 should be an instance of LibUvLoop.');
        }
        $this->loop = $loop;
        $this->converter = new LibUvFlagConverter();
    }

    public function mkdir($dirname, $mode = 0755)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_mkdir($loop, $dirname, $mode, function ($result) use ($deferred, $dirname, $loop) {
            if (-1 === $result) {
                $deferred->reject($this->createIoException($loop));
                return;
            }
            $deferred->resolve($dirname);
        });

        return $deferred->promise();
    }

    public function open($path, $flags = FilesystemInterface::FLAG_RDONLY, $mode = 0755)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        $flags = $this->converter->convertFlags($flags);
        uv_fs_open($loop, $path, $flags, $mode, function ($r) use ($deferred, $path, $loop) {
            if (-1 === $r) {
                $deferred->reject($this->createIoException($loop));
                return;
            }
            $deferred->resolve($r);
        });

        return $deferred->promise();
    }

    public function write($fd, $buffer, $offset = 0)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_write($loop, $fd, $buffer, $offset, function ($stream, $result) use ($deferred, $loop) {
            if (-1 === $result) {
                $deferred->reject($this->createIoException($loop));
                return;
            }
            $deferred->resolve($result);
        });

        return $deferred->promise();
    }

    public function close($fd)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_close($loop, $fd, function ($result) use ($deferred, $loop) {
            if (-1 === $result) {
                $deferred->reject($this->createIoException($loop));
                return;
            }
            $deferred->resolve($result);
        });

        return $deferred->promise();
    }

    public function read($fd, $length)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_read($loop, $fd, $length, function ($r, $nbread, $buffer) use ($deferred, $loop) {
            if ($nbread <= 0) {
                $deferred->reject($this->createIoException($loop));
                return;
            }
            $deferred->resolve($buffer);
        });

        return $deferred->promise();
    }

    public function stat($filename)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_stat($loop, $filename, function ($result, $stat) use ($deferred, $loop) {
            if (-1 === $result) {
                $deferred->reject($this->createIoException($loop));
                return;
            }
            $deferred->resolve($stat);
        });

     return $deferred->promise();
    }

    public function readFile($filename)
    {
        $fs = $this;

        $all = array(
            'stat' => $fs->stat($filename),
            'fd'   => $fs->open($filename)
        );

        return When::all($all)->then(function ($result) use ($fs) {
            $fd = $result['fd'];

            return $fs->read($fd, $result['stat']['size'])->then(function ($data) use ($fs, $fd) {
                $fs->close($fd);
                return $data;
            });
        });
    }
    
    private function createIoException($loop)
    {
        return new IoException(uv_strerror(uv_last_error($loop)));
    }
}
