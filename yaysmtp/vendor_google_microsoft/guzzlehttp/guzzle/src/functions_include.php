<?php

namespace YaySMTP;

// Don't redefine the functions if included multiple times.
if (!\function_exists('YaySMTP\GuzzleHttp\uri_template')) {
    require __DIR__ . '/functions.php';
}
