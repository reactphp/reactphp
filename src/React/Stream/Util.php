<?php

namespace React\Stream;

// TODO: move to a trait

class Util
{
    public static function pipe(ReadableStreamInterface $source, WritableStreamInterface $dest, array $options = array())
    {
        // TODO: use stream_copy_to_stream
        // it is 4x faster than this
        // but can lose data under load with no way to recover it

        $dest->emit('pipe', array($source));

        $source->on('data', function ($data) use ($source, $dest) {
            $feedMore = $dest->write($data);

            if (false === $feedMore) {
                $source->pause();
            }
        });

        $dest->on('drain', function () use ($source) {
            $source->resume();
        });

        $end = isset($options['end']) ? $options['end'] : true;
        if ($end && $source !== $dest) {
            $source->on('end', function () use ($dest) {
                $dest->end();
            });
        }
    }

    public static function forwardEvents($source, $target, array $events)
    {
        foreach ($events as $event) {
            $source->on($event, function () use ($event, $target) {
                $target->emit($event, func_get_args());
            });
        }
    }
}
