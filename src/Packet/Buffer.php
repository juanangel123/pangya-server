<?php

namespace PangYa\Packet;

use Nelexa\Buffer\Buffer as BaseBuffer;
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

    /**
     * Read an string based on the current pointer of the buffer.
     *
     * @return string|null
     */
    public function readString(): ?string
    {
        try {
            return implode(array_map('chr', $this->getArrayBytes($this->getUnsignedShort())));
        } catch (BufferException $e) {
            return null;
        }
    }

    /**
     * Insert string and his length.
     *
     * @param  string  $string
     * @return self
     * @throws BufferException
     */
    public function insertPString(string $string): self
    {
        $this->insertInt(strlen($string));
        $this->insertString($string);

        return $this;
    }

    /**
     * Insert a fixed length string with overflow.
     *
     * @param  string  $string
     * @param  int  $length
     * @param  int  $overflow
     * @return self
     * @throws BufferException
     */
    public function insertString($string, int $length = 0, int $overflow = 0x00): self
    {
        parent::insertString($string);

        if (($diff = $length - strlen($string)) > 0) {
            $overflowData = [];
            for ($i = 0; $i < $diff; $i++) {
                $overflowData[] = $overflow;
            }

            $this->insertArrayBytes($overflowData);
        }

        return $this;
    }
}
