<?php
declare(strict_types = 1);

use Innmind\IO\{
    Stream\Size,
    Stream\Size\Unit,
};
use Innmind\BlackBox\Set;

return static function() {
    yield test(
        'Empty stream size',
        static function($assert) {
            $model = Size::of(0);

            $assert->same(Unit::bytes, $model->unit());
            $assert->same('0B', $model->toString());
        },
    );

    yield proof(
        'Stream sizes',
        given(
            Set::integers()->between(1, 999),
            Set::of(...Unit::cases()),
        ),
        static function($assert, $size, $unit) {
            $model = $unit->of($size);
            $extension = match ($unit) {
                Unit::bytes => 'B',
                Unit::kilobytes => 'KB',
                Unit::megabytes => 'MB',
                Unit::gigabytes => 'GB',
                Unit::terabytes => 'TB',
                Unit::petabytes => 'PB',
            };

            $assert->same($unit, $model->unit());
            $assert->same("$size$extension", $model->toString());
        },
    );

    yield proof(
        'Stream::lessThan()',
        given(
            Set::integers()->above(0),
            Set::integers()->above(1),
        )->filter(static fn($a, $b) => \is_int($a + $b)),
        static function($assert, $size, $additionnal) {
            $model = Size::of($size);

            $assert->false($model->lessThan($model));
            $assert->true($model->lessThan(Size::of($size + $additionnal)));
            $assert->false(Size::of($size + $additionnal)->lessThan($model));
        },
    );
};
