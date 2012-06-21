<?php

namespace React\Tests\Espresso;

use React\Espresso\Application;
use React\Espresso\Stack;
use React\Http\Request;
use React\Http\Response;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationWithGetRequest()
    {
        $app = new Application();

        $app->get('/', function ($request, $response) {
            $response->writeHead(200, array('Content-Type' => 'text/plain'));
            $response->end("Hello World\n");
        });

        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->at(0))
            ->method('write')
            ->with($this->stringContains("text/plain"));
        $conn
            ->expects($this->at(1))
            ->method('write')
            ->with($this->stringContains("Hello World\n"));

        $request = new Request('GET', '/');
        $response = new Response($conn);

        $app($request, $response);
    }
}
