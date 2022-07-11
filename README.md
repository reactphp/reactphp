<div align="center">
    <a href="https://reactphp.org"><img src="https://rawgit.com/reactphp/branding/master/reactphp-logo.svg" alt="ReactPHP Logo" width="160"></a>
</div>
    
<br>
    
<div align="center">
    <strong>Event-driven, non-blocking I/O with PHP.</strong>
</div>

<br>

<div align="center">
    <a href="https://github.com/reactphp/reactphp/actions"><img src="https://github.com/reactphp/reactphp/actions/workflows/ci.yml/badge.svg" alt="Build Status"></a>
</div>

<br>

ReactPHP is a low-level library for event-driven programming in PHP. At its core
is an event loop, on top of which it provides low-level utilities, such as:
Streams abstraction, async DNS resolver, network client/server, HTTP
client/server and interaction with processes. Third-party libraries can use these
components to create async network clients/servers and more.

```php
<?php

// $ composer require react/http react/socket # install example using Composer
// $ php example.php # run example on command line, requires no additional web server

require __DIR__ . '/vendor/autoload.php';

$server = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    return React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
});

$socket = new React\Socket\SocketServer('127.0.0.1:8080');
$server->listen($socket);

echo "Server running at http://127.0.0.1:8080" . PHP_EOL;
```

This simple web server written in ReactPHP responds with "Hello World!" for every request.

ReactPHP is production ready and battle-tested with millions of installations
from all kinds of projects around the world. Its event-driven architecture makes
it a perfect fit for efficient network servers and clients handling hundreds or
thousands of concurrent connections, long-running applications and many other
forms of cooperative multitasking with non-blocking I/O operations. What makes
ReactPHP special is its vivid ecosystem with hundreds of third-party libraries
allowing you to integrate with many existing systems, such as common network
services, database systems and other third-party APIs.

* **Production ready** and battle-tested.
* **Rock-solid** with stable long-term support (LTS) releases.
* **Requires no extensions** and runs on any platform - no excuses!
* Takes advantage of **optional extensions** to get better performance when available.
* **Highly recommends latest version of PHP 7+** for best performance and support.
* **Supports legacy PHP 5.3+ and HHVM** for maximum compatibility.
* **Well designed** and **reusable components**.
* **Decoupled parts** so they can be replaced by alternate implementations.
* Carefully **tested** (unit & functional).
* Promotes **standard PSRs** where possible for maximum interoperability.
* Aims to be **technology neutral**, so you can use your preferred application stack.
* Small **core team of professionals** supported by **large network** of outside contributors.

ReactPHP is non-blocking by default. Use workers for blocking I/O.
The event loop is based on the reactor pattern (hence the name) and strongly
inspired by libraries such as EventMachine (Ruby), Twisted (Python) and
Node.js (V8).

> This repository you're currently looking at is mostly used as a meta
  repository to discuss and plan all things @ReactPHP. See the individual
  components linked below for more details about each component, its
  documentation and source code.

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

