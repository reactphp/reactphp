<?php

foreach (glob(__DIR__ . '/../vendor/react/*/tests/bootstrap.php') as $b) {
    include $b;
}
