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
    ->setTemplateId('YOUR_EMAIL_TEMPLATE_ID')
    ->setFrom('receipts@example.com', 'Receipts')
    ->setReplyTo('support@example.com')
    ->setSubject('Order {{ORDER_NUMBER}}')
    ->addTo('customer@example.com', 'Alex', ['FIRST_NAME' => 'Alex'])
    ->addDynamicTemplateData('ORDER_NUMBER', 'A-1007')
    ->setTracking(true, true);

try {
    $response = $client->send($mail);
    echo $response->statusCode() . PHP_EOL;
    echo $response->body() . PHP_EOL;
} catch (\RuntimeException $exception) {
    // Do not retry blindly: a transport failure may occur after a send.
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
