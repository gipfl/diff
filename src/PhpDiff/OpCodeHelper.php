<?php

namespace gipfl\Diff\PhpDiff;

use function count;
use function max;
use function min;

abstract class OpCodeHelper
{
    /**
     * Return a list of all of the opcodes for the differences between the
     * two strings.
     *
     * The nested array returned contains an array describing the opcode
     * which includes:
     * 0 - The type of tag (as described below) for the opcode.
     * 1 - The beginning line in the first sequence.
     * 2 - The end line in the first sequence.
     * 3 - The beginning line in the second sequence.
     * 4 - The end line in the second sequence.
     *
     * The different types of tags include:
     * replace - The string from $i1 to $i2 in $a should be replaced by
     *           the string in $b from $j1 to $j2.
     * delete -  The string in $a from $i1 to $j2 should be deleted.
     * insert -  The string in $b from $j1 to $j2 should be inserted at
     *           $i1 in $a.
     * equal  -  The two strings with the specified ranges are equal.
     *
     * @param Block[] $blocks
     * @return OpCode[] Array of the opcodes describing the differences between the strings.
     */
    public static function calculateOpCodes(array $blocks)
    {
        $lastLeftEnd = 0;
        $lastRightEnd = 0;
        $opCodes = [];

        foreach ($blocks as $block) {
            $tag = null;
            if ($lastLeftEnd < $block->beginLeft) {
                if ($lastRightEnd < $block->beginRight) {
                    $tag = 'replace';
                } else {
                    $tag = 'delete';
                }
            } elseif ($lastRightEnd < $block->beginRight) {
                $tag = 'insert';
            }

            if ($tag) {
                $opCodes[] = new OpCode($tag, $lastLeftEnd, $block->beginLeft, $lastRightEnd, $block->beginRight);
            }

            $lastLeftEnd = $block->beginLeft + $block->size;
            $lastRightEnd = $block->beginRight + $block->size;

            if ($block->hasLines()) {
                $opCodes[] = new OpCode('equal', $block->beginLeft, $lastLeftEnd, $block->beginRight, $lastRightEnd);
            }
        }

        return $opCodes;
    }

    /**
     * Return a series of nested arrays containing different groups of generated
     * opcodes for the differences between the strings with up to $context lines
     * of surrounding content.
     *
     * Essentially what happens here is any big equal blocks of strings are stripped
     * out, the smaller subsets of changes are then arranged in to their groups.
     * This means that the sequence matcher and diffs do not need to include the full
     * content of the different files but can still provide context as to where the
     * changes are.
     *
     * @param array $opCodes
     * @param int $context The number of lines of context to provide around the groups.
     * @return OpCode[] Nested array of all of the grouped opcodes.
     */
    public static function getGroupedOpcodes(array $opCodes, $context = 3)
    {
        if (empty($opCodes)) {
            $opCodes = [new OpCode('equal', 0, 1, 0, 1)];
        }

        if ($opCodes[0]->type === 'equal') {
            $opCodes[0] = $opCodes[0]->withLowerContext($context);
        }

        $lastItem = count($opCodes) - 1;
        if ($opCodes[$lastItem]->type === 'equal') {
            $opCodes[$lastItem] = $opCodes[$lastItem]->withUpperContext($context);
        }

        $maxRange = $context * 2;
        $groups = [];
        $group = [];
        foreach ($opCodes as $opCode) {
            if ($opCode->type === 'equal'
                && $opCode->endLeft - $opCode->beginLeft > $maxRange
            ) {
                $group[] = $opCode->withUpperContext($context);
                $groups[] = $group;
                $group = [];
                $opCode = $opCode->withLowerContext($context);
            }
            $group[] = $opCode;
        }

        if (!empty($group)
            && !(count($group) === 1 && $group[0]->type === 'equal')
        ) {
            $groups[] = $group;
        }

        return $groups;
    }
}
