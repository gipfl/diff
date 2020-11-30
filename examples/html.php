<?php

use gipfl\Diff\HtmlRenderer\SideBySideDiff;
use gipfl\Diff\PhpDiff;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$diff = new PhpDiff(
    file_get_contents(__DIR__ . '/left.json'),
    file_get_contents(__DIR__ . '/right.json')
);
$this->parent->add(new SideBySideDiff($diff));
