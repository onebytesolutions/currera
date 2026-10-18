<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Currera\Client;
use Currera\Sms;

$client = new Client(
    getenv('CURRERA_API_KEY') ?: '',
    getenv('CURRERA_BASE_URL') ?: ''
);

$sms = (new Sms())
    ->setTemplateId('YOUR_SMS_TEMPLATE_ID')
    ->addTo('+12025550123', 'Alex')
    ->addDynamicTemplateData('ORDER_NUMBER', 'A-1007');

try {
    $response = $client->sendSms($sms);
    echo $response->statusCode() . PHP_EOL;
    echo $response->body() . PHP_EOL;
} catch (\RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
