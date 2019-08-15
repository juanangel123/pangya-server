<?php

namespace Pangya;

use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;

/**
 * Class AuthClient
 *
 * @package Pangya
 */
class AuthClient
{
    /**
     * Execute the command.
     *
     * @param Client $client
     * @param  string  $command
     * @throws BufferException
     */
    public function execute(Client $client, string $command)
    {
        $buffer = new StringBuffer($command);

        echo "Data:\n";
        echo var_dump(bin2hex($buffer->toString())) . "\n";

        $client->processCommand($command);
    }
}
