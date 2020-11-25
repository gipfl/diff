<?php

namespace gipfl\Diff;

use Diff_Renderer_Html_SideBySide as SideBySide;
use ipl\Html\ValidHtml;

class SideBySideDiff implements ValidHtml
{
    use SimpleDiffHelper;
    protected $renderClass = SideBySide::class;
}
