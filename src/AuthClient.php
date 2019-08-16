<?php

namespace Pangya;

use Nelexa\Buffer\Buffer;
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
    public function execute(Client $client, string $command): void
    {
        $buffer = new StringBuffer($command);
        $buffer->insertString($command);

        echo "Data:\n";
        echo var_dump(bin2hex($buffer->toString())) . "\n";

        $securityCheck = $client->securityCheck($buffer);

        if (!$securityCheck) {
            echo "NO SECURITY CHECK";
            return;
        }
    }
}
