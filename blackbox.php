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
    ->map(static fn($app) => match (\getenv('BLACKBOX_ENV')) {
        'extensive' => $app->scenariiPerProof(1_000),
        'coverage' => $app
            ->codeCoverage(
                CodeCoverage::of(
                    __DIR__.'/src/',
                    __DIR__.'/tests/',
                    __DIR__.'/proofs/',
                )
                    ->dumpTo('coverage.clover'),
            ),
        default => $app,
    })
    ->tryToProve(static function(Prove $prove) {
        yield from PHPUnit\Load::testsAt(__DIR__.'/tests/');
        yield from Load::everythingIn(__DIR__.'/proofs/')($prove);
    })
    ->exit();
