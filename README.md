# React

Event-driven, non-blocking I/O with PHP.

[![Build Status](https://secure.travis-ci.org/react-php/react.png?branch=master)](http://travis-ci.org/react-php/react)

## Install

The recommended way to install react is [through composer](http://getcomposer.org).

```JSON
{
    "minimum-stability": "dev",
    "require": {
        "react/react": "0.2.*"
    }
}
```

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

$stack = new React\Espresso\Stack($app);
$stack->listen(1337);
```

## Community

Check out #reactphp on irc.freenode.net. Also follow [@reactphp](https://twitter.com/#!/reactphp) on twitter.

## Tests

To run the test suite, you need PHPUnit.

    $ phpunit

## License

MIT, see LICENSE.
