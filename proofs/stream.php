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
            $model = Size::of($unit->times($size));
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
};
