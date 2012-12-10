<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class LibeioFilesystem implements FilesystemInterface
{
    private $loop;
    private $fd;
    private $active = false;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->fd = eio_get_event_stream();
    }

    public function mkdir($dirname, $permissions = 0755)
    {
        $this->register();

        $deferred = new Deferred();

        eio_mkdir($dirname, $permissions, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($deferred) {
            if (0 !== $result) {
                $deferred->reject(eio_get_last_error($req));
                return;
            }

            $deferred->resolve($result);
        });

        return $deferred->promise();
    }

    public function open($path, $flags, $mode = 0644)
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

        $deferred = new Deferred();

        eio_open($path, $flags, $mode, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($deferred) {
            if (0 === $result) {
                $deferred->reject(eio_get_last_error($req));
                return;
            }

            $deferred->resolve($result);
        });

        return $deferred->promise();
    }

    public function read($fd, $length, $offset)
    {
        $this->register();

        $deferred = new Deferred();

        eio_read($fd, $length, $offset, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($deferred) {
            $deferred->resolve($result);
        });

        return $deferred->promise();
    }

    public function close($fd)
    {
        $this->register();

        $deferred = new Deferred();

        eio_close($fd, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($deferred) {
            if (0 !== $result) {
                $deferred->reject(eio_get_last_error($req));
                return;
            }

            $deferred->resolve($result);
        });
    }

    public function stat($filename)
    {
        $this->register();

        $deferred = new Deferred();

        eio_stat($filename, EIO_PRI_DEFAULT, function ($data, $result, $req) use ($deferred) {
            if (-1 === $result) {
                $deferred->reject(eio_get_last_error($req));
                return;
            }

            $deferred->resolve($result);
        });

        return $deferred->promise();
    }

    public function readFile($filename)
    {
        $fs = $this;

        $deferred = new Deferred();

        $errorHandler = function ($error) use ($deferred) {
            $deferred->reject($error);
        };

        $fs->stat($filename)
            ->then(function($stat) use ($fs, $filename, $deferred, $errorHandler) {
                $size = $stat['size'];

                $fs->open($filename, 'w+', 0644)
                    ->then(function ($fd) use ($fs, $size, $deferred, $errorHandler) {
                        $fs->read($fd, $size, 0)
                            ->then(function($data) use ($fs, $fd, $deferred) {
                                $deferred->resolve($data);
                                $fs->close($fd);
                            }, $errorHandler);
                    }, $errorHandler);
            }, $errorHandler);

        return $deferred->promise();
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
