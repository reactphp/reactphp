# React

Event-driven, non-blocking I/O with PHP.

[![Build Status](https://secure.travis-ci.org/reactphp/react.png?branch=master)](http://travis-ci.org/reactphp/react)

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

## Core Components

* **EventLoop**
  ReactPHP's core reactor event-loop.
  [Read the documentation](https://github.com/reactphp/event-loop)

* **Stream**
  Event-driven readable and writable streams for non-blocking I/O in ReactPHP.
  [Read the documentation](https://github.com/reactphp/stream)

* **Promise**
  Promises/A implementation for PHP.
  [Read the documentation](https://github.com/reactphp/promise)


## Network Components

* **Socket**
  Async, streaming plaintext TCP/IP and secure TLS socket server and client connections for ReactPHP.
  [Read the documentation](https://github.com/reactphp/socket)

* **Datagram**
  Event-driven UDP client and server sockets for ReactPHP.
  [Read the documentation](https://github.com/reactphp/datagram)

## Protocol Components

* **HTTP**
  Event-driven, streaming plaintext HTTP and secure HTTPS server for ReactPHP.
  [Read the documentation](https://github.com/reactphp/http)

* **HTTPClient**
  Event-driven, streaming HTTP client for ReactPHP.
  [Read the documentation](https://github.com/reactphp/http-client)

* **DNS**
  Async DNS resolver for ReactPHP.
  [Read the documentation](https://github.com/reactphp/dns)

## Utility Components

* **Cache**
  Async caching for ReactPHP.
  [Read the documentation](https://github.com/reactphp/cache)

* **PromiseTimer**
  Trivial timeout implementation for ReactPHP's Promise lib.
  [Read the documentation](https://github.com/reactphp/promise-timer)

* **ChildProcess**
  Library for executing child processes.
  [Read the documentation](https://github.com/reactphp/child-process)

## Getting started

React consists of individual components.
This means that instead of installing something like a "React framework", you actually
pick only the components that you need.

The recommended way to install these components is [through Composer](http://getcomposer.org).
[New to Composer?](http://getcomposer.org/doc/00-intro.md)

For example, this may look something like this:

```bash
$ composer require react/event-loop react/http
```

For more details, check out [React's homepage](http://reactphp.org) for
quickstart examples and usage details.

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
