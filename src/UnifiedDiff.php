<?php

namespace gipfl\Diff;

use Diff_Renderer_Text_Unified as Unified;

class UnifiedDiff
{
    use SimpleDiffHelper;
    protected $rendererClass = Unified::class;
}
