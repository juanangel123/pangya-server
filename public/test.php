<?php

use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;
use PangYa\Crypt\Lib;
use PangYa\Util\MiniLZO;
use PangYa\Util\Util;

/**
 * Register the auto loader.
 */
require __DIR__.'/../vendor/autoload.php';

/**
 * Test against server encryption / decryption and MiniLZO compression / decompression.
 *
 * @throws BufferException
 */
function testEncryption(): void
{
    $crypt = new Lib();

    $buffer = new StringBuffer();
    $buffer->insertArrayBytes([0x01, 0x00, 0xe3, 0x48, 0xd2, 0x4d, 0x00]);

    $compressed = MiniLZO::compress1X1($buffer->rewind()->getArrayBytes($buffer->size()));

    $buffer2 = new StringBuffer();
    $buffer2->insertArrayBytes($compressed);
    Util::showHex($buffer2);
    // 18 1 0 E3 48 D2 4D 0 11 0 0

    $buffer3 = $crypt->encrypt($buffer, 0);
    Util::showHex($buffer3);
    // 0 10 0 0 0 0 0 7 18 1 0 E4 50 D3 4D E3 59 D2 4D

    $decompressed = MiniLZO::decompress1X($compressed);

    $buffer4 = new StringBuffer();
    $buffer4->insertArrayBytes($decompressed);
    Util::showHex($buffer4);
    // 1 0 E3 48 D2 4D 0

    $buffer5 = new StringBuffer();
    $buffer5->insertArrayBytes([
        0xcc,
        0x3c,
        0x00,
        0x00,
        0x8e,
        0x01,
        0x00,
        0x04,
        0x7d,
        0x75,
        0x65,
        0x77,
        0x74,
        0x54,
        0x65,
        0x43,
        0x4d,
        0x18,
        0x46,
        0x06,
        0x7b,
        0x7b,
        0x02,
        0x02,
        0x74,
        0x71,
        0x75,
        0x70,
        0x05,
        0x05,
        0x02,
        0x07,
        0x72,
        0x73,
        0x76,
        0x77,
        0x04,
        0x7c,
        0x76,
        0x06,
        0x73,
        0x0a,
        0x04,
        0x70,
        0x02,
        0x74,
        0x01,
        0x42,
        0x34,
        0x46,
        0x36,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
    ]);
    $buffer5->rewind();

    $result = $crypt->decrypt($buffer5, 1);
    Util::showHex($result);
    // 01 00 04 00 74 65 73 74 20 00 30 39 38 46 36 42 43 44 34 36 32 31 44 33 37 33 43 41 44 45 34 45 38 33 32 36 32 37 42 34 46 36 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00

    $buffer5 = new \PangYa\Packet\Buffer();
    $buffer5->insertArrayBytes( [
        0x02,
        0x00,
        0x01,
        0x54,
        0x65,
        0x73,
        0x74,
        0x20,
        0x73,
        0x65,
        0x72,
        0x76,
        0x65,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x68,
        0x29,
        0x22,
        0x13,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x01,
        0x00,
        0x00,
        0x00,
        0xB0,
        0x04,
        0x00,
        0x00,
        0x38,
        0x01,
        0x00,
        0x00,
        0x31,
        0x32,
        0x37,
        0x2E,
        0x30,
        0x2E,
        0x30,
        0x2E,
        0x31,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x60,
        0x29,
        0xE8,
        0x4E,
        0x00,
        0x00,
        0x00,
        0x08,
        0x00,
        0x00,
        0x01,
        0x00,
        0x00,
        0x00,
        0x01,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x00,
        0x01,
        0x00,
    ]);

    $result = $crypt->encrypt($buffer5, 0);

    Util::showHex($result);
    // 0 54 0 0 0 0 0 5F B 2 0 5E 5F 67 73 75 74 16 16 6 56 16 65 5D 76 65 1 47 29 22 12 98 2B 22 10 F1 2 0 3 B1 4 0 0 88 5 0 0 9 33 37 2E 1 1C 7 0 1 FE 33 2E 37 B0 2A E8 48 60 29 E8 46 0 0 1 8 0 0 0 0 0 0 1 0 0 0 1 0 11 0 1
    die();
}

testEncryption();
