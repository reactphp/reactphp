<?php

namespace React\Tests\Dns\Resolver;

use React\Dns\Resolver\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function createShouldCreateResolver()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $factory = new Factory();
        $resolver = $factory->create('8.8.8.8:53', $loop);

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
    }

    /** @test */
    public function createWithoutPortShouldCreateResolverWithDefaultPort()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $factory = new Factory();
        $resolver = $factory->create('8.8.8.8', $loop);

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);

        $reflector = new \ReflectionProperty('React\Dns\Resolver\Resolver', 'nameserver');
        $reflector->setAccessible(true);
        $nameserver = $reflector->getValue($resolver);
        $this->assertSame('8.8.8.8:53', $nameserver);
    }
}
