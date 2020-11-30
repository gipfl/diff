gipfl\\Diff
===========

ipl-compatible modernized php-diff fork

Usage
-----

```php
<?php

use gipfl\Diff\HtmlRenderer\SideBySideDiff;
use gipfl\Diff\PhpDiff;

require_once 'vendor/autoload.php';

$diff = new PhpDiff(
    file_get_contents(__DIR__ . '/left.json'),
    file_get_contents(__DIR__ . '/right.json')
);
$this->parent->add(new SideBySideDiff($diff));
```

Credits
-------

* Copyright (c) 2009 Chris Boulton <chris.boulton@interspire.com>
* Copyright (c) 2020 Thomas Gelf <thomas@gelf.net>

This started based on the great work of [Chris Boulton](https://github.com/chrisboulton/php-diff),
which has been abandoned. Tried various forks, worked with the forks maintained
by the [Phalcon Framework Team](https://github.com/phalcongelist/php-diff) and
the one maintained by [PHPSpec](https://github.com/phpspec/php-diff) for a
little while. Then finally decided to fork and modernize the code by myself.
