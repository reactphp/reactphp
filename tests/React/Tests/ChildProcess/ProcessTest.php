<?php

namespace React\Tests\ChildProcess;

use React\ChildProcess\Process;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCommand()
    {
        $process = $this->createProcess('echo foo bar');

        $this->assertSame('echo foo bar', $process->getCommand());
    }

    public function testIsRunning()
    {
        $process = $this->createProcess('echo foo bar');

        $this->assertTrue($process->isRunning());
    }

    public function testIsRunningIsFalseWhenTerminated()
    {
        $process = $this->createProcess('sleep 5');

        // proc_terminate() seems not work well
        // https://bugs.php.net/bug.php?id=39992
        exec('kill -9 ' . $process->getPid());

        $this->assertFalse($process->isRunning());
    }

    private function createProcess($command)
    {
        return new Process(
            $this->createProcessStream($command),
            $this->getMock('\\React\\Stream\\WritableStreamInterface'),
            $this->getMock('\\React\\Stream\\ReadableStreamInterface'),
            $this->getMock('\\React\\Stream\\ReadableStreamInterface')
        );
    }

    private function createProcessStream($command)
    {
        $fdSpecs = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('pipe', 'w'),
        );
        return proc_open($command, $fdSpecs, $pipes);
    }
}
