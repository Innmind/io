<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

use Innmind\BlackBox\{
    Application,
    Runner\Load,
    Runner\CodeCoverage,
    PHPUnit,
    Prove,
};

Application::new($argv)
    ->when(
        \getenv('BLACKBOX_SET_SIZE') !== false,
        static fn(Application $app) => $app->scenariiPerProof((int) \getenv('BLACKBOX_SET_SIZE')),
    )
    ->when(
        \getenv('ENABLE_COVERAGE') !== false,
        static fn(Application $app) => $app
            ->codeCoverage(
                CodeCoverage::of(
                    __DIR__.'/src/',
                    __DIR__.'/tests/',
                    __DIR__.'/proofs/',
                )
                    ->dumpTo('coverage.clover'),
            ),
    )
    ->tryToProve(static function(Prove $prove) {
        yield from PHPUnit\Load::testsAt(__DIR__.'/tests/');
        yield from Load::everythingIn(__DIR__.'/proofs/')($prove);
    })
    ->exit();
