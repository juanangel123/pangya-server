<?php

/**
 * This is the Pangya Login Server.
 */

use Pangya\Client;

$host = '127.0.0.1';
$port = '10103';

/**
 * Register the auto loader.
 */
require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($host.':'.$port, $loop);

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) {
    echo 'Client connected: '. $connection->getRemoteAddress() . "\n";

    $client = new Client($connection);
    $client->connect();

    $connection->on('data', function ($data) use ($connection) {
        echo 'Data: ' . $data;
        //$connection->close();
    });
});

echo 'Server running at http://'.$host.':'.$port."\n";

$loop->run();
