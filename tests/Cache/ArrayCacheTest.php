<?php

namespace React\Tests\Cache;

use React\Cache\ArrayCache;
use React\Tests\Socket\TestCase;

class ArrayCacheTest extends TestCase
{
    private $cache;

    public function setUp()
    {
        $this->cache = new ArrayCache();
    }

    /** @test */
    public function getShouldRejectPromiseForNonExistentKey()
    {
        $this->cache
            ->get('foo')
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableOnce()
            );
    }

    /** @test */
    public function setShouldSetKey()
    {
        $this->cache
            ->set('foo', 'bar');

        $success = $this->createCallableMock();
        $success
            ->expects($this->once())
            ->method('__invoke')
            ->with('bar');

        $this->cache
            ->get('foo')
            ->then($success);
    }

    /** @test */
    public function removeShouldRemoveKey()
    {
        $this->cache
            ->set('foo', 'bar');

        $this->cache
            ->remove('foo');

        $this->cache
            ->get('foo')
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableOnce()
            );
    }
}
