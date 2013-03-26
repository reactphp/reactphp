<?php

namespace React\Tests\ChildProcess;

use React\ChildProcess\Process;
use React\EventLoop\Factory as LoopFactory;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEnhanceSigchildCompatibility()
    {
        $process = new Process('echo foo');

        $this->assertSame($process, $process->setEnhanceSigchildCompatibility(true));
        $this->assertTrue($process->getEnhanceSigchildCompatibility());

        $this->assertSame($process, $process->setEnhanceSigchildCompatibility(false));
        $this->assertFalse($process->getEnhanceSigchildCompatibility());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetExitCodeShouldThrowExceptionIfSigchildCompatibilityIsRequired()
    {
        if (!Process::isSigchildEnabled()) {
            $this->markTestSkipped('PHP has not been compiled with --enable-sigchild.');
        }

        $process = new Process('echo foo');
        $process->setEnhanceSigchildCompatibility(false);

        $process->getExitCode();
    }

    public function testGetCommand()
    {
        $process = new Process('echo foo');

        $this->assertSame('echo foo', $process->getCommand());
    }

    public function testIsRunning()
    {
        $loop = LoopFactory::create();
        $process = new Process('sleep 1');

        $this->assertFalse($process->isRunning());

        $process->start($loop);

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
    public function testGetTermSignalWhenRunning($process)
    {
        $this->assertNull($process->getTermSignal());
    }

    public function testProcessWithDefaultCwdAndEnv()
    {
        $cmd = 'php -r ' . escapeshellarg('echo getcwd(), PHP_EOL, count($_ENV), PHP_EOL;');

        $loop = LoopFactory::create();
        $process = new Process($cmd);

        $output = '';

        $loop->addTimer(0.001, function() use ($process, $loop, &$output) {
            $process->start($loop);
            $process->stdout->on('data', function () use (&$output) {
                $output .= func_get_arg(0);
            });
        });

        $loop->run();

        $expectedOutput = sprintf('%s%s%d%s', getcwd(), PHP_EOL, count($_ENV), PHP_EOL);
        $this->assertSame($expectedOutput, $output);
    }

    public function testProcessWithCwd()
    {
        $cmd = 'php -r ' . escapeshellarg('echo getcwd(), PHP_EOL;');

        $loop = LoopFactory::create();
        $process = new Process($cmd, '/');

        $output = '';

        $loop->addTimer(0.001, function() use ($process, $loop, &$output) {
            $process->start($loop);
            $process->stdout->on('data', function () use (&$output) {
                $output .= func_get_arg(0);
            });
        });

        $loop->run();

        $this->assertSame('/' . PHP_EOL, $output);
    }

    public function testProcessWithEnv()
    {
        $cmd = 'php -r ' . escapeshellarg('echo getenv("foo"), PHP_EOL;');

        $loop = LoopFactory::create();
        $process = new Process($cmd, null, array('foo' => 'bar'));

        $output = '';

        $loop->addTimer(0.001, function() use ($process, $loop, &$output) {
            $process->start($loop);
            $process->stdout->on('data', function () use (&$output) {
                $output .= func_get_arg(0);
            });
        });

        $loop->run();

        $this->assertSame('bar' . PHP_EOL, $output);
    }

    public function testStartAndAllowProcessToExitSuccessfullyUsingEventLoop()
    {
        $loop = LoopFactory::create();
        $process = new Process('exit 0');

        $called = false;
        $exitCode = 'initial';
        $termSignal = 'initial';

        $process->on('exit', function () use (&$called, &$exitCode, &$termSignal) {
            $called = true;
            $exitCode = func_get_arg(0);
            $termSignal = func_get_arg(1);
        });

        $loop->addTimer(0.001, function() use ($process, $loop) {
            $process->start($loop);
        });

        $loop->run();

        $this->assertTrue($called);
        $this->assertSame(0, $exitCode);
        $this->assertNull($termSignal);

        $this->assertFalse($process->isRunning());
        $this->assertSame(0, $process->getExitCode());
        $this->assertNull($process->getTermSignal());
        $this->assertFalse($process->isTerminated());
    }

    public function testStartInvalidProcess()
    {
        $cmd = tempnam(sys_get_temp_dir(), 'react');

        $loop = LoopFactory::create();
        $process = new Process($cmd);

        $output = '';

        $loop->addTimer(0.001, function() use ($process, $loop, &$output) {
            $process->start($loop);
            $process->stderr->on('data', function () use (&$output) {
                $output .= func_get_arg(0);
            });
        });

        $loop->run();

        unlink($cmd);

        $this->assertNotEmpty($output);
    }

    public function testStartInvalidProcessWithoutErrorChecking()
    {
        $cmd = tempnam(sys_get_temp_dir(), 'react');

        $loop = LoopFactory::create();
        $process = new Process($cmd);
        $process->start($loop, 0.1, false);
        unlink($cmd);
    }

    public function testTerminateWithDefaultTermSignalUsingEventLoop()
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->markTestSkipped('Windows does not report signals via proc_get_status()');
        }

        if (!defined('SIGTERM')) {
            $this->markTestSkipped('SIGTERM is not defined');
        }

        $loop = LoopFactory::create();
        $process = new Process('sleep 1; exit 0');

        $called = false;
        $exitCode = 'initial';
        $termSignal = 'initial';

        $process->on('exit', function () use (&$called, &$exitCode, &$termSignal) {
            $called = true;
            $exitCode = func_get_arg(0);
            $termSignal = func_get_arg(1);
        });

        $loop->addTimer(0.001, function() use ($process, $loop) {
            $process->start($loop);
            $process->terminate();
        });

        $loop->run();

        $this->assertTrue($called);
        $this->assertNull($exitCode);
        $this->assertEquals(SIGTERM, $termSignal);

        $this->assertFalse($process->isRunning());
        $this->assertNull($process->getExitCode());
        $this->assertEquals(SIGTERM, $process->getTermSignal());
        $this->assertTrue($process->isTerminated());
    }

    public function testTerminateWithStopAndContinueSignalsUsingEventLoop()
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->markTestSkipped('Windows does not report signals via proc_get_status()');
        }

        if (!defined('SIGSTOP') && !defined('SIGCONT')) {
            $this->markTestSkipped('SIGSTOP and/or SIGCONT is not defined');
        }

        $loop = LoopFactory::create();
        $process = new Process('sleep 1; exit 0');

        $called = false;
        $exitCode = 'initial';
        $termSignal = 'initial';

        $process->on('exit', function () use (&$called, &$exitCode, &$termSignal) {
            $called = true;
            $exitCode = func_get_arg(0);
            $termSignal = func_get_arg(1);
        });

        $self = $this;

        $loop->addTimer(0.001, function() use ($process, $loop, $self) {
            $process->start($loop);
            $process->terminate(SIGSTOP);

            $self->assertSoon(function() use ($self, $process) {
                $self->assertTrue($process->isStopped());
                $self->assertTrue($process->isRunning());
                $self->assertEquals(SIGSTOP, $process->getStopSignal());
            });

            $process->terminate(SIGCONT);

            $self->assertSoon(function() use ($self, $process) {
                $self->assertFalse($process->isStopped());
                $self->assertEquals(SIGSTOP, $process->getStopSignal());
            });
        });

        $loop->run();

        $this->assertTrue($called);
        $this->assertSame(0, $exitCode);
        $this->assertNull($termSignal);

        $this->assertFalse($process->isRunning());
        $this->assertSame(0, $process->getExitCode());
        $this->assertNull($process->getTermSignal());
        $this->assertFalse($process->isTerminated());
    }

    /**
     * Execute a callback at regular intervals until it returns successfully or
     * a timeout is reached.
     *
     * @param Closure $callback Callback with one or more assertions
     * @param integer $timeout  Time limit for callback to succeed (milliseconds)
     * @param integer $interval Interval for retrying the callback (milliseconds)
     * @throws PHPUnit_Framework_ExpectationFailedException Last exception raised by the callback
     */
    private function assertSoon(\Closure $callback, $timeout = 20000, $interval = 200)
    {
        $start = microtime(true);
        $timeout /= 1000; // convert to seconds
        $interval *= 1000; // convert to microseconds

        while (1) {
            try {
                call_user_func($callback);
                return;
            } catch (\PHPUnit_Framework_ExpectationFailedException $e) {}

            if ((microtime(true) - $start) > $timeout) {
                throw $e;
            }

            usleep($interval);
        }
    }
}
