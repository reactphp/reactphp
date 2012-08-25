<?php

namespace React\Tests\ChildProcess;

use React\ChildProcess\Process;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    const USLEEP_TIME_FOR_PROCESS_TERMINATION = 1000000; // 1 sec

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
        $process->terminate();

        usleep(self::USLEEP_TIME_FOR_PROCESS_TERMINATION);

        $this->assertFalse($process->isRunning());
    }

    public function testHandleExitExitEvent()
    {
        $process = $this->createProcess("php '-r' 'exit(0);'");

        $capturedExitStatus = null;

        $process->on('exit', function ($status) use (&$capturedExitStatus) {
            $capturedExitStatus = $status;
        });

        $process->handleExit();

        $this->assertEquals(0, $capturedExitStatus);
    }

    public function testHandleExitExitEventStatus1()
    {
        $process = $this->createProcess("php '-r' 'exit(1);'");

        $capturedExitStatus = null;

        $process->on('exit', function ($status) use (&$capturedExitStatus) {
            $capturedExitStatus = $status;
        });

        $process->handleExit();

        $this->assertEquals(1, $capturedExitStatus);
    }

    public function testHandleCloseExitEvent()
    {
        $process = $this->createProcess("php '-r' 'exit(0);'");

        $capturedExitStatus = null;

        $process->on('close', function ($status) use (&$capturedExitStatus) {
            $capturedExitStatus = $status;
        });

        $process->handleExit();

        $this->assertEquals(0, $capturedExitStatus);
    }

    public function testHandleCloseExitEventStatus1()
    {
        $process = $this->createProcess("php '-r' 'exit(1);'");

        $capturedExitStatus = null;

        $process->on('close', function ($status) use (&$capturedExitStatus) {
            $capturedExitStatus = $status;
        });

        $process->handleExit();

        $this->assertEquals(1, $capturedExitStatus);
    }

    private function createProcess($command)
    {
        return new Process(
            $this->createProcessStream($command),
            $this->getMock('React\Stream\WritableStreamInterface'),
            $this->getMock('React\Stream\ReadableStreamInterface'),
            $this->getMock('React\Stream\ReadableStreamInterface')
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
