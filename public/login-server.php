<?php

/**
 * This is the Pangya Login Server.
 */

use Pangya\AuthClient;
use Pangya\Client;
use Pangya\Util\Util;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

/**
 * Register the auto loader.
 */
require __DIR__.'/../vendor/autoload.php';

$host = '127.0.0.1';
$port = Util::PANGYA_US_SERVER_LOGIN_PORT;

$loop = Factory::create();
$socket = new Server($host.':'.$port, $loop);

// TODO
$authClient = new AuthClient();

$socket->on('connection', static function (ConnectionInterface $connection) use ($authClient) {
    $client = new Client($connection);
    $client->connect();

    echo 'Client connected: '.$connection->getRemoteAddress().' - ID: '.$client->getId()."\n";

    $connection->on('data', static function (string $data) use ($authClient, $client) {
        $authClient->execute($client, $data);
    });
    $connection->on('end', static function () use ($client) {
        echo 'Client: '.$client->getId()." has end the connection\n";
    });
    $connection->on('error', static function (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";
    });
    $connection->on('close', static function () use ($client) {
        echo 'Client: '.$client->getId()." has disconnected\n";
    });
});

// This is not working since it is not doing both handling the connection and the input.
//try {
//    $loop->addReadStream(fopen('php://stdin', 'rb'), static function ($stream) {
//        $line = fgets($stream);
//        if (!$line) {
//            return;
//        }
//
//        echo 'Input: '.$line;
//    });
//} catch (Exception $e) {
//    echo "Can't get the stdin input stream\n";
//}

echo "Pangya Fresh UP! Login Server\n";
echo 'Server running at http://'.$host.':'.$port."\n";

$loop->run();
