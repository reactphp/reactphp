<?php

namespace React\Filesystem;

use React\EventLoop\LibUvLoop;
use React\Filesystem\Exception\IOException;
use React\Promise\Deferred;
use React\Promise\When;


class LibuvFilesystem implements FilesystemInterface
{
    private $loop;
    
    public function __construct(LibUvLoop $loop)
    {
        $this->loop = $loop;
    }
    
    /**
     * 
     * @param String $dirname
     * @param Integer $permissions
     * 
     * @return Deferred
     */
    public function mkdir($dirname, $mode = 0755)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_mkdir($loop, $dirname, $mode | \UV::O_CREAT, function ($result) use ($deferred, $dirname, $loop) {
            if (-1 === $result) {
                $deferred->reject(new IOException(
                    uv_strerror(uv_last_error($loop))
                ));
                return;
            }
            $deferred->resolve($dirname);
        });

        return $deferred->promise();
    }
    
    /**
     * 
     * @param String $path
     * @param Integer $options
     * @param Integer $mode
     * 
     * @return Deferred
     */
    public function open($path, $flags = \UV::O_RDONLY, $mode = 0755)
    {    
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_open($loop, $path, $flags, $mode, function ($r) use ($deferred, $path, $loop) {
            if (-1 === $r) {
                $deferred->reject(new IOException(
                    uv_strerror(uv_last_error($loop))
                ));
                return;
            }
            $deferred->resolve($r);
        });

        return $deferred->promise();
    }

    /**
     * 
     * @param Integer $fd
     * @param String $buffer
     * @param Integer $offset
     *
     * @return Deferred
     */
    public function write($fd, $buffer, $offset = 0)
    {   
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_write($loop, $fd, $buffer, $offset, function ($stream, $result) use ($deferred, $loop) {
            if (-1 === $result) {
                $deferred->reject(new IOException(
                    uv_strerror(uv_last_error($loop))
                ));
                return;
            }
            $deferred->resolve($result);
        });

        return $deferred->promise();
    }
    
    /**
     * 
     * @param type $fd
     * 
     * @return Deferred
     */
    public function close($fd)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_close($loop, $fd, function ($result) use ($deferred, $loop) {
            if (-1 === $result) {
                $deferred->reject(new IOException(
                    uv_strerror(uv_last_error($loop))
                ));
                return;
            }
            $deferred->resolve($result);
        });

        return $deferred->promise();
    }
    
    /**
     * 
     * @param Integer $fd
     * @param String $buffer
     * 
     * @return Deferred
     */
    public function read($fd, $length)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_read($loop, $fd, $length, function ($r, $nbread, $buffer) use ($deferred, $loop) {
            if ($nbread <= 0) {
                $deferred->reject(new IOException(
                    uv_strerror(uv_last_error($loop))
                ));
                return;
            }
            $deferred->resolve($buffer);
        });

        return $deferred->promise();
    }
    
    /**
     * 
     * @param String $filename
     * 
     * @return Deferred
     */
    public function stat($filename)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        uv_fs_stat($loop, $filename, function ($result, $stat) use ($deferred, $loop) {
            if (-1 === $result) {
                $deferred->reject(new IOException(
                        uv_strerror(uv_last_error($loop))
                ));
                return;
            }
           $deferred->resolve($stat);
        });
     return $deferred->promise();
    }
    
    /**
     * 
     * @param String $filename
     * 
     * @return Deferred
     */ 
    public function readfile($filename)
    {
        $fs = $this;
        $thatFd = null;
        
        $all = array(
            'stat' => $fs->stat($filename),
            'fd'   => $fs->open($filename)
        );
        return When::all($all)
            ->then(function ($result) use ($fs, &$thatFd) {
                $thatFd = $result['fd'];
                return $fs->read($thatFd, $result['stat']['size']);
            })
            ->then(function ($data) use ($fs, &$thatFd) {
                return $fs->close($thatFd);
            });
    }
}
