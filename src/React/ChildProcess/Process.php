<?php

namespace React\ChildProcess;

use React\EventLoop\LoopInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\ReadableStreamInterface;
use Evenement\EventEmitter;

class Process extends EventEmitter
{
    public $stdin;
    public $stdout;
    public $stderr;

    public function __construct(LoopInterface $loop, $process, WritableStreamInterface $stdin, ReadableStreamInterface $stdout, ReadableStreamInterface $stderr)
    {
        $this->process = $process;
        $this->stdin   = $stdin;
        $this->stdout  = $stdout;
        $this->stderr  = $stderr;

        $self = $this;

        $this->stdout->on('end', function () use ($self, $stdout, $stderr) {
            $stdout->close();

            if ($stderr->isReadable() === false) {
                $self->handleExit();
            }
        });

        $this->stderr->on('end', function () use ($self, $stdout, $stderr) {
            $stderr->close();

            if ($stdout->isReadable() === false) {
                $self->handleExit();
            }
        });
    }

    public function handleExit()
    {
        $status = proc_close($this->process);
        $this->emit('exit', array($status));
        $this->emit('close', array($status));
    }
}
