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

$response = $client->updateSubscriberFields('customer@example.com', [
    'company' => 'Acme',
    'loyalty_points' => 120,
]);

echo $response->statusCode() . PHP_EOL;
echo $response->body() . PHP_EOL;
