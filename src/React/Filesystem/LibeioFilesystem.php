<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;

class Filesystem
{
    private $loop;
    private $fd;
    private $active = false;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->fd = eio_get_event_stream();
    }

    public function mkdir($dirname, $permissions = 0755, $callback = null)
    {
        $this->register();

        $callback = $callback ?: function ($err, $result) {};

        eio_mkdir($dirname, $permissions, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($callback) {
            if (0 !== $result) {
                $err = eio_get_last_error($req);
                call_user_func($callback, $err, $result);
                return;
            }

            call_user_func($callback, null, $result);
        });
    }

    public function open($path, $flags, $mode = 0644, $callback)
    {
        $this->register();

        $flags = 0;
        if (false !== strpos($flags, 'w') || false !== strpos($flags, 'a')) {
            $flags |= EIO_O_CREAT;
        }
        if (false !== strpos($flags, 'r+') || false !== strpos($flags, 'w+')) {
            $flags |= EIO_O_RDWR;
        } elseif (false !== strpos($flags, 'w')) {
            $flags |= EIO_O_WRONLY;
        } else {
            $flags |= EIO_O_RDONLY;
        }

        eio_open($path, $flags, $mode, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($callback) {
            if (0 === $result) {
                $err = eio_get_last_error($req);
                call_user_func($callback, $err, $result);
                return;
            }

            call_user_func($callback, null, $result);
        });
    }

    public function read($fd, $length, $offset, $callback)
    {
        $this->register();

        eio_read($fd, $length, $offset, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($callback) {
            $callback(null, $result);
        });
    }

    public function close($fd, $callback = null)
    {
        $this->register();

        $callback = $callback ?: function ($err, $result) {};

        eio_close($fd, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($callback) {
            if (0 !== $result) {
                $err = eio_get_last_error($req);
                call_user_func($callback, $err, $result);
                return;
            }

            call_user_func($callback, null, $result);
        });
    }

    public function stat($filename, $callback)
    {
        $this->register();

        eio_stat($filename, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($callback) {
            call_user_func($callback, null, $result);
        });
    }

    public function readFile($filename, $callback)
    {
        $fs = $this;

        $fs->stat($filename, function ($err, $stat) use ($fs, $filename, $callback) {
            $size = $stat['size'];

            $fs->open($filename, 'w+', 0644, function ($err, $fd) use ($fs, $size, $callback) {
                $fs->read($fd, $size, 0, function ($err, $data) use ($fs, $fd, $callback) {
                    call_user_func($callback, null, $data);

                    $fs->close($fd);
                });
            });
        });
    }

    public function register()
    {
        if ($this->active) {
            return;
        }

        $this->active = true;
        $this->loop->addReadStream($this->fd, array($this, 'handleReadEvent'));
    }

    public function unregister()
    {
        if (!$this->active) {
            return;
        }

        $this->active = false;
        $this->loop->removeReadStream($this->fd, array($this, 'handleReadEvent'));
    }

    public function handleReadEvent()
    {
        if (!eio_npending()) {
            return;
        }

        while (eio_npending()) {
            eio_poll();
        }
        $this->unregister();
    }
}
