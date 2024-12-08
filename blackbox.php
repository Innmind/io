<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

use Innmind\BlackBox\{
    Application,
    PHPUnit\Load,
    Runner\CodeCoverage,
};

Application::new($argv)
    ->when(
        \getenv('ENABLE_COVERAGE') !== false,
        static fn(Application $app) => $app
            ->codeCoverage(
                CodeCoverage::of(
                    __DIR__.'/src/',
                    __DIR__.'/tests/',
                )
                    ->dumpTo('coverage.clover')
                    ->enableWhen(true),
            ),
    )
    ->tryToProve(Load::directory(__DIR__.'/tests/'))
    ->exit();
