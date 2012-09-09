<?php

// http client making a request to github api

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$client = new React\HttpClient\Client($loop);

$request = $client->request('GET', 'https://api.github.com/repos/react-php/react/commits');
$request->on('response', function ($response) {
    $buffer = '';

    $response->on('data', function ($data) use (&$buffer) {
        $buffer .= $data;
        echo ".";
    });

    $response->on('end', function () use (&$buffer) {
        $decoded = json_decode($buffer, true);
        $latest = $decoded[0]['commit'];
        $author = $latest['author']['name'];
        $date = date('F j, Y', strtotime($latest['author']['date']));

        echo "\n";
        echo "Latest commit on react was done by {$author} on {$date}\n";
        echo "{$latest['message']}\n";
    });
});
$request->end();

$loop->run();
