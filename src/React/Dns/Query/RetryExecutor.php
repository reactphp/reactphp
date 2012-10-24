<?php

namespace React\Dns\Query;

use React\Promise\Deferred;

class RetryExecutor implements ExecutorInterface
{
    private $executor;
    private $cache;
    private $retries;

    public function __construct(ExecutorInterface $executor, $retries = 2)
    {
        $this->executor = $executor;
        $this->retries = $retries;
    }

    public function query($nameserver, Query $query)
    {
        $deferred = new Deferred();
        $this->tryQuery($nameserver, $query, $deferred->resolver(), $this->retries);

        return $deferred->promise();
    }

    public function tryQuery($nameserver, Query $query, $resolver, $retries)
    {
        $that = $this;
        $errorback = function ($error) use ($nameserver, $query, $resolver, $retries, $that) {
            if (!$error instanceof TimeoutException) {
                $resolver->reject($error);
                return;
            }
            if (0 >= $retries) {
                $error = new \RuntimeException(
                    sprintf("DNS query for %s failed: too many retries", $query->name),
                    0,
                    $error
                );
                $resolver->reject($error);
                return;
            }
            $that->tryQuery($nameserver, $query, $resolver, $retries-1);
        };

        return $this->executor
            ->query($nameserver, $query)
            ->then(array($resolver, 'resolve'), $errorback);
    }
}
