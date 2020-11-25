<?php

namespace gipfl\Diff;

use gipfl\Diff\PhpDiff\Renderer\AbstractRenderer;
use LogicException;
use function explode;

/**
 * @internal
 */
trait SimpleDiffHelper
{
    protected $left;

    protected $right;

    /** @var PhpDiff */
    protected $diff;

    /** @var AbstractRenderer */
    protected $renderer;

    public function __construct($left, $right)
    {
        if (empty($left)) {
            $this->left = [];
        } else {
            $this->left = explode("\n", (string) $left);
        }

        if (empty($right)) {
            $this->right = [];
        } else {
            $this->right = explode("\n", (string) $right);
        }

        $options = [
            'context' => 5,
            // 'ignoreWhitespace' => true,
            // 'ignoreCase' => true,
        ];
        $this->diff = new PhpDiff($this->left, $this->right, $options);
    }

    public static function create($left, $right)
    {
        return new static($left, $right);
    }

    public function render()
    {
        return (string) $this->diff->Render($this->getRenderer());
    }

    /**
     * @return AbstractRenderer
     */
    protected function getRenderer()
    {
        if ($this->renderer === null) {
            $this->renderer = $this->createRenderer();
        }

        return $this->renderer;
    }

    /**
     * @return string
     */
    protected function createRenderer()
    {
        if (isset($this->renderClass)) {
            return new $this->renderClass;
        }

        throw new LogicException('SimpleDiffHelper: a renderClass is required');
    }

    public function __toString()
    {
        return $this->render();
    }
}
