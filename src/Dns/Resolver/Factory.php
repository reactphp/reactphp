<?php

namespace React\Dns\Resolver;

use React\Cache\ArrayCache;
use React\Dns\Query\Executor;
use React\Dns\Query\CachedExecutor;
use React\Dns\Query\RecordCache;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\LoopInterface;
use React\Dns\Query\RetryExecutor;
use React\Socket\AddressFactory;
use React\Socket\RemoteAddressInterface;

class Factory
{
    public function create($nameserver, LoopInterface $loop)
    {
        $address = AddressFactory::create($nameserver);

        if ($address->getPort() === null) {
            $address->setPort(53);
        }

        $executor = $this->createRetryExecutor($loop);

        return new Resolver($address, $executor);
    }

    public function createCached($nameserver, LoopInterface $loop)
    {
        $address = AddressFactory::create($nameserver);

        if ($address->getPort() === null) {
            $address->setPort(53);
        }

        $executor = $this->createCachedExecutor($loop);

        return new Resolver($address, $executor);
    }

    protected function createExecutor(LoopInterface $loop)
    {
        return new Executor($loop, new Parser(), new BinaryDumper());
    }

    protected function createRetryExecutor(LoopInterface $loop)
    {
        return new RetryExecutor($this->createExecutor($loop));
    }

    protected function createCachedExecutor(LoopInterface $loop)
    {
        return new CachedExecutor($this->createRetryExecutor($loop), new RecordCache(new ArrayCache()));
    }
}
