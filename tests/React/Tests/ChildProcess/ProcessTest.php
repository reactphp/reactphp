<?php

namespace React\Tests\ChildProcess;

use React\ChildProcess\Process;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCommand()
    {
        $process = new Process(
            $this->createProcessStream('echo foo bar'),
            $this->getMock('\\React\\Stream\\WritableStreamInterface'),
            $this->getMock('\\React\\Stream\\ReadableStreamInterface'),
            $this->getMock('\\React\\Stream\\ReadableStreamInterface')
        );

        $this->assertSame('echo foo bar', $process->getCommand());
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
