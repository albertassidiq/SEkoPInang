<?php

// Intentionally independent of Composer so an unsafe CLI fails before install.
require_once __DIR__.'/../app/Support/PhpRuntime.php';

if (! App\Support\PhpRuntime::isSupported(PHP_VERSION)) {
    fwrite(STDERR, 'Unsupported PHP '.PHP_VERSION.'. Use PHP 8.5.12 or a newer stable 8.5 patch (php2shell security fixes).'.PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'PHP '.PHP_VERSION.' ('.PHP_SAPI.'): runtime version check passed. Verify the web handler and restart all workers separately.'.PHP_EOL);
