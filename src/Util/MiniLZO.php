<?php

namespace PangYa\Util;

use Nelexa\Buffer\Cast;

/**
 * Class MiniLZO
 *
 * Based on the MiniLZO library by Markus Oberhumer and some code
 * from a port to C# by Frank Razenberg.
 * Copyright (C) 1996-2019 Markus Franz Xaver Johannes Oberhumer
 * All Rights Reserved.
 * The LZO library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 * The LZO library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with the LZO library; see the file COPYING.
 * If not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * Markus F.X.J. Oberhumer
 * <markus@oberhumer.com>
 * http://www.oberhumer.com/opensource/lzo/
 *
 * @link https://github.com/pangyatools/PangCrypt/blob/master/PangCrypt/MiniLzo.cs
 * @link https://en.wikipedia.org/wiki/Lempel%E2%80%93Ziv%E2%80%93Oberhumer
 * @link https://en.wikipedia.org/wiki/De_Bruijn_sequence
 *
 * @package PangYa\Util
 */
class MiniLZO
{
    /**
     * @var array
     */
    protected const multiplyDeBruijnPosition = [
        0,
        1,
        28,
        2,
        29,
        14,
        24,
        3,
        30,
        22,
        20,
        15,
        25,
        17,
        4,
        8,
        31,
        27,
        13,
        23,
        21,
        19,
        16,
        7,
        26,
        12,
        18,
        6,
        11,
        5,
        10,
        9,
    ];

    /**
     * @param  int  $v
     * @return mixed
     */
    protected static function lzoBitOpsCtz32(int $v)
    {
        return self::multiplyDeBruijnPosition[(($v & -$v) * Cast::toUnsignedInt(hexdec(0x077cb531))) >> 27];
    }

    /**
     * Compress data using the LZO algorithm.
     *
     * @param  array  $input
     * @return array
     */
    public static function decompress1X(array $input): array
    {
        $t = 0;
        $output = [];
        $op = 0;
        $ip = 0;
        $gtFirstLateralRun = false;
        $gtMatchDone = false;

        if ($input[$ip] > 17) {
            $t = Cast::toUnsignedByte($input[$ip++] - 17);
            if ($t > 0) {
                do {
                    $output[$op++] = $input[$ip++];
                } while (--$t > 0);
            }

            if ($t >= 0) {
                $gtFirstLateralRun = true;
            }
        }

        while (true) {
            $mPos = 0;
            if ($gtFirstLateralRun) {
                $gtFirstLateralRun = false;
                goto first_lateral_run;
            }

            $t = $input[$ip++];
            if ($t > 16) {
                goto match;
            }
            if ($t === 0) {
                while($input[$ip] === 0) {
                    $t += 255;
                    $ip++;
                }

                $t += Cast::toUnsignedInt(15 + $input[$ip++]);
            }

            $t += 3;
            if ($t > 0) {
                do {
                    $output[$op++] = $input[$ip++];
                } while(--$t > 0);
            }

            first_lateral_run:
            $t = $input[$ip++];
            if ($t >= 16) {
                goto match;
            }
            $mPos = $op - (1 + 2048);
            $mPos -= $t >> 2;
            $mPos -= Cast::toUnsignedInt($input[$ip++] << 2);

            $output[$op++] = $output[$mPos++];
            $output[$op++] = $output[$mPos++];
            $output[$op++] = $output[$mPos];
            $gtMatchDone = true;

            match:
            do {
                if ($gtMatchDone) {
                    $gtMatchDone = false;
                    goto match_done;
                }

                if ($t >= 64) {
                    $mPos = $op - 1;
                    $mPos -= ($t >> 2) & 7;
                    $mPos -= Cast::toUnsignedInt($input[$ip++] << 3);
                    $t = ($t >> 5) - 1;
                    $t += 2;

                    do {
                        $output[$op++] = $output[$mPos++];
                    } while (--$t > 0);

                    goto match_done;
                }

                if ($t >= 32) {
                    $t &= 31;
                    if ($t === 0) {
                        while ($input[$ip] === 0) {
                            $t += 255;
                            $ip++;
                        }

                        $t = Cast::toUnsignedInt(31 + $input[$ip++]);
                    }

                    $mPos = $op - 1;
                    $mPos -= Util::readUnsignedShort($input, $ip) >> 2;
                    $ip += 2;
                } elseif ($t >= 16) {
                    $mPos = $op;
                    $mPos -= ($t & 8) << 11;
                    $t &= 7;
                    if ($t === 0) {
                        while ($input[$ip] === 0) {
                            $t += 255;
                            $ip++;
                        }

                        $t += Cast::toUnsignedInt(7 + $input[$ip++]);
                    }

                    $mPos -= Util::readUnsignedShort($input, $ip) >> 2;
                    $ip += 2;
                    if ($mPos === $op) {
                        goto eof_found;
                    }
                    $mPos -= 16384;
                } else {
                    $mPos = $op - 1;
                    $mPos -= $t >> 2;
                    $mPos -= Cast::toUnsignedInt($input[$ip++] << 2);

                    $output[$op++] = $output[$mPos++];
                    $output[$op++] = $output[$mPos];

                    goto match_done;
                }

                $t += 2;
                do {
                    $output[$op++] = $output[$mPos++];
                } while(--$t > 0);

                match_done:
                $t = Cast::toUnsignedInt($input[$ip - 2] & 3);
                if ($t === 0) {
                    break;
                }
                if ($t >= 0) {
                    do {
                        $output[$op++] = $input[$ip++];
                    } while(--$t > 0);
                }
                $t = $input[$ip++];
            } while(true);
        }

        eof_found:
        return $output;
    }

