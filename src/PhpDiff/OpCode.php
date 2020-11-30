<?php

namespace gipfl\Diff\PhpDiff;

/**
 * Single string difference OpCode
 */
class OpCode
{
    const TYPE_REPLACE = 'replace';
    const TYPE_DELETE = 'delete';
    const TYPE_INSERT = 'insert';
    const TYPE_EQUAL = 'equal';

    /** @var string one of TYPE_* */
    public $type;

    /** @var int */
    public $beginLeft;

    /** @var int */
    public $endLeft;

    /** @var int */
    public $beginRight;

    /** @var int */
    public $endRight;

    /**
     * @param string $type
     * @param int $beginLeft
     * @param int $endLeft
     * @param int $beginRight
     * @param int $endRight
     */
    public function __construct($type, $beginLeft, $endLeft, $beginRight, $endRight)
    {
        $this->type = $type;
        $this->beginLeft = $beginLeft;
        $this->endLeft = $endLeft;
        $this->beginRight = $beginRight;
        $this->endRight = $endRight;
    }

    /**
     * @param int $context
     * @return OpCode
     */
    public function withLowerContext($context = 3)
    {
        $clone = clone($this);
        $this->beginLeft = max($this->beginLeft, $this->endLeft - $context);
        $this->beginRight = max($this->beginRight, $this->endRight - $context);
        return $clone;
    }

    /**
     * @param int $context
     * @return OpCode
     */
    public function withUpperContext($context = 3)
    {
        $clone = clone($this);
        $this->beginLeft = min($this->endLeft, $this->beginLeft + $context);
        $this->beginRight = min($this->endRight, $this->beginRight  + $context);
        return $clone;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            $this->type,
            $this->beginLeft,
            $this->endLeft,
            $this->beginRight,
            $this->endRight
        ];
    }

    /**
     * @param array $array
     * @return static
     */
    public static function fromArray(array $array)
    {
        return new static($array[0], $array[1], $array[2], $array[3], $array[4]);
    }
}
