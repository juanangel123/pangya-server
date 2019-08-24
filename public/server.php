<?php

/**
 * This is the PangYa Login Server main entry point.
 */

use PangYa\GameServer;
use PangYa\LoginServer;
use PangYa\MessengerServer;
use PangYa\Server;
use React\EventLoop\Factory;
use Symfony\Component\Dotenv\Dotenv;

// Register the auto loader.
require __DIR__.'/../vendor/autoload.php';

// Set timezone.
date_default_timezone_set('Europe/Madrid');

// Init env variables.
$dotEnv = new Dotenv();
$dotEnv->load(__DIR__.'/../.env');

// Create the loop.
$loop = Factory::create();

/** @var array|Server[] $servers */
$servers = [];
$servers[] = new LoginServer($_ENV['LOGIN_SERVER_HOST'], $_ENV['LOGIN_SERVER_PORT'], $loop);
$servers[] = new GameServer($_ENV['GAME_SERVER_HOST'], $_ENV['GAME_SERVER_PORT'], $loop);
$servers[] = new MessengerServer($_ENV['MESSENGER_SERVER_HOST'], $_ENV['MESSENGER_SERVER_PORT'], $loop);


// https://github.com/spatie/phpunit-watcher/issues/82
//$stdio = new Stdio($loginServer->getLoop());
//
//$stdio->setPrompt('Input > ');
//
//$stdio->on('data', function ($line) use ($stdio) {
//    $line = rtrim($line, "\r\n");
//    $stdio->write('Your input: ' . $line . PHP_EOL);
//
//    if ($line === 'quit') {
//        $stdio->end();
//    }
//});

echo "PangYa Fresh UP! Server\n";
foreach ($servers as $server) {
    echo $server->getName().' running at http://'.$server->getHost().':'.$server->getPort()."\n";
}

$loop->run();
