#!/usr/bin/env php
<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Lace\Ainstruct\Application;
use Lace\Ainstruct\Bootstrap\AppServiceProvider;

$autoloadCandidates = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
];

$autoloaded = false;

foreach ($autoloadCandidates as $file) {
    if (is_file($file)) {
        require $file;
        $autoloaded = true;
        break;
    }
}

if (! $autoloaded) {
    fwrite(STDERR, "Unable to find the Composer autoloader. Run `composer install` first.\n");
    exit(1);
}

$container = new Container;
(new AppServiceProvider($container))->register();

exit((new Application($container))->run(array_slice($argv, 1)));
