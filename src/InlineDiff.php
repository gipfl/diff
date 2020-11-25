<?php

namespace gipfl\Diff;

use gipfl\Diff\PhpDiff\Renderer\Html\Inline;
use ipl\Html\ValidHtml;

class InlineDiff implements ValidHtml
{
    use SimpleDiffHelper;
    protected $renderClass = Inline::class;
}
