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
        $this->assertSame('8.8.8.8:53', $this->getResolverPrivateMemberValue($resolver, 'nameserver'));
    }

    /** @test */
    public function createCachedShouldCreateResolverWithCachedExecutor()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $factory = new Factory();
        $resolver = $factory->createCached('8.8.8.8:53', $loop);

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
        $this->assertSame($expected, $this->getResolverPrivateMemberValue($resolver, 'nameserver'));
    }

    public static function factoryShouldAddDefaultPortProvider()
    {
        return array(
            array('8.8.8.8',        '8.8.8.8:53'),
            array('1.2.3.4:5',      '1.2.3.4:5'),
            array('localhost',      'localhost:53'),
            array('localhost:1234', 'localhost:1234'),
            array('::1',            '[::1]:53'),
            array('[::1]:53',       '[::1]:53')
        );
    }

    private function getResolverPrivateMemberValue($resolver, $field)
    {
        $reflector = new \ReflectionProperty('React\Dns\Resolver\Resolver', $field);
        $reflector->setAccessible(true);
        return $reflector->getValue($resolver);
    }
}
