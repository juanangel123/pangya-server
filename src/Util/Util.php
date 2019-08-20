<?php

namespace PangYa\Util;

use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\Cast;

/**
 * Utility functions.
 *
 * @package PangYa\Util
 */
class Util
{
    /**
     * @var array
     */
    protected const CHARS_1 = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 1, 2, 3, 4, 5, 6, 7, 8, 9, 0];

    /**
     * Show hex representation of a buffer.
     *
     * @param  Buffer  $buffer
     * @throws BufferException
     */
    public static function showHex(Buffer $buffer): void
    {
        $oldPosition = $buffer->position();

        $buffer->rewind();

        while ($buffer->remaining() > 0) {
            $byte = $buffer->getUnsignedByte();
            echo '0x'.str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
            if ($buffer->remaining() > 0) {
                echo ' ';
            }
        }

        echo "\n";

        $buffer->setPosition($oldPosition);
    }

    /**
     * Copy a part of an array to another array.
     * This is a copy from the same method in C#:
     *
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
     * Random auth.
     *
     * @param  int  $length
     * @return string
     * @throws Exception
     */
    public static function randomAuth(int $length): string
    {
        $result = '';
        do {
            $result.= self::CHARS_1[random_int(0, $length - 1)];
        } while (strlen($result) < $length);

        return $result;
    }

    /**
     * Read an unsigned short from a byte array.
     *
     * @param  array  $array
     * @param  int  $i
     * @return int
     */
    public static function readUnsignedShort(array $array, int $i): int
    {
        return Cast::toUnsignedShort($array[$i] | ($array[$i + 1] << 8));
    }

    /**
     * Read an unsigned int from a byte array.
     *
     * @param  array  $array
     * @param  int  $i
     * @return int
     */
    public static function readUnsignedInt(array $array, int $i): int
    {
        return Cast::toUnsignedInt($array[$i] | ($array[$i + 1] << 8) | ($array[$i + 2] << 16) | ($array[$i + 3] << 24));
    }
}