    /**
     * @param  array  $input
     * @param  int  $inIndex
     * @param  int  $inLen
     * @param  array  $output
     * @param  int  $outIndex
     * @param  int  $outLen
     * @param  int  $ti
     * @return int
     */
    protected static function compress1X1Core(
        array $input,
        int $inIndex,
        int $inLen,
        array &$output,
        int $outIndex,
        int &$outLen,
        int $ti
    ): int {
        $dict = [];
        // Short length (2 bytes).
        for ($i = 0; $i < (1 << 14) * 2; $i++) {
            $dict[$i] = 0;
        }

        $inEnd = $inIndex + $inLen;
        $ipEnd = $inIndex + $inLen - 20;
        $op = $outIndex;
        $ip = $inIndex;
        $ii = $ip;
        $ip += $ti < 4 ? 4 - $ti : 0;

        while (true) {
            literal:
            $ip += 1 + (($ip - $ii) >> 5);

            next:
            if ($ip >= $ipEnd) {
                break;
            }
            $dv = Util::readUnsignedInt($input, $ip);
            $dIndex = Cast::toUnsignedShort((((405029533 * $dv) >> (32 - 14)) & ((Cast::toUnsignedShort(1 << 14) - 1) >> 0)) << 0);

            $mPos = $inIndex + $dict[$dIndex];
            $dict[$dIndex] = Cast::toUnsignedShort($ip - $inIndex);
            if ($dv !== Util::readUnsignedInt($input, $mPos)) {
                goto literal;
            }

            $ii -= $ti;
            $ti = 0;
            {
                $t = $ip - $ii;
                if ($t !== 0) {
                    if ($t <= 3) {
                        $output[$op - 2] |= Cast::toByte($t);
                        Util::copyArray($input, $ii, $output, $op, $t);
                        $op += $t;
                    } elseif ($t <= 16) {
                        $output[$op++] = Cast::toByte($t - 3);
                        Util::copyArray($input, $ii, $output, $op, $t);
                        $op += $t;
                    } else {
                        if ($t <= 18) {
                            $output[$op++] = Cast::toByte($t - 3);
                        } else {
                            $tt = $t - 18;
                            $output[$op++] = 0;
                            while ($tt > 255) {
                                $tt -= 255;
                                $output[$op++] = 0;
                            }

                            $output[$op++] = Cast::toByte($tt);
                        }

                        Util::copyArray($input, $ii, $output, $op, $t);
                        $op += $t;
                    }
                }
            }

            $mLen = 4;

            {
                $v = Util::readUnsignedInt($input, $ip + $mLen) ^ Util::readUnsignedInt($input, $mPos + $mLen);
                while ($v === 0) {
                    $mLen += 4;
                    $v = Util::readUnsignedInt($input, $ip + $mLen) ^ Util::readUnsignedInt($input, $mPos + $mLen);
                    if ($ip + $mLen >= $ipEnd) {
                        goto m_len_done;
                    }
                }
                $mLen += Cast::toUnsignedInt(self::lzoBitOpsCtz32($v) / 8);
            }

            m_len_done:
            $mOff = $ip - $mPos;
            $ip += $mLen;
            $ii = $ip;
            if ($mLen <= 8 && $mOff <= 2048) {
                --$mOff;
                $output[$op++] = Cast::toByte((($mLen - 1) << 5) | (($mOff & 7) << 2));
                $output[$op++] = Cast::toByte($mOff >> 3);
            } elseif ($mOff <= 16384) {
                --$mOff;
                if ($mLen <= 33) {
                    $output[$op++] = Cast::toByte(32 | ($mLen - 2));
                } else {
                    $mLen -= 33;
                    $output[$op++] = 32 | 0;
                    while ($mLen > 255) {
                        $mLen -= 255;
                        $output[$op++] = 0;
                    }

                    $output[$op++] = Cast::toByte($mLen);
                }

                $output[$op++] = Cast::toByte($mOff << 2);
                $output[$op++] = Cast::toByte($mOff >> 6);
            } else {
                $mOff -= 16384;
                if ($mLen <= 9) {
                    $output[$op++] = Cast::toByte(16 | (($mOff >> 11) & 8) | ($mLen - 2));
                } else {
                    $mLen -= 9;
                    $output[$op++] = Cast::toByte(16 | (($mOff >> 11) & 8));
                    while ($mLen > 255) {
                        $mLen -= 255;
                        $output[$op++] = 0;
                    }

                    $output[$op++] = Cast::toByte($mLen);
                }

                $output[$op++] = Cast::toByte($mOff << 2);
                $output[$op++] = Cast::toByte($mOff >> 6);
            }

            goto next;
        }

        $outLen = $op - $outIndex;

        return $inEnd - ($ii - $ti);
    }

