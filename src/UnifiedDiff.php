<?php

namespace gipfl\Diff;

use gipfl\Diff\PhpDiff\Renderer\Text\Unified;

class UnifiedDiff
{
    use SimpleDiffHelper;
    protected $rendererClass = Unified::class;
}
