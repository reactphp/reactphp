<?php

namespace React\Espresso;

use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Stack extends Application
{
    public function __construct()
    {
        parent::__construct();

        $this['loop'] = $this->share(function () {
            return Factory::create();
        });

        $this['socket'] = $this->share(function ($app) {
            return new SocketServer($app['loop']);
        });

        $this['http'] = $this->share(function ($app) {
            return new HttpServer($app['socket']);
        });
    }

    public function listen($port, $host = '127.0.0.1')
    {
        $this['http']->on('request', $this);
        $this['socket']->listen($port, $host);
        $this['loop']->run();
    }
}