    /**
     * Compress data using the LZO algorithm (LZO1X-1).
     *
     * @param  array  $input  Array of bytes.
     * @return array
     */
    public static function compress1X1(array $input): array
    {
        $output = [];
        $outLen = 0;

        $ip = 0;
        $op = 0;
        $l = count($input);
        $t = 0;
        while ($l > 20) {
            $ll = $l;
            $ll = $ll <= 49152 ? $ll : 49152;
            $llEnd = $ip + $ll;
            if ($llEnd + (($t + $ll) >> 5) <= $llEnd || $llEnd + (($t + $ll) >> 5 <= $ip + $ll)) {
                break;
            }

            $t = self::compress1X1Core($input, $ip, $ll, $output, $op, $outLen, $t);

            $ip += $ll;
            $op += $outLen;
            $l -= $ll;
        }

        $t += $l;
        if ($t > 0) {
            $ii = count($input) - $t;
            if ($op === 0 && $t <= 238) {
                $output[$op++] = Cast::toByte(17 + $t);
            } elseif ($t <= 3) {
                $output[$op - 2] |= Cast::toByte($t);
            } elseif ($t <= 18) {
                $output[$op++] = Cast::toByte($t - 3);
            } else {
                $tt = $t - 18;
                $output[$op++] = Cast::toByte($t - 3);
                while ($tt > 255) {
                    $tt -= 255;
                    $output[$op++] = 0;
                }

                $output[$op++] = Cast::toByte($tt);
            }

            do {
                $output[$op++] = $input[$ii++];
            } while (--$t > 0);
        }

        $output[$op++] = 16 | 1;
        $output[$op++] = 0;
        $output[$op++] = 0;
        $outLen = $op;

        return $output;
    }
}
