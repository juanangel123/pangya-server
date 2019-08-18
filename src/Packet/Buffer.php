<?php

namespace PangYa\Packet;

use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;

/**
 * This class will implement a type of buffer used for PangYa operations.
 *
 * @package PangYa\Packet
 */
class Buffer extends StringBuffer
{
    /**
     * Buffer constructor.
     *
     * @param  string  $string
     * @throws BufferException
     */
    public function __construct(string $string = '')
    {
        $this->setOrder(self::LITTLE_ENDIAN);

        parent::__construct($string);
    }
}
