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

