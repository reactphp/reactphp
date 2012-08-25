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

    private $exitCode = null;

    private $signalCode = null;

    private $exited = false;

    public function __construct($process, WritableStreamInterface $stdin, ReadableStreamInterface $stdout, ReadableStreamInterface $stderr)
    {
        $this->process = $process;
        $this->stdin   = $stdin;
        $this->stdout  = $stdout;
        $this->stderr  = $stderr;

        $self = $this;

        $this->stdout->on('end', function () use ($self, $stderr) {
            if ($stderr->isReadable() === false) {
                $self->exits();
            }
        });

        $this->stderr->on('end', function () use ($self, $stdout) {
            if ($stdout->isReadable() === false) {
                $self->exits();
            }
        });
    }

    public function exits()
    {
        if ($this->exited) {
            return;
        }

        $exitCode = proc_close($this->process);
        $this->exited = true;

        $this->handleExit($exitCode);
    }

    public function handleExit($exitCode)
    {
        $this->exitCode = $exitCode;

        $this->emit('exit', array($exitCode));
        $this->emit('close', array($exitCode));
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
        if ($this->exited) {
            return false;
        } else {
            $status = $this->getFreshStatus();

            return $status['running'];
        }
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

    public function terminate()
    {
        proc_terminate($this->process);
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }

    public function getSignalCode()
    {
        return $this->signalCode;
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
