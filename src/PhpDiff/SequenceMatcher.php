<?php

namespace gipfl\Diff\PhpDiff;

/**
 * Sequence matcher for Diff
 */
class SequenceMatcher
{
    /**
     * Either a string or an array containing a callback function to determine
     * if a line is "junk" or not
     *
     * @var string|array
     */
    private $junkCallback;

    /**
     * @var array The first sequence to compare against.
     */
    private $a = [];

    /**
     * @var array The second sequence.
     */
    private $b = [];

    /**
     * @var array Characters that are considered junk from the second sequence. Characters are the array key.
     */
    private $junkDict = [];

    /**
     * @var array Array of indices that do not contain junk elements.
     */
    private $b2j = [];

    private $options = [];

    private $defaultOptions = [
        'ignoreNewLines' => false,
        'ignoreWhitespace' => false,
        'ignoreCase' => false
    ];

    /** @var array|null */
    private $matchingBlocks;

    /** @var array|null */
    private $opCodes;

    /**
     * The constructor. With the sequences being passed, they'll be set for the
     * sequence matcher and it will perform a basic cleanup & calculate junk
     * elements.
     *
     * @param string|array $a A string or array containing the lines to compare against.
     * @param string|array $b A string or array containing the lines to compare.
     * @param string|array $junkCallback Either an array or string that references a callback
     *                     function (if there is one) to determine 'junk' characters.
     */
    public function __construct($a, $b, $junkCallback = null, $options = [])
    {
        $this->junkCallback = $junkCallback;
        $this->setOptions($options);
        $this->setSequences($a, $b);
    }

