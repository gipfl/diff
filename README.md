gipfl\\Diff
===========

ipl-compatible wrapper for php-diff

Usage
-----

```php
<?php

use gipfl\Diff\SideBySideDiff;

require_once 'vendor/autoload.php';

assert($this->parent instanceof \ipl\Html\BaseHtmlElement);
$this->parent->add(SideBySideDiff::create(
    file_get_contents(__DIR__ . '/left.json'),
    file_get_contents(__DIR__ . '/right.json')
));
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
