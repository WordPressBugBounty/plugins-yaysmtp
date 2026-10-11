<?php

namespace YaySMTP;

// Don't redefine the functions if included multiple times.
if (!\function_exists('YaySMTP\GuzzleHttp\Promise\promise_for')) {
    require __DIR__ . '/functions.php';
}
