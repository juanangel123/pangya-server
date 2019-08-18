<?php

/**
 * This is the PangYa Login Server main entry point.
 */

use PangYa\LoginServer;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Register the auto loader.
 */
require __DIR__.'/../vendor/autoload.php';

$dotEnv = new Dotenv();
$dotEnv->load(__DIR__.'/../.env');

$loginServer = new LoginServer($_ENV['LOGIN_SERVER_HOST'], $_ENV['LOGIN_SERVER_PORT']);

echo "PangYa Fresh UP! Login Server\n";
echo 'Server running at http://'.$loginServer->getHost().':'.$loginServer->getPort()."\n";

$loginServer->getLoop()->run();
