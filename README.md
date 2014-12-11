# React

Event-driven, non-blocking I/O with PHP.

[![Build Status](https://secure.travis-ci.org/reactphp/react.png?branch=master)](http://travis-ci.org/reactphp/react)

### Notice - (May 25th, 2014)

As of 2014-05-25 we have reversed roles of this and the component repositories. 
Instead of reactphp/react being the master code repository it is now the sum of React's parts. 
All PRs should be made against their corresponding repository found in [/reactphp](https://github.com/reactphp). 
All existing PRs will be evaluated and work will be done with the submitter to merge it into the proper component. 

## Install

The recommended way to install React is [through composer](http://getcomposer.org). Type the following command in your shell environment:

```
php ~/composer.phar require react/react:0.4.*
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

## High-level abstractions

There are two main abstractions that make dealing with control flow a lot more
manageable.

* **Stream:** A stream represents an I/O source (ReadableStream) or
  destination (WritableStream). These can be used to model pipes, similar
  to a unix pipe that is composed of processes. Streams represent very large
  values as chunks.

* **Promise:** A promise represents an eventual return value. Promises can be
  composed and are a lot easier to deal with than traditional CPS callback
  spaghetti and allow for almost sane error handling. Promises represent the
  computation for producing single values.

You should use these abstractions whenever you can.

## Usage

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
$http = new React\Http\Server($socket);

$http->on('request', $app);

$socket->listen(1337);
$loop->run();
```

## Documentation

Superficial documentation can be found in the README files of the individual
components. See `vendor/react/*/src/README.md`.

## Community

Check out #reactphp on irc.freenode.net. Also follow
[@reactphp](https://twitter.com/reactphp) on twitter.

## Tests

To run the test suite, you need install the dependencies via composer, then
run PHPUnit.

    $ composer install
    $ phpunit

## License

MIT, see LICENSE.
