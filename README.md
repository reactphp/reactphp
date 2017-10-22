<div align="center">
    <a href="https://reactphp.org"><img src="https://rawgit.com/reactphp/branding/master/reactphp-logo.svg" alt="ReactPHP Logo" width="160"></a>
</div>
    
<br>
    
<div align="center">
    <strong>Event-driven, non-blocking I/O with PHP.</strong>
</div>

<br>

<div align="center">
    <a href="https://travis-ci.org/reactphp/react"><img src="https://travis-ci.org/reactphp/react.svg?branch=master" alt="Build Status"></a>
</div>

<br>

ReactPHP is a low-level library for event-driven programming in PHP. At its core
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

ReactPHP is non-blocking by default. Use workers for blocking I/O.

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

* **ChildProcess**
  Library for executing child processes.
  [Read the documentation](https://github.com/reactphp/child-process)

* **PromiseTimer**
  Trivial timeout implementation for ReactPHP's Promise lib.
  [Read the documentation](https://github.com/reactphp/promise-timer)

* **PromiseStream**
  The missing link between Promise-land and Stream-land, built on top of ReactPHP.
  [Read the documentation](https://github.com/reactphp/promise-stream)

## Getting started

ReactPHP consists of individual components.
This means that instead of installing something like a "ReactPHP framework", you actually
pick only the components that you need.

The recommended way to install these components is [through Composer](http://getcomposer.org).
[New to Composer?](http://getcomposer.org/doc/00-intro.md)

For example, this may look something like this:

```bash
$ composer require react/event-loop react/http
```

For more details, check out [ReactPHP's homepage](https://reactphp.org) for
quickstart examples and usage details.

## Documentation

Superficial documentation can be found in the README files of the individual
components. See `vendor/react/*/src/README.md`.

## Community

Check out #reactphp on irc.freenode.net. Also follow
[@reactphp](https://twitter.com/reactphp) on twitter.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

MIT, see LICENSE.
