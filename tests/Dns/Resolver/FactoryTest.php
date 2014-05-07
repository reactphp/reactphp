<?php

namespace React\Tests\Dns\Resolver;

use React\Dns\Resolver\Factory;
use React\Socket\AddressFactory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function createShouldCreateResolver()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $factory = new Factory();
        $resolver = $factory->create('udp://8.8.8.8:53', $loop);

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
    }

    /** @test */
    public function createWithoutPortShouldCreateResolverWithDefaultPort()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $factory = new Factory();
        $resolver = $factory->create('udp://8.8.8.8', $loop);

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
        $this->assertEquals(AddressFactory::create('udp://8.8.8.8:53'), $this->getResolverPrivateMemberValue($resolver, 'nameserver'));
    }

    /** @test */
    public function createCachedShouldCreateResolverWithCachedExecutor()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $factory = new Factory();
        $resolver = $factory->createCached('udp://8.8.8.8:53', $loop);

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
        $this->assertInstanceOf('React\Dns\Query\CachedExecutor', $this->getResolverPrivateMemberValue($resolver, 'executor'));
    }

    /**
     * @test
     * @dataProvider factoryShouldAddDefaultPortProvider
     */
    public function factoryShouldAddDefaultPort($input, $expected)
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $factory = new Factory();
        $resolver = $factory->create($input, $loop);

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
        $this->assertEquals($expected, $this->getResolverPrivateMemberValue($resolver, 'nameserver'));
    }

    public static function factoryShouldAddDefaultPortProvider()
    {
        return array(
            array('udp://8.8.8.8',        AddressFactory::create('udp://8.8.8.8:53')),
            array('udp://1.2.3.4:5',      'udp://1.2.3.4:5'),
            array('udp://localhost',      'udp://localhost:53'),
            array('udp://localhost:1234', 'udp://localhost:1234'),
            // array('udp://::1',            'udp://[::1]:53'),
            array('udp://[::1]:53',       'udp://[::1]:53')
        );
    }

    private function getResolverPrivateMemberValue($resolver, $field)
    {
        $reflector = new \ReflectionProperty('React\Dns\Resolver\Resolver', $field);
        $reflector->setAccessible(true);
        return $reflector->getValue($resolver);
    }
}
