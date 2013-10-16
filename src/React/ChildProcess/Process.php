<?php

namespace React\ChildProcess;

use React\Stream\WritableStreamInterface;
use React\Stream\ReadableStreamInterface;
use Evenement\EventEmitter;

class Process extends EventEmitter
{
    const SIGNAL_CODE_SIGKILL = 9;
    const SIGNAL_CODE_SIGTERM = 15;

    public $stdin;

    public $stdout;

    public $stderr;

    private $process;

    private $status = null;

    private $exitCode = null;

    private $signalCode = null;

    private $exited = false;
    
    private $stopped = false;

    public function __construct($process, WritableStreamInterface $stdin, ReadableStreamInterface $stdout, ReadableStreamInterface $stderr)
    {
        $this->process = $process;
        $this->stdin   = $stdin;
        $this->stdout  = $stdout;
        $this->stderr  = $stderr;

        $self = $this;

        $this->stdout->on('end', function () use ($self) {
            $self->updateStatus();
            $self->observeStatus();
        });

        $this->stderr->on('end', function () use ($self) {
            $self->updateStatus();
            $self->observeStatus();
        });
    }

    public function updateStatus()
    {
        if ($this->process && is_null($this->signalCode)) {
            $this->status = proc_get_status($this->process);
            if (!$this->status['running'] && !$this->stopped) {
                $this->exitCode = $this->status['exitcode'];
                $this->stopped = true;
            }
            if ($this->status['signaled']) {
                $this->signalCode = $this->status['termsig'];
            }
        }
    }

    public function observeStatus()
    {
        if (!$this->stdout->isReadable() && !$this->stderr->isReadable()) {
            $this->stdin->close();
            $this->stdout->close();
            $this->stderr->close();
            $this->exits();
        }
    }

    public function exits()
    {
        proc_close($this->process);
        $this->process = null;

        if ($this->signalCode) {
            $this->handleExit(null, $this->signalCode);
        } else {
            $this->handleExit($this->exitCode, null);
        }
    }

    public function handleExit($exitCode, $signalCode)
    {
        if ($this->exited) {
            return;
        }

        $this->exited = true;

        $this->exitCode = $exitCode;
        $this->signalCode = $signalCode;

        $this->emit('exit', array($exitCode, $signalCode));
        $this->emit('close', array($exitCode, $signalCode));
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

    public function terminate($signalCode = self::SIGNAL_CODE_SIGTERM)
    {
        proc_terminate($this->process, $signalCode);
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
            $this->updateStatus();
        }

        return $this->status;
    }

    private function getFreshStatus()
    {
        $this->updateStatus();

        return $this->status;
    }
}
