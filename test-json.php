<?php

$json = file_get_contents('database/data/salon.json');
$data = json_decode($json, true);

if ($data === null) {
    echo 'JSON decode returned null' . PHP_EOL;
    echo 'File size: ' . strlen($json) . ' bytes' . PHP_EOL;
    echo 'Last error: ' . json_last_error_msg() . PHP_EOL;
} else {
    echo 'JSON is valid, array has ' . count($data) . ' items' . PHP_EOL;
}