* **Async**
  Async utilities and fibers for ReactPHP.
  [Read the documentation](https://github.com/reactphp/async)

## Network Components

* **Socket**
  Async, streaming plaintext TCP/IP and secure TLS socket server and client connections for ReactPHP.
  [Read the documentation](https://github.com/reactphp/socket)

* **Datagram**
  Event-driven UDP client and server sockets for ReactPHP.
  [Read the documentation](https://github.com/reactphp/datagram)

* **HTTP**
  Event-driven, streaming HTTP client and server implementation for ReactPHP.
  [Read the documentation](https://github.com/reactphp/http)

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

## Built with ReactPHP

* **Thruway**
  PHP Client and Router Library for Autobahn and WAMP (Web Application Messaging
  Protocol) for Real-Time Application Messaging
  [voryx/Thruway](https://github.com/voryx/Thruway)

* **PPM - PHP Process Manager**
  PPM is a process manager, supercharger and load balancer for modern PHP
  applications.
  [php-pm/php-pm](https://github.com/php-pm/php-pm)

* **php-ar-drone**
  🚁 Port of node-ar-drone which allows user to control a Parrot AR Drone over
  PHP
  [jolicode/php-ar-drone](https://github.com/jolicode/php-ar-drone)

* **Ratchet**
  Asynchronous WebSocket server
  [ratchetphp/Ratchet](https://github.com/ratchetphp/Ratchet)

* **Predis\Async**
  Asynchronous PHP client library for Redis built on top of ReactPHP
  [nrk/predis-async](https://github.com/nrk/predis-async)

* **clue/redis-server**
  A Redis server implementation in pure PHP
  [clue/redis-server](https://github.com/clue/php-redis-server)

[And many more on our wiki page »](https://github.com/reactphp/react/wiki/Users)

## Articles

* **Sergey Zhuk**
  A series of articles covering ReactPHP: from the basics to the real
  application examples.
  [sergeyzhuk.me](http://sergeyzhuk.me/reactphp-series)

* **Cees-Jan Kiewiet**
  Blog series about several ReactPHP components and how they work.
  [blog.wyrihaximus.net](http://blog.wyrihaximus.net/categories/reactphp-series/)

* **Loïc Faugeron**
  Super Speed Symfony - ReactPHP.
  [gnugat.github.io](https://gnugat.github.io/2016/04/13/super-speed-sf-react-php.html)

* **Marc J. Schmidt**
  Bring High Performance Into Your PHP App (with ReactPHP).
  [marcjschmidt.de](http://marcjschmidt.de/blog/2014/02/08/php-high-performance.html)
  
* **Marc Morera**
  When ReactPHP meet Symfony
  [medium.com/@apisearch](https://medium.com/@apisearch/symfony-and-reactphp-series-82082167f6fb)

## Talks

* **Christian Lück**
  [Pushing the limits with ReactPHP](https://www.youtube.com/watch?v=-5ZdGUvOqx4)

* **Jeremy Mikola**
  [Async PHP With React](https://www.youtube.com/watch?v=s6xrnYae1FU)

* **Igor Wiedler**
  [Event-driven PHP](https://www.youtube.com/watch?v=MWNcItWuKpI)

## Getting started

ReactPHP consists of a set of individual [components](#core-components).
This means that instead of installing something like a "ReactPHP framework", you actually
pick only the components that you need.

This project follows [SemVer](https://semver.org/) for all its stable components.
The recommended way to install these components is [through Composer](https://getcomposer.org/).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

For example, this may look something like this:

```bash
# recommended install: pick required components
composer require react/event-loop react/http
```

As an alternative, we also provide a meta package that will install all stable
components at once. Installing this is only recommended for quick prototyping,
as the list of stable components may change over time. This meta package can be
installed like this:

```bash
# quick protoyping only: install all stable components
composer require react/react:^1.3
```

For more details, check out [ReactPHP's homepage](https://reactphp.org/) for
quickstart examples and usage details.

See also the combined [changelog for all ReactPHP components](https://reactphp.org/changelog.html)
for details about version upgrades.

## Support

Do you have a question and need help with ReactPHP? Don't worry, we're here to help!

As a first step, check the elaborate documentation that comes with each
component (see links to individual documentation for each component above).
If you find your question is not answered within the documentation, there's a
fair chance that it may be relevant to more people. Please do not hesitate to
file your question as an issue in the relevant component so others can also
participate.

You can also check out our official [Gitter chat room](https://gitter.im/reactphp/reactphp).
Most of the people involved in this project are available in this chat room, so many
questions get answered in a few minutes to some hours. We also use this chat room
to announce all new releases and ongoing development efforts, so consider
staying in this chat room for a little longer.

Also follow [@reactphp](https://twitter.com/reactphp) on Twitter for updates.
We use this mostly for noteworthy, bigger updates and to keep the community
updated about ongoing development efforts. You can always use the `#reactphp`
hashtag if you have anything to share!

We're a very open project and we prefer public communication whenever possible,
so that more people can participate and help getting the best solutions available.
At the same time, we realize that some things are better addressed in private.
Whether you just want to say *thank you*, want to report a security issue or
want to help sponsor a certain feature development, you can reach out to the
core team in private by sending an email to `support@reactphp.org`. Please keep in
mind that we're a small team of volunteers and do our best to support anybody
reaching out.

Do you want to support ReactPHP? Awesome! Let's start with letting the the world
know why you think ReactPHP is awesome and try to help others getting on board!
Send a tweet, write a blog post, give a talk at your local user group or
conference or even write a book. There are many ways you can help. You can
always reach out to us in private and help others in our support channels.
Thank you!

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org/):

```bash
composer install
```

To run the test suite, go to the project root and run:

```bash
vendor/bin/phpunit
```

The test suite also contains a number of functional integration tests that rely
on a stable internet connection. Due to the vast number of integration tests,
these are skipped by default during CI runs. If you also do not want to run these,
they can simply be skipped like this:

```bash
vendor/bin/phpunit --exclude-group internet
```

## License

MIT, see LICENSE.
