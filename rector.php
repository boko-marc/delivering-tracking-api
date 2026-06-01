<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/modules',
        __DIR__.'/routes',
        __DIR__.'/tests',

    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
        __DIR__.'/vendor',
    ])
    ->withoutParallel()
    ->withPhpSets()
    ->withPreparedSets(
        codeQuality: true,
    );
