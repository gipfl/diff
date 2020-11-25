<?php

namespace gipfl\Diff;

use Diff_Renderer_Html_Inline as Inline;
use ipl\Html\ValidHtml;

class InlineDiff implements ValidHtml
{
    use SimpleDiffHelper;
    protected $renderClass = Inline::class;
}
