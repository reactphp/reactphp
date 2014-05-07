<?php

foreach (glob(__DIR__ . '/../src/*/tests/bootstrap.php') as $b) {
    include $b;
}
