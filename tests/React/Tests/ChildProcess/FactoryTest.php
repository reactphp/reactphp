<?php

namespace React\Tests\ChildProcess;

use React\ChildProcess\Factory;
use React\EventLoop\StreamSelectLoop;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testSpawn()
    {
        $loop    = $this->createLoop();
        $factory = $this->createFactory($loop);
        $process = $factory->spawn('php', array('-r', 'echo "cwd = ", getcwd(), ", count of env = ", count($_ENV);'));

        $capturedData = '';

        $process->stdout->on('data', function ($data) use (&$capturedData) {
            $capturedData .= $data;
        });

        $loop->run();

        $cwd = getcwd();
        $this->assertSame("cwd = {$cwd}, count of env = 0", $capturedData);
    }

    public function testSpawnWithPwd()
    {
        $loop    = $this->createLoop();
        $factory = $this->createFactory($loop);
        $process = $factory->spawn('php', array('-r', 'echo "cwd = ", getcwd();'), '/');

        $capturedData = '';

        $process->stdout->on('data', function ($data) use (&$capturedData) {
            $capturedData .= $data;
        });

        $loop->run();

        $this->assertSame('cwd = /', $capturedData);
    }

    public function testSpawnWithEnv()
    {
        $loop    = $this->createLoop();
        $factory = $this->createFactory($loop);
        $process = $factory->spawn('php', array('-r', 'echo "foo = ", $_SERVER["foo"];'), null, array('foo' => 'FOO'));

        $capturedData = '';

        $process->stdout->on('data', function ($data) use (&$capturedData) {
            $capturedData .= $data;
        });

        $loop->run();

        $this->assertSame('foo = FOO', $capturedData);
    }

    public function testCommand()
    {
        $loop    = $this->createLoop();
        $factory = $this->createFactory($loop);
        $process = $factory->spawn('echo');

        $this->assertSame('echo', $process->getCommand());
    }

    public function testCommandWithOneArgument()
    {
        $loop    = $this->createLoop();
        $factory = $this->createFactory($loop);
        $process = $factory->spawn('echo', array('foo'));

        $this->assertSame("echo 'foo'", $process->getCommand());
    }

    public function testCommandWithManyArguments()
    {
        $loop    = $this->createLoop();
        $factory = $this->createFactory($loop);
        $process = $factory->spawn('echo', array('foo', 'bar', 'foo bar'));

        $this->assertSame("echo 'foo' 'bar' 'foo bar'", $process->getCommand());
    }

    private function createFactory($loop)
    {
        return new Factory($loop);
    }

    private function createLoop()
    {
        return new StreamSelectLoop();
    }
}
