<?php

/**
 * This is the Pangya Login Server main entry point.
 */

use Pangya\LoginServer;
use Pangya\Util\Util;

/**
 * Register the auto loader.
 */
require __DIR__.'/../vendor/autoload.php';

$loginServer = new LoginServer('127.0.0.1', Util::PANGYA_US_SERVER_LOGIN_PORT);

echo "Pangya Fresh UP! Login Server\n";
echo 'Server running at http://'.$loginServer->getHost().':'.$loginServer->getPort()."\n";

$loginServer->getLoop()->run();
