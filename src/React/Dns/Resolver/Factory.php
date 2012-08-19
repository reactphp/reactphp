<?php

namespace React\Dns\Resolver;

use React\Dns\Query\Executor;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\LoopInterface;

class Factory
{
    public function create($nameserver, LoopInterface $loop)
    {
        $nameserver = $this->addPortToServerIfMissing($nameserver);
        $executor = new Executor($loop, new Parser(), new BinaryDumper());

        return new Resolver($nameserver, $executor);
    }

    protected function addPortToServerIfMissing($nameserver)
    {
        return false === strpos($nameserver, ':') ? "$nameserver:53" : $nameserver;
    }
}
