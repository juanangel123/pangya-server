<?php

namespace Pangya\Util;

use Nelexa\Buffer\Cast;

/**
 * Class LZO
 *
 * @link https://github.com/pangyatools/PangCrypt/blob/master/PangCrypt/MiniLzo.cs
 * @link https://en.wikipedia.org/wiki/Lempel%E2%80%93Ziv%E2%80%93Oberhumer
 *
 * @package Pangya\Util
 */
class LZO
{
    /**
     *
     */
    protected static function compress1X1Core()
    {
        /**
         * var inEnd = inIndex + inLen;
         * var ipEnd = inIndex + inLen - 20;
         * var op = outIndex;
         * var ip = inIndex;
         * var ii = ip;
         * ip += ti < 4 ? 4 - ti : 0;
         *
         * for (;;)
         * {
         * literal:
         * ip += 1 + ((ip - ii) >> 5);
         * next:
         * if (ip >= ipEnd)
         * break;
         * var dv = ReadU32(@in, ip);
         * var dIndex = (((0x1824429d * dv) >> (32 - 14)) & (((1u << 14) - 1) >> 0)) << 0;
         * var mPos = inIndex + dict[dIndex];
         * dict[dIndex] = (ushort) (ip - inIndex);
         * if (dv != ReadU32(@in, mPos))
         * goto literal;
         *
         * ii -= ti;
         * ti = 0;
         * {
         * var t = ip - ii;
         * if (t != 0)
         * {
         * if (t <= 3)
         * {
         * @out[op - 2] |= (byte) t;
         * Array.Copy(@in, ii, @out, op, t);
         * op += t;
         * }
         * else if (t <= 16)
         * {
         * @out[op++] = (byte) (t - 3);
         * Array.Copy(@in, ii, @out, op, t);
         * op += t;
         * }
         * else
         * {
         * if (t <= 18)
         * {
         * @out[op++] = (byte) (t - 3);
         * }
         * else
         * {
         * var tt = t - 18;
         * @out[op++] = 0;
         * while (tt > 255)
         * {
         * tt -= 255;
         * @out[op++] = 0;
         * }
         *
         * @out[op++] = (byte) tt;
         * }
         *
         * Array.Copy(@in, ii, @out, op, t);
         * op += t;
         * }
         * }
         * }
         * uint mLen = 4;
         * {
         * var v = ReadU32(@in, ip + mLen) ^ ReadU32(@in, mPos + mLen);
         * while (v == 0)
         * {
         * mLen += 4;
         * v = ReadU32(@in, ip + mLen) ^ ReadU32(@in, mPos + mLen);
         * if (ip + mLen >= ipEnd)
         * goto m_len_done;
         * }
         *
         * mLen += (uint) LzoBitOpsCtz32(v) / 8;
         * }
         * m_len_done:
         * var mOff = ip - mPos;
         * ip += mLen;
         * ii = ip;
         * if (mLen <= 8 && mOff <= 0x0800)
         * {
         * mOff -= 1;
         * @out[op++] = (byte) (((mLen - 1) << 5) | ((mOff & 7) << 2));
         * @out[op++] = (byte) (mOff >> 3);
         * }
         * else if (mOff <= 0x4000)
         * {
         * mOff -= 1;
         * if (mLen <= 33)
         * {
         * @out[op++] = (byte) (32 | (mLen - 2));
         * }
         * else
         * {
         * mLen -= 33;
         * @out[op++] = 32 | 0;
         * while (mLen > 255)
         * {
         * mLen -= 255;
         * @out[op++] = 0;
         * }
         *
         * @out[op++] = (byte) mLen;
         * }
         *
         * @out[op++] = (byte) (mOff << 2);
         * @out[op++] = (byte) (mOff >> 6);
         * }
         * else
         * {
         * mOff -= 0x4000;
         * if (mLen <= 9)
         * {
         * @out[op++] = (byte) (16 | ((mOff >> 11) & 8) | (mLen - 2));
         * }
         * else
         * {
         * mLen -= 9;
         * @out[op++] = (byte) (16 | ((mOff >> 11) & 8));
         * while (mLen > 255)
         * {
         * mLen -= 255;
         * @out[op++] = 0;
         * }
         *
         * @out[op++] = (byte) mLen;
         * }
         *
         * @out[op++] = (byte) (mOff << 2);
         * @out[op++] = (byte) (mOff >> 6);
         * }
         *
         * goto next;
         * }
         *
         * outLen = op - outIndex;
         * return inEnd - (ii - ti);
         */

        return 2;
    }

    /**
     * Compress data using the LZO algorithm (LZO1X-1).
     *
     * @param  array  $input  Array of bytes.
     * @return array
     */
    public static function compress1X1(array $input): array
    {
        $out = [];
        $dict = [];
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

            for ($i = 0; $i < (1 << 14) * 2; $i++) {
                $dict[$i] = 0;
            }

            $t = self::compress1X1Core();
            $ip += $ll;
            $op += $outLen;
            $l -= $ll;
        }

        $t += $l;
        if ($t > 0) {
            $ii = count($input) - $t;
            if ($op === 0 && $t <= 238) {
                $out[$op++] = Cast::toByte(17 + $t);
            } elseif ($t <= 3) {
                $out[$op - 2] |= Cast::toByte($t);
            } elseif ($t <= 18) {
                $out[$op++] = Cast::toByte($t - 3)
                } else {
                $tt = $t - 18;
                $out[$op++] = Cast::toByte($t - 3);

                while ($tt > 255) {
                    $tt -= 255;
                    $out[$op++] = 0;
                }

                $out[$op++] = Cast::toByte($tt);
            }

            do {
                $out[$op++] = $input[$ii++];
            } while (--$t > 0);
        }

        $out[$op++] = 16 | 1;
        $out[$op++] = 0;
        $out[$op++] = 0;
        $outLen = $op;

        // TODO: resize to outlen.
        /**
         * var @out = new byte[input.Length + input.Length / 16 + 64 + 3];
         * Lzo1X1Compress(input, (uint) input.Length, @out, out var outLen, new ushort[32768]);
         * Array.Resize(ref @out, (int) outLen);
         * return @out;
         */

        return $out;
    }
}
