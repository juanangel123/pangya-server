<?php

/**
 * This is the PangYa Login Server main entry point.
 */

use Clue\React\Stdio\Stdio;
use Nelexa\Buffer\StringBuffer;
use PangYa\LoginServer;
use PangYa\Util\Util;
use React\Socket\ConnectionInterface;
use React\Socket\Server as ReactServer;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Register the auto loader.
 */
require __DIR__.'/../vendor/autoload.php';

$dotEnv = new Dotenv();
$dotEnv->load(__DIR__.'/../.env');

$loginServer = new LoginServer($_ENV['LOGIN_SERVER_HOST'], $_ENV['LOGIN_SERVER_PORT']);

echo "PangYa Fresh UP! Server\n";
echo 'Login server running at http://'.$loginServer->getHost().':'.$loginServer->getPort()."\n";

//$socket = new ReactServer($_ENV['AUTH_CLIENT_HOST'].':'.$_ENV['AUTH_CLIENT_PORT'], $loginServer->getLoop());
//$socket->on('connection', function (ConnectionInterface $connection) {
//    echo 'Auth client connected: '.$connection->getRemoteAddress()."\n";
//
//    $connection->on('data', function (string $data) {
//        $buffer = new StringBuffer($data);
//
//        Util::showHex($buffer);
//    });
//});

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

$loginServer->getLoop()->run();

$loginServer->getLoop()->run();
