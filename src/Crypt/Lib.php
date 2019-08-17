<?php

namespace Pangya\Crypt;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use Pangya\Packet\Buffer as PangyaBuffer;
use Pangya\Util\LZO;

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
     * @param  Buffer  $buffer  The decrypted packet data.
     * @param  int  $key  Key to encrypt with.
     * @param  int  $salt  Byte Random salt value to encrypt with.
     * @return Buffer The encrypted packet data.
     * @throws BufferException
     */
    public function encrypt(Buffer $buffer, int $key, int $salt): Buffer
    {
        $index = ($key << 8) + $salt;
        $encrypted = new StringBuffer();
        $pLength = $buffer->size() + self::MIN_PACKET_SIZE;

        $u = $buffer->size();
        $x = ($u + $u / 255) & 0xff;
        $v = ($u - $x) / 255;
        $y = ($v + $v / 255) & 0xff;
        $w = ($v - $y) / 255;
        $z = ($w + $w / 255) & 0xff;

        $encrypted->insertByte($salt);
        $encrypted->insertByte(($pLength >> 0) & 0xff);
        $encrypted->insertByte(($pLength >> 8) & 0xff);
        $encrypted->insertByte(Tables::CRYPT_TABLE_1[$index] ^ Tables::CRYPT_TABLE_2[$index]);
        $encrypted->insertByte(0); // To be filled after.
        $encrypted->insertByte($z);
        $encrypted->insertByte($y);
        $encrypted->insertByte($x);

        // Insert compressed string.
        $encrypted->insertString(LZO::compress1X1($buffer->rewind()->getArrayBytes($buffer->toString())));

        for ($i = $buffer->size() - 1; $i >= 10; $i--) {
            $encrypted->setPosition($i)->putByte($buffer->getUnsignedByte() ^ $buffer->setPosition($i - 4)->getUnsignedByte());
        }

        return $encrypted->setPosition(7)->putByte($buffer->getUnsignedByte() ^ Tables::CRYPT_TABLE_2[$index])->rewind();
    }

    /**
     * Decrypt the packet provided.
     *
     * @param  Buffer  $buffer  The encrypted packet data.
     * @param  int  $key  Key to decrypt with.
     * @return PangyaBuffer The decrypted packet data.
     * @throws BufferException
     */
    public function decrypt(Buffer $buffer, int $key): Buffer
    {
        $decrypted = new PangyaBuffer($buffer->toString());

        $decrypted->setPosition(self::MIN_PACKET_SIZE - 1)->putByte(Tables::CRYPT_TABLE_2[($key << 8) + $buffer->getUnsignedByte()]);

        for ($i = 8; $i < $buffer->size(); $i++) {
            $decrypted->setPosition($i)->putByte($buffer->getUnsignedByte() ^ $buffer->setPosition($i - 4)->getUnsignedByte());
        }

        return $decrypted->rewind()->remove(self::MIN_PACKET_SIZE);
    }
}
