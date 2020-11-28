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
     * @param array $blocks
     * @return array Array of the opcodes describing the differences between the strings.
     */
    public static function calculateOpCodes(array $blocks)
    {
        $i = 0;
        $j = 0;
        $opCodes = [];

        foreach ($blocks as list($ai, $bj, $size)) {
            $tag = '';
            if ($i < $ai && $j < $bj) {
                $tag = 'replace';
            } elseif ($i < $ai) {
                $tag = 'delete';
            } elseif ($j < $bj) {
                $tag = 'insert';
            }

            if ($tag) {
                $opCodes[] = [$tag, $i, $ai, $j, $bj];
            }

            $i = $ai + $size;
            $j = $bj + $size;

            if ($size) {
                $opCodes[] = ['equal', $ai, $i, $bj, $j];
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
     * @return array Nested array of all of the grouped opcodes.
     */
    public static function getGroupedOpcodes(array $opCodes, $context = 3)
    {
        if (empty($opCodes)) {
            $opCodes = [
                ['equal', 0, 1, 0, 1]
            ];
        }

        if ($opCodes[0][0] === 'equal') {
            $opCodes[0] = [
                $opCodes[0][0],
                max($opCodes[0][1], $opCodes[0][2] - $context),
                $opCodes[0][2],
                max($opCodes[0][3], $opCodes[0][4] - $context),
                $opCodes[0][4]
            ];
        }

        $lastItem = count($opCodes) - 1;
        if ($opCodes[$lastItem][0] === 'equal') {
            list($tag, $i1, $i2, $j1, $j2) = $opCodes[$lastItem];
            $opCodes[$lastItem] = [
                $tag,
                $i1,
                min($i2, $i1 + $context),
                $j1,
                min($j2, $j1 + $context)
            ];
        }

        $maxRange = $context * 2;
        $groups = [];
        $group = [];
        foreach ($opCodes as list($tag, $i1, $i2, $j1, $j2)) {
            if ($tag === 'equal' && $i2 - $i1 > $maxRange) {
                $group[] = [
                    $tag,
                    $i1,
                    min($i2, $i1 + $context),
                    $j1,
                    min($j2, $j1 + $context)
                ];
                $groups[] = $group;
                $group = [];
                $i1 = max($i1, $i2 - $context);
                $j1 = max($j1, $j2 - $context);
            }
            $group[] = [$tag, $i1, $i2, $j1, $j2];
        }

        if (!empty($group) && !(count($group) === 1 && $group[0][0] === 'equal')) {
            $groups[] = $group;
        }

        return $groups;
    }
}
