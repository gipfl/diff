<?php

namespace gipfl\Diff;

use gipfl\Diff\PhpDiff\Renderer\Html\SideBySide;
use ipl\Html\ValidHtml;

class SideBySideDiff implements ValidHtml
{
    use SimpleDiffHelper;
    protected $renderClass = SideBySide::class;
}
