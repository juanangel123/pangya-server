<?php

/**
 * This is the PangYa Login Server main entry point.
 */

use PangYa\GameServer;
use PangYa\LoginServer;
use PangYa\MessengerServer;
use PangYa\Server;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Socket\Server as ReactServer;
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

echo "PangYa Fresh UP! Server\n";
foreach ($servers as $server) {
    echo $server->getName().' running at http://'.$server->getHost().':'.$server->getPort()."\n";
}

// TODO
$socket = new ReactServer('127.0.0.1:50009', $loop);
$httpSocket = new HttpServer(static function (ServerRequestInterface $request) {
    return new Response(
        200,
        array(
            'Content-Type' => 'text/plain'
        ),
        file_get_contents(__DIR__.'/../public/Translation/Read.aspx'),
    );
});
$httpSocket->listen($socket);

$socket = new ReactServer('127.0.0.1:8080', $loop);
$httpSocket = new HttpServer(static function (ServerRequestInterface $request) {
    return new Response(
        200,
        array(
            'Content-Type' => 'text/plain'
        ),
        file_get_contents(__DIR__.'/../public/updatelist'),
    );
});
$httpSocket->listen($socket);

// Run the loop.
$loop->run();

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
