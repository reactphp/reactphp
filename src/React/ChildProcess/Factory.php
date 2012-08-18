<?php

namespace React\ChildProcess;

use React\EventLoop\LoopInterface;
use React\ChildProcess\Process;
use React\ChildProcess\Stream;

class Factory
{
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function spawn($file, array $args = array(), $cwd = null, $env = null)
    {
        $cmd = $this->createCommand($file, $args);

        $fdSpec = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('pipe', 'w'),
        );

        $process = proc_open($cmd, $fdSpec, $pipes, $cwd, $env);

        $stdin  = new Stream($pipes[0], $this->loop);
        $stdout = new Stream($pipes[1], $this->loop);
        $stderr = new Stream($pipes[2], $this->loop);

        $stdin->pause();

        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        return new Process($process, $stdin, $stdout, $stderr);
    }

    private function createCommand($file, $args)
    {
        $command = $file;

        if (count($args) > 0) {
            $command .= ' ' . join(' ', array_map('escapeshellarg', $args));
        }

        return $command;
    }
}
