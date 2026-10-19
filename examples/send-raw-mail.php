<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Currera\Client;
use Currera\Mail;

$client = new Client(
    getenv('CURRERA_API_KEY') ?: '',
    getenv('CURRERA_BASE_URL') ?: ''
);

$mail = (new Mail())
    ->setSubject('Order {{ORDER_NUMBER}}')
    ->setHtml('<p>Hello {{NAME}}, your order is {{ORDER_NUMBER}}.</p>')
    ->setText('Hello {{NAME}}, your order is {{ORDER_NUMBER}}.')
    ->addTo('customer@example.com', 'Alex')
    ->addDynamicTemplateData('ORDER_NUMBER', 'A-1007');

try {
    $response = $client->send($mail);
    echo $response->statusCode() . PHP_EOL;
    echo $response->body() . PHP_EOL;
} catch (\Currera\Exception\ApiException $exception) {
    fwrite(STDERR, 'Currera API error (' . $exception->statusCode() . '): ' . $exception->getMessage() . PHP_EOL);
    // The original response includes any accepted and failed recipients.
    // Do not blindly retry a partial send.
    exit(1);
} catch (\Exception $exception) {
    // A transport failure does not prove the message was not sent; do not retry blindly.
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
