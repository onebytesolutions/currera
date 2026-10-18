<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$client = new \Currera\Client(
    getenv('CURRERA_API_KEY') ?: '',
    getenv('CURRERA_BASE_URL') ?: ''
);

$response = $client->subscribe('a7c93e2b8f0146d5', 'customer@example.com', [
    'first_name' => 'Alex',
    'last_name' => 'Smith',
    'bypass_double_opt_in' => false,
]);

echo $response->statusCode() . PHP_EOL;
echo $response->body() . PHP_EOL;
