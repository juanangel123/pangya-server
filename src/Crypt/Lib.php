<?php

namespace Pangya\Crypt;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Pangya\Packet\Buffer as PangyaBuffer;

/**
 * Class used to encrypt and decrypt packets from the server-side.
 *
 * @link https://github.com/pangyatools/PangCrypt/blob/master/PangCrypt/ClientCipher.cs
 * @link https://github.com/pangyatools/PangCrypt/blob/master/PangCrypt/ServerCipher.cs
 *
 * @package Pangya\Crypt
 */
class Lib
{
    /**
     * @var int Minimum packet size.
     */
    public const MIN_PACKET_SIZE = 5;

    /**
     * Encrypt the packet provided.
     *
     * @param  Buffer  $buffer
     * @param  int  $key
     * @return PangyaBuffer
     */
    public function encrypt(Buffer $buffer, int $key): Buffer
    {
        return $buffer;
    }

    /**
     * Decrypt the packet provided.
     *
     * @param  Buffer  $buffer
     * @param  int  $key
     * @return PangyaBuffer
     * @throws BufferException
     */
    public function decrypt(Buffer $buffer, int $key): Buffer
    {
        $decrypted = new PangyaBuffer($buffer->toString());

        $decrypted->setPosition(self::MIN_PACKET_SIZE - 1)->putByte(Tables::CRYPT_TABLE_2[($key << 8) + $buffer->getUnsignedByte()]);

        for ($i = 8; $i < $buffer->size(); $i++) {
            $decrypted->setPosition($i)->putByte($buffer->setPosition($i - 4)->getUnsignedByte());
        }

        return $decrypted->rewind()->remove(self::MIN_PACKET_SIZE);
    }
}
