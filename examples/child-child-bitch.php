<?php

    require __DIR__ . '/../vendor/autoload.php';

    $i = 0;

    // block all the things!
    while (true) {
        echo ++$i . "\n";

        sleep(1);
    }
