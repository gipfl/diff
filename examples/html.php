<?php

use gipfl\Diff\SideBySideDiff;

require_once dirname(__DIR__) . '/vendor/autoload.php';

echo SideBySideDiff::create(
    file_get_contents(__DIR__ . '/left.json'),
    file_get_contents(__DIR__ . '/right.json')
);
