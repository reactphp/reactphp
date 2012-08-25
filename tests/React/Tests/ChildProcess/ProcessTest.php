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
        $process = $this->createProcess('sleep 10');

        $this->assertTrue($process->isRunning());

        return $process;
    }

    /**
     * @depends testIsRunning
     */
    public function testGetExitCodeWhenRunning($process)
    {
        $this->assertNull($process->getExitCode());
    }

    /**
     * @depends testIsRunning
     */
    public function testGetSignalCodeWhenRunning($process)
    {
        $this->assertNull($process->getSignalCode());
    }

    public function testIsRunningIsFalseWhenTerminated()
    {
        $process = $this->createProcess('sleep 1');
        $process->handleExit();

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

    public function testHandleExitExitEventCanBeCalledOnlyOnce()
    {
        $process = $this->createProcess('php -v');

        $counter = 0;

        $process->on('exit', function ($status) use (&$counter) {
            $counter++;
        });

        $process->handleExit();
        $process->handleExit();

        $this->assertEquals(1, $counter);
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

    public function testHandleExitCloseEventCanBeCalledOnlyOnce()
    {
        $process = $this->createProcess('php -v');

        $counter = 0;

        $process->on('close', function ($status) use (&$counter) {
            $counter++;
        });

        $process->handleExit();
        $process->handleExit();

        $this->assertEquals(1, $counter);
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
