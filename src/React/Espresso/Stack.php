<?php

namespace React\Espresso;

use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Stack extends \Pimple
{
    public function __construct($app)
    {
        $this['loop'] = $this->share(function () {
            return Factory::create();
        });

        $this['socket'] = $this->share(function ($stack) {
            return new SocketServer($stack['loop']);
        });

        $this['http'] = $this->share(function ($stack) {
            return new HttpServer($stack['socket']);
        });

        $isFactory = is_object($app) && method_exists($app, '__invoke');
        $this['app'] = $isFactory ? $this->protectService($app) : $app;
    }

    public function listen($port, $host = '127.0.0.1')
    {
        $this['http']->on('request', $this['app']);
        $this['socket']->listen($port, $host);
        $this['loop']->run();
    }

    // Pimple::protect minus the type hint
    public function protectService($callable)
    {
        return function ($c) use ($callable) {
            return $callable;
        };
    }
}
