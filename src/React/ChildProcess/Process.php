<?php

namespace React\ChildProcess;

use React\Stream\WritableStreamInterface;
use React\Stream\ReadableStreamInterface;
use Evenement\EventEmitter;

class Process extends EventEmitter
{
    public $stdin;

    public $stdout;

    public $stderr;

    private $process;

    private $status = null;

    public function __construct($process, WritableStreamInterface $stdin, ReadableStreamInterface $stdout, ReadableStreamInterface $stderr)
    {
        $this->process = $process;
        $this->stdin   = $stdin;
        $this->stdout  = $stdout;
        $this->stderr  = $stderr;

        $self = $this;

        $this->stdout->on('end', function () use ($self, $stderr) {
            if ($stderr->isReadable() === false) {
                $self->handleExit();
            }
        });

        $this->stderr->on('end', function () use ($self, $stdout) {
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

    public function getPid()
    {
        $status = $this->getCachedStatus();

        return $status['pid'];
    }

    public function getCommand()
    {
        $status = $this->getCachedStatus();

        return $status['command'];
    }

    public function isRunning()
    {
        $status = $this->getFreshStatus();

        return $status['running'];
    }

    public function isSignaled()
    {
        $status = $this->getFreshStatus();

        return $status['signaled'];
    }

    public function isStopped()
    {
        $status = $this->getFreshStatus();

        return $status['stopped'];
    }

    private function getCachedStatus()
    {
        if (is_null($this->status)) {
            $this->status = proc_get_status($this->process);
        }

        return $this->status;
    }

    private function getFreshStatus()
    {
        $this->status = proc_get_status($this->process);

        return $this->status;
    }
}
