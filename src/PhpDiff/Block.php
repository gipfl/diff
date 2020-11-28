<?php

namespace gipfl\Diff\PhpDiff;

/**
 * Matching sub-sequence for two strings $a and $b
 */
class Block
{
    /** @var  int lower constraint of the left block */
    public $beginLeft;

    /** @var int lower constraint of the right block */
    public $beginRight;

    /** @var int number of lines that the block continues for */
    public $size;

    /**
     * @param int $beginLeft
     * @param int $beginRight
     * @param int $cntLines
     */
    public function __construct($beginLeft, $beginRight, $cntLines)
    {
        $this->beginLeft = $beginLeft;
        $this->beginRight = $beginRight;
        $this->size = $cntLines;
    }

    public function isEmpty()
    {
        return $this->size === 0;
    }

    public function hasLines()
    {
        return $this->size > 0;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [$this->beginLeft, $this->beginRight, $this->size];
    }

    /**
     * @param array $array
     * @return static
     */
    public static function fromArray(array $array)
    {
        return new static($array[0], $array[1], $array[2]);
    }
}
