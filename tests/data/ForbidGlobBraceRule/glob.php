<?php

declare(strict_types=1);

$files = glob('*.{jpg,jpeg,png}', GLOB_BRACE);

// Should also detect when used with other constants
$files = glob('*.{jpg,jpeg,png}', GLOB_BRACE | GLOB_NOSORT);
