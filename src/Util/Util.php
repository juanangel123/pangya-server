<?php

namespace PangYa\Util;

use DateTime;
use Exception;
use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\Cast;
use PangYa\Packet\Buffer as PangYaBuffer;

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
     */
    public static function showHex(Buffer $buffer): void
    {
        try {
            $oldPosition = $buffer->position();

            $buffer->rewind();

            while ($buffer->remaining() > 0) {
                $byte = $buffer->getUnsignedByte();
                echo '0x'.str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                if ($buffer->remaining() > 0) {
                    echo ' ';
                }
                if ($buffer->position() > 0 && $buffer->position() % 25 === 0) {
                    echo "\n";
                }
            }

            echo "\n";

            $buffer->setPosition($oldPosition);
        } catch (BufferException $e) {
            //
        }
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
     * @return void
     */
    public static function copyArray(array $input, int $inputIndex, array &$output, int $outputIndex, int $length): void
    {
        $aux = array_slice($input, $inputIndex, $length);
        for ($i = $outputIndex; $i < $outputIndex + $length; $i++) {
            $output[$i] = $aux[$i - $outputIndex];
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
            $result .= self::CHARS_1[random_int(0, $length - 1)];
        } while (strlen($result) < $length);

        return $result;
    }

    /**
     * Return the game time as a byte array.
     *
     * @return array
     */
    public static function getGameTime(): array
    {
        try {
            $now = new DateTime();
        } catch (Exception $e) {
            return [];
        }

        try {
            // Note: no leading zeros.
            $result = new PangYaBuffer();
            $result->insertShort($now->format('Y'));
            $result->insertShort($now->format('m'));
            $result->insertShort($now->format('w'));
            $result->insertShort($now->format('d'));
            $result->insertShort($now->format('G'));
            $result->insertShort($now->format('i'));
            $result->insertShort($now->format('s'));
            $result->insertShort($now->format('v'));

            $result->rewind();

            return $result->getArrayBytes($result->size());
        } catch (BufferException $e) {
            return [];
        }
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
