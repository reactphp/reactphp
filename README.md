# React

Event-driven, non-blocking I/O with PHP.

[![Build Status](https://secure.travis-ci.org/react-php/react.png?branch=master)](http://travis-ci.org/react-php/react)

## Install

The recommended way to install react is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "react/react": "0.2.*"
    }
}
```

## What is it?

React is a low-level library for event-driven programming in PHP. At its core
is an event loop, on top of which it  provides low-level utilities, such as:
Streams abstraction, async dns resolver, network client/server, http
client/server, interaction with processes. Third-party libraries can use these
components to create async network clients/servers and more.

The event loop is based on the reactor pattern (hence the name) and strongly
inspired by libraries such as EventMachine (Ruby), Twisted (Python) and
Node.js (V8).

## Design goals

* Usable with a bare minimum of PHP extensions, add more extensions to get better performance.
* Provide a standalone event-loop component that can be re-used by other libraries.
* Decouple parts so they can be replaced by alternate implementations.

React is non-blocking by default. Use workers for blocking I/O.

## Usage

### Events

Most classes extend
[événement](https://github.com/igorw/evenement), allowing you to bind to
events.

### Example

Here is an example of a simple HTTP server listening on port 1337:
```php
<?php

$i = 0;

$app = function ($request, $response) use (&$i) {
    $i++;

    $text = "This is request number $i.\n";
    $headers = array('Content-Type' => 'text/plain');

    $response->writeHead(200, $headers);
    $response->end($text);
};

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket, $loop);

$http->on('request', $app);

$socket->listen(1337);
$loop->run();
```

## Community

Check out #reactphp on irc.freenode.net. Also follow [@reactphp](https://twitter.com/#!/reactphp) on twitter.

## Tests

To run the test suite, you need PHPUnit.

    $ phpunit

## License

MIT, see LICENSE.
