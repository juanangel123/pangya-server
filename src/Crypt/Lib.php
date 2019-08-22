<?php

namespace PangYa\Crypt;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\Cast;
use Nelexa\Buffer\StringBuffer;
use PangYa\Packet\Buffer as PangYaBuffer;
use PangYa\Util\MiniLZO;
use PangYa\Util\Util;

/**
 * Class used to encrypt and decrypt packets from the server-side.
 *
 * @link https://github.com/pangyatools/PangCrypt/blob/master/PangCrypt/ClientCipher.cs
 * @link https://github.com/pangyatools/PangCrypt/blob/master/PangCrypt/ServerCipher.cs
 *
 * @package PangYa\Crypt
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
    public function encrypt(Buffer $buffer, int $key, int $salt = 0): Buffer
    {
        $compressed = MiniLZO::compress1X1($buffer->rewind()->getArrayBytes($buffer->size()));
        $buffer->rewind();

        $index = ($key << 8) + $salt;
        $encrypted = new StringBuffer();
        $pLength = count($compressed) + self::MIN_PACKET_SIZE;

        $u = $buffer->size();
        $x = ($u + $u / 255) & 0xff;
        $v = ($u - $x) / 255;
        $y = ($v + $v / 255) & 0xff;
        $w = ($v - $y) / 255;
        $z = ($w + $w / 255) & 0xff;

        $encrypted->insertByte($salt);
        $encrypted->insertByte(Cast::toByte(($pLength >> 0) & 0xff));
        $encrypted->insertByte(Cast::toByte(($pLength >> 8) & 0xff));
        $encrypted->insertByte(Cast::toByte(Tables::CRYPT_TABLE_1[$index] ^ Tables::CRYPT_TABLE_2[$index]));
        $encrypted->insertByte(0); // To be filled after.
        $encrypted->insertByte(Cast::toByte($z));
        $encrypted->insertByte(Cast::toByte($y));
        $encrypted->insertByte(Cast::toByte($x));

        $encrypted->setPosition(8)->insertArrayBytes($compressed);

        for ($i = $encrypted->size() - 1; $i >= 10; $i--) {
            $data = $encrypted->setPosition($i)->getUnsignedByte() ^ $encrypted->setPosition($i - 4)->getUnsignedByte();
            $encrypted->setPosition($i)->putByte($data);
        }

        $data = $encrypted->setPosition(7)->getUnsignedByte() ^ Tables::CRYPT_TABLE_2[$index];

        return $encrypted->setPosition(7)->putByte($data)->rewind();
    }

    /**
     * Decrypt the packet provided.
     *
     * @param  Buffer  $buffer  The encrypted packet data.
     * @param  int  $key  Key to decrypt with.
     * @return PangYaBuffer The decrypted packet data.
     * @throws BufferException
     */
    public function decrypt(Buffer $buffer, int $key): Buffer
    {
        $decrypted = new StringBuffer($buffer->toString());

        $data = Tables::CRYPT_TABLE_2[($key << 8) + $decrypted->getUnsignedByte()];
        $decrypted->setPosition(self::MIN_PACKET_SIZE - 1)->putByte($data);

        for ($i = 8; $i < $decrypted->size(); $i++) {
            $data = $decrypted->setPosition($i)->getUnsignedByte() ^ $decrypted->setPosition($i - 4)->getUnsignedByte();
            $decrypted->setPosition($i)->putByte($data);
        }

        $decrypted->rewind()->remove(self::MIN_PACKET_SIZE);

        return new PangYaBuffer($decrypted->toString());
    }
}
