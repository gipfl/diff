<?php

namespace gipfl\Diff;

use gipfl\Diff\PhpDiff\OpCodeHelper;
use gipfl\Diff\PhpDiff\Renderer\AbstractRenderer;
use gipfl\Diff\PhpDiff\SequenceMatcher;

class PhpDiff
{
    /**
     * @var array The "old" sequence to use as the basis for the comparison.
     */
    private $a = null;

    /**
     * @var array The "new" sequence to generate the changes for.
     */
    private $b = null;

    /**
     * @var array Array containing the generated opcodes for the differences between the two items.
     */
    private $groupedCodes = null;

    /**
     * @var array Associative array of the default options available for the diff class and their default value.
     */
    private $defaultOptions = [
        'context' => 3,
        'ignoreNewLines' => false,
        'ignoreWhitespace' => false,
        'ignoreCase' => false
    ];

    /**
     * @var array Array of the options that have been applied for generating the diff.
     */
    private $options;

    /**
     * The constructor.
     *
     * @param array $a Array containing the lines of the first string to compare.
     * @param array $b Array containing the lines for the second string to compare.
     * @param array $options
     */
    public function __construct($a, $b, $options = [])
    {
        $this->a = $a;
        $this->b = $b;

        if (is_array($options)) {
            $this->options = array_merge($this->defaultOptions, $options);
        } else {
            $this->options = $this->defaultOptions;
        }
    }

    /**
     * Render a diff using the supplied rendering class and return it.
     *
     * @param AbstractRenderer $renderer An instance of the rendering object to use for generating the diff.
     * @return mixed The generated diff. Exact return value depends on the rendered.
     */
    public function render(AbstractRenderer $renderer)
    {
        $renderer->diff = $this;
        return $renderer->render();
    }

    /**
     * Get a range of lines from $start to $end from the first comparison string
     * and return them as an array. If no values are supplied, the entire string
     * is returned. It's also possible to specify just one line to return only
     * that line.
     *
     * @param int $start The starting number.
     * @param int $end The ending number. If not supplied, only the item in $start will be returned.
     * @return array Array of all of the lines between the specified range.
     */
    public function getA($start = 0, $end = null)
    {
        if ($start === 0 && $end === null) {
            return $this->a;
        }

        if ($end === null) {
            $length = 1;
        } else {
            $length = $end - $start;
        }

        return array_slice($this->a, $start, $length);
    }

    /**
     * Get a range of lines from $start to $end from the second comparison string
     * and return them as an array. If no values are supplied, the entire string
     * is returned. It's also possible to specify just one line to return only
     * that line.
     *
     * @param int $start The starting number.
     * @param int $end The ending number. If not supplied, only the item in $start will be returned.
     * @return array Array of all of the lines between the specified range.
     */
    public function getB($start = 0, $end = null)
    {
        if ($start === 0 && $end === null) {
            return $this->b;
        }

        if ($end === null) {
            $length = 1;
        } else {
            $length = $end - $start;
        }

        return array_slice($this->b, $start, $length);
    }

    /**
     * Generate a list of the compiled and grouped opcodes for the differences between the
     * two strings. Generally called by the renderer, this class instantiates the sequence
     * matcher and performs the actual diff generation and return an array of the opcodes
     * for it. Once generated, the results are cached in the diff class instance.
     *
     * @return array Array of the grouped opcodes for the generated diff.
     */
    public function getGroupedOpcodes()
    {
        if (!is_null($this->groupedCodes)) {
            return $this->groupedCodes;
        }

        $sequenceMatcher = new SequenceMatcher($this->a, $this->b, null, $this->options);
        $this->groupedCodes = OpCodeHelper::getGroupedOpcodes(
            $sequenceMatcher->getOpcodes(),
            $this->options['context']
        );

        return $this->groupedCodes;
    }
}
