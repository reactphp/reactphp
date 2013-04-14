<?php

class Bench {
    private $prev;

    public function start() {
        $this->prev = microtime(true);
    }

    public function snap() {
        $prev = $this->prev;

        $current = microtime(true);
        $this->prev = $current;

        return $current - $prev;
    }

    public function printSnap($name) {
        printf("%-40s: %s s\n", $name, $this->snap());
    }
}

function benchLoops(array $tests) {
    $loops = array(
        'StreamSelectLoop',
        'LibEventLoop',
        'LibEvLoop',
        // 'LibUvLoop',
    );

    foreach ($tests as $testName => $test) {
        foreach ($loops as $loopName) {
            $loopClass = "React\\EventLoop\\$loopName";
            $loop = new $loopClass();

            $bench = new Bench();
            $bench->start();

            $test($loop);

            $bench->printSnap("$loopName: $testName");
        }

        printf("----------------------------------------\n");
    }
}
