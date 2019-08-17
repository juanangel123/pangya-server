<?php

namespace Pangya\Util;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;

/**
 * Class Util
 *
 * @package Pangya\Util
 */
class Util
{
    /**
     * @var string
     */
    public const PANGYA_US_LOGIN_SERVER_PORT = '10103';

    /**
     * Show hex representation of a buffer.
     *
     * @param  Buffer  $buffer
     * @throws BufferException
     */
    public static function showHex(Buffer $buffer): void
    {
        $buffer->rewind();

        while ($buffer->remaining() > 0) {
            $byte = $buffer->getUnsignedByte();
            echo '0x'.str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
            if ($buffer->remaining() > 0) {
                echo ' ';
            }
        }

        echo "\n";

        $buffer->rewind();
    }
}
