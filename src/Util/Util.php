<?php

namespace Pangya\Util;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\Cast;

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

    /**
     * @link https://docs.microsoft.com/es-es/dotnet/api/system.array.copy?view=netframework-4.8#System_Array_Copy_System_Array_System_Int32_System_Array_System_Int32_System_Int32_
     *
     * @param  array  $input
     * @param  int  $inputIndex
     * @param  array  $output
     * @param  int  $outputIndex
     * @param  int  $length
     */
    public static function copyArray(array $input, int $inputIndex, array &$output, int $outputIndex, int $length): void
    {
        for ($x = 0; $x < ($length - $outputIndex); $x++) {
            $output[$outputIndex + $x] = $input[$inputIndex + $x];
        }
    }

    /**
     * @param  array  $array
     * @param  int  $i
     * @return int
     */
    public static function readU16(array $array, int $i): int
    {
        return Cast::toUnsignedShort($array[$i] | ($array[$i + 1] << 8));
    }

    /**
     * @param  array  $array
     * @param  int  $i
     * @return int
     */
    public static function readU32(array $array, int $i): int
    {
        return Cast::toUnsignedInt($array[$i] | ($array[$i + 1] << 8) | ($array[$i + 2] << 16) | ($array[$i + 3] << 24));
    }
}
