<?php

namespace React\Functional;

use React\EventLoop\LibEventLoop;

class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    public function getEventLoop()
    {
        $loop = new LibEventLoop();
        $this->addTestTimeout($loop);

        return $loop;
    }

    public function addTestTimeout($loop, $timeout = 2)
    {
        $phpunit = $this;
        $loop->addTimer($timeout, function () use ($loop, $phpunit) {
            $loop->stop();
            $phpunit->fail('Test timeout reached');
        });
    }

    public function getSuccessfulCallbackThatStopsLoop($loop, $callback = null)
    {
        return function () use ($loop, $callback) {
            if (is_callable($callback)) {
                call_user_func_array($callback, func_get_args());
            }
            $loop->stop();
        };
    }

    public function getErroredCallbackThatStopsLoop($loop, $callback = null)
    {
        $phpunit = $this;
        return function ($error) use ($loop, $phpunit, &$callback) {
            if (is_callable($callback)) {
                call_user_func($callback);
            }
            $loop->stop();
            $phpunit->fail($error->getMessage());
        };
    }
}
