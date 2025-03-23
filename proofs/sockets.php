<?php
declare(strict_types = 1);

use Innmind\IO\{
    IO,
    Frame,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

return static function() {
    yield test(
        'IO::fromAmbientAuthority()->sockets()->pair()',
        static function($assert) {
            [$parent, $child] = IO::fromAmbientAuthority()
                ->sockets()
                ->pair()
                ->match(
                    static fn($pair) => $pair,
                    static fn() => [null, null],
                );

            $assert->not()->null($parent);
            $assert->not()->null($child);

            $assert->true(
                $child
                    ->sink(Sequence::of(Str::of('foo')))
                    ->match(
                        static fn() => true,
                        static fn() => false,
                    ),
            );
            $assert->same(
                'foo',
                $parent
                    ->watch()
                    ->frames(Frame::chunk(3)->strict())
                    ->one()
                    ->match(
                        static fn($chunk) => $chunk->toString(),
                        static fn() => null,
                    ),
            );
        },
    );
};
