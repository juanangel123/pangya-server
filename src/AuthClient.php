<?php

namespace Pangya;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use Pangya\Crypt\Lib;

/**
 * Class AuthClient
 *
 * @package Pangya
 */
class AuthClient
{
    /**
     * @var Lib
     */
    protected $crypt;

    /**
     * AuthClient constructor.
     */
    public function __construct()
    {
        $this->crypt = new Lib();
    }

    /**
     * Execute the command.
     *
     * @param  Client  $client
     * @param  string  $command
     * @throws BufferException
     */
    public function execute(Client $client, string $command): void
    {
        $buffer = new StringBuffer($command);
        $buffer->insertString($command);

        $size = $buffer->setPosition(1)->getUnsignedByte() + 4;
        $buffer->rewind();

        while ($buffer->remaining() >= $size) {
            if (!$client->securityCheck($buffer)) {
                $client->disconnect();

                return;
            }

            $decrypted = $this->crypt->decrypt(new StringBuffer($buffer->getString($size)));
        }

        dump('correct!');
        $client->disconnect();
    }
}
