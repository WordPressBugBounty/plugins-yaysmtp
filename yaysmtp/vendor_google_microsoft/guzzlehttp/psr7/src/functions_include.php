<?php

namespace YaySMTP;

// Don't redefine the functions if included multiple times.
if (!\function_exists('YaySMTP\GuzzleHttp\Psr7\str')) {
    require __DIR__ . '/functions.php';
}