    public function setOptions($options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Set the first and second sequences to use with the sequence matcher.
     *
     * @param string|array $a A string or array containing the lines to compare against.
     * @param string|array $b A string or array containing the lines to compare.
     */
    public function setSequences($a, $b)
    {
        $this->setSeq1($a);
        $this->setSeq2($b);
    }

    /**
     * Set the first sequence ($a) and reset any internal caches to indicate that
     * when calling the calculation methods, we need to recalculate them.
     *
     * @param string|array $a The sequence to set as the first sequence.
     */
    public function setSeq1($a)
    {
        if (!is_array($a)) {
            $a = str_split($a);
        }
        if ($a === $this->a) {
            return;
        }

        $this->a = $a;
        $this->matchingBlocks = null;
        $this->opCodes = null;
    }

    /**
     * Set the second sequence ($b) and reset any internal caches to indicate that
     * when calling the calculation methods, we need to recalculate them.
     *
     * @param string|array $b The sequence to set as the second sequence.
     */
    public function setSeq2($b)
    {
        if (!is_array($b)) {
            $b = str_split($b);
        }
        if ($b === $this->b) {
            return;
        }

        $this->b = $b;
        $this->matchingBlocks = null;
        $this->opCodes = null;
        $this->fullBCount = null;
        $this->chainB();
    }

    /**
     * @return array
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @return array
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * Generate the internal arrays containing the list of junk and non-junk
     * characters for the second ($b) sequence.
     */
    private function chainB()
    {
        $length = count($this->b);
        $this->b2j = [];
        $popularDict = [];

        for ($i = 0; $i < $length; ++$i) {
            $char = $this->b[$i];
            if (isset($this->b2j[$char])) {
                if ($length >= 200 && count($this->b2j[$char]) * 100 > $length) {
                    $popularDict[$char] = 1;
                    unset($this->b2j[$char]);
                } else {
                    $this->b2j[$char][] = $i;
                }
            } else {
                $this->b2j[$char] = [$i];
            }
        }

        // Remove leftovers
        foreach (array_keys($popularDict) as $char) {
            unset($this->b2j[$char]);
        }

        $this->junkDict = [];
        if (is_callable($this->junkCallback)) {
            foreach (array_keys($popularDict) as $char) {
                if (call_user_func($this->junkCallback, $char)) {
                    $this->junkDict[$char] = 1;
                    unset($popularDict[$char]);
                }
            }

            foreach (array_keys($this->b2j) as $char) {
                if (call_user_func($this->junkCallback, $char)) {
                    $this->junkDict[$char] = 1;
                    unset($this->b2j[$char]);
                }
            }
        }
    }

    /**
     * Checks if a particular character is in the junk dictionary
     * for the list of junk characters.
     *
     * @return boolean $b True if the character is considered junk. False if not.
     */
    private function isBJunk($b)
    {
        if (isset($this->junkDict[$b])) {
            return true;
        }

        return false;
    }

    /**
     * Find the longest matching block in the two sequences, as defined by the
     * lower and upper constraints for each sequence. (for the first sequence,
     * $alo - $ahi and for the second sequence, $blo - $bhi)
     *
     * Essentially, of all of the maximal matching blocks, return the one that
     * startest earliest in $a, and all of those maximal matching blocks that
     * start earliest in $a, return the one that starts earliest in $b.
     *
     * If the junk callback is defined, do the above but with the restriction
     * that the junk element appears in the block. Extend it as far as possible
     * by matching only junk elements in both $a and $b.
     *
     * @param int $alo The lower constraint for the first sequence.
     * @param int $ahi The upper constraint for the first sequence.
     * @param int $blo The lower constraint for the second sequence.
     * @param int $bhi The upper constraint for the second sequence.
     * @return array Array containing the longest match that includes the starting
     *               position in $a, start in $b and the length/size.
     */
    public function findLongestMatch($alo, $ahi, $blo, $bhi)
    {
        $a = $this->a;
        $b = $this->b;

        $bestI = $alo;
        $bestJ = $blo;
        $bestSize = 0;

        $j2Len = [];
        $nothing = [];

        for ($i = $alo; $i < $ahi; ++$i) {
            $newJ2Len = [];
            $jDict = ArrayHelper::getPropertyOrDefault($this->b2j, $a[$i], $nothing);
            foreach ($jDict as $jKey => $j) {
                if ($j < $blo) {
                    continue;
                }
                if ($j >= $bhi) {
                    break;
                }

                $k = ArrayHelper::getPropertyOrDefault($j2Len, $j -1, 0) + 1;
                $newJ2Len[$j] = $k;
                if ($k > $bestSize) {
                    $bestI = $i - $k + 1;
                    $bestJ = $j - $k + 1;
                    $bestSize = $k;
                }
            }

            $j2Len = $newJ2Len;
        }

        while ($bestI > $alo
            && $bestJ > $blo
            && !$this->isBJunk($b[$bestJ - 1])
            && !$this->linesAreDifferent($bestI - 1, $bestJ - 1)
        ) {
            --$bestI;
            --$bestJ;
            ++$bestSize;
        }

        while ($bestI + $bestSize < $ahi && ($bestJ + $bestSize) < $bhi
            && !$this->isBJunk($b[$bestJ + $bestSize])
            && !$this->linesAreDifferent($bestI + $bestSize, $bestJ + $bestSize)
        ) {
            ++$bestSize;
        }

        while ($bestI > $alo
            && $bestJ > $blo
            && $this->isBJunk($b[$bestJ - 1])
            && !$this->linesAreDifferent($bestI - 1, $bestJ - 1)
        ) {
            --$bestI;
            --$bestJ;
            ++$bestSize;
        }

        while ($bestI + $bestSize < $ahi
            && $bestJ + $bestSize < $bhi
            && $this->isBJunk($b[$bestJ + $bestSize])
            && !$this->linesAreDifferent($bestI + $bestSize, $bestJ + $bestSize)
        ) {
            ++$bestSize;
        }

        return [
            $bestI,
            $bestJ,
            $bestSize
        ];
    }

    /**
     * Check if the two lines at the given indexes are different or not.
     *
     * @param int $aIndex Line number to check against in a.
     * @param int $bIndex Line number to check against in b.
     * @return boolean True if the lines are different and false if not.
     */
    public function linesAreDifferent($aIndex, $bIndex)
    {
        $lineA = $this->a[$aIndex];
        $lineB = $this->b[$bIndex];

        if ($this->options['ignoreWhitespace']) {
            $replace = array("\t", ' ');
            $lineA = str_replace($replace, '', $lineA);
            $lineB = str_replace($replace, '', $lineB);
        }

        if ($this->options['ignoreCase']) {
            $lineA = strtolower($lineA);
            $lineB = strtolower($lineB);
        }

        return $lineA !== $lineB;
    }

    /**
     * Return a nested set of arrays for all of the matching sub-sequences
     * in the strings $a and $b.
     *
     * Each block contains the lower constraint of the block in $a, the lower
     * constraint of the block in $b and finally the number of lines that the
     * block continues for.
     *
     * @return array Nested array of the matching blocks, as described by the function.
     */
    public function getMatchingBlocks()
    {
        if (!empty($this->matchingBlocks)) {
            return $this->matchingBlocks;
        }

        $aLength = count($this->a);
        $bLength = count($this->b);

        $queue = [
            [
                0,
                $aLength,
                0,
                $bLength
            ]
        ];

        $matchingBlocks = [];
        while (!empty($queue)) {
            list($alo, $ahi, $blo, $bhi) = array_pop($queue);
            $x = $this->findLongestMatch($alo, $ahi, $blo, $bhi);
            list($i, $j, $k) = $x;
            if ($k) {
                $matchingBlocks[] = $x;
                if ($alo < $i && $blo < $j) {
                    $queue[] = [
                        $alo,
                        $i,
                        $blo,
                        $j
                    ];
                }

                if ($i + $k < $ahi && $j + $k < $bhi) {
                    $queue[] = [
                        $i + $k,
                        $ahi,
                        $j + $k,
                        $bhi
                    ];
                }
            }
        }

        usort($matchingBlocks, [ArrayHelper::class, 'tupleSort']);

        $i1 = 0;
        $j1 = 0;
        $k1 = 0;
        $nonAdjacent = [];
        foreach ($matchingBlocks as $block) {
            list($i2, $j2, $k2) = $block;
            if ($i1 + $k1 === $i2 && $j1 + $k1 === $j2) {
                $k1 += $k2;
            } else {
                if ($k1) {
                    $nonAdjacent[] = [
                        $i1,
                        $j1,
                        $k1
                    ];
                }

                $i1 = $i2;
                $j1 = $j2;
                $k1 = $k2;
            }
        }

        if ($k1) {
            $nonAdjacent[] = [
                $i1,
                $j1,
                $k1
            ];
        }

        $nonAdjacent[] = [
            $aLength,
            $bLength,
            0
        ];

        $this->matchingBlocks = $nonAdjacent;
        return $this->matchingBlocks;
    }

    public function getOpcodes()
    {
        if ($this->opCodes === null) {
            $this->opCodes = $this->calculateOpCodes();
        }

        return $this->opCodes;
    }

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
     * @return array Array of the opcodes describing the differences between the strings.
     */
    public function calculateOpCodes()
    {
        $i = 0;
        $j = 0;
        $opCodes = [];

        $blocks = $this->getMatchingBlocks();
        foreach ($blocks as $block) {
            list($ai, $bj, $size) = $block;
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
     * @param int $context The number of lines of context to provide around the groups.
     * @return array Nested array of all of the grouped opcodes.
     */
    public function getGroupedOpcodes($context = 3)
    {
        $opCodes = $this->getOpCodes();
        if (empty($opCodes)) {
            $opCodes = array(
                array(
                    'equal',
                    0,
                    1,
                    0,
                    1
                )
            );
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
        foreach ($opCodes as $code) {
            list($tag, $i1, $i2, $j1, $j2) = $code;
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
            $group[] = [
                $tag,
                $i1,
                $i2,
                $j1,
                $j2
            ];
        }

        if (!empty($group) && !(count($group) === 1 && $group[0][0] === 'equal')) {
            $groups[] = $group;
        }

        return $groups;
    }
}
