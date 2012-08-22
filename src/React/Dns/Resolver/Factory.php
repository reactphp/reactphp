<?php

namespace React\Dns\Resolver;

use React\Dns\Query\Executor;
use React\Dns\Query\CachedExecutor;
use React\Dns\Query\RecordCache;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\LoopInterface;

class Factory
{
    public function create($nameserver, LoopInterface $loop)
    {
        $nameserver = $this->addPortToServerIfMissing($nameserver);
        $executor = $this->createExecutor($nameserver, $loop);

        return new Resolver($nameserver, $executor);
    }

    public function createCached($nameserver, LoopInterface $loop)
    {
        $nameserver = $this->addPortToServerIfMissing($nameserver);
        $executor = $this->createCachedExecutor($nameserver, $loop);

        return new Resolver($nameserver, $executor);
    }

    protected function createExecutor($nameserver, LoopInterface $loop)
    {
        return new Executor($loop, new Parser(), new BinaryDumper());
    }

    protected function createCachedExecutor($nameserver, LoopInterface $loop)
    {
        return new CachedExecutor($this->createExecutor(), new RecordCache());
    }

    protected function addPortToServerIfMissing($nameserver)
    {
        return false === strpos($nameserver, ':') ? "$nameserver:53" : $nameserver;
    }
}
