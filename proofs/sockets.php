<?php
declare(strict_types = 1);

use Innmind\IO\{
    IO,
    Frame,
    Sockets\Unix\Address,
};
use Innmind\TimeContinuum\Period;
use Innmind\Url\Path;
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

    yield test(
        'Socket client poll',
        static function($assert) {
            @\unlink('/tmp/foo.sock');
            $sockets = IO::fromAmbientAuthority()->sockets();
            $address = Address::of(Path::of('/tmp/foo'));

            $server = $sockets
                ->servers()
                ->takeOver($address)
                ->match(
                    static fn($server) => $server,
                    static fn() => null,
                );

            $assert->not()->null($server);

            $client = $sockets
                ->clients()
                ->unix($address)
                ->match(
                    static fn($client) => $client,
                    static fn() => null,
                );

            $assert->not()->null($client);

            $_ = $client
                ->sink(Sequence::of(Str::of('foo')))
                ->match(
                    static fn() => null,
                    static fn() => null,
                );

            $result = $server
                ->timeoutAfter(Period::second(1))
                ->accept()
                ->flatMap(
                    static fn($client) => $client
                        ->poll()
                        ->frames(Frame::chunk(3)->strict())
                        ->one(),
                )
                ->match(
                    static fn($data) => $data->toString(),
                    static fn() => null,
                );

            $assert->same('foo', $result);
            $client->close()->memoize();
            $server->close()->memoize();
        },
    );
};
