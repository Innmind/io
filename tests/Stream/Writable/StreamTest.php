<?php
declare(strict_types = 1);

namespace Tests\Innmind\IO\Stream\Writable;

use Innmind\IO\Stream\{
    Writable\Stream,
    Writable,
};
use Innmind\Stream\Writable as LowLevelStream;
use Innmind\Immutable\{
    Str,
    Maybe,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class StreamTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Writable::class,
            Stream::of(
                $this->createMock(LowLevelStream::class),
                static fn() => null,
                static fn() => null,
            ),
        );
    }

    public function testWrite()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($string) => Str::of($string),
                    Set\Unicode::strings(),
                ),
            ))
            ->then(function($chunks) {
                $tmp = \tmpfile();
                $stream = Stream::of(
                    LowLevelStream\Stream::of($tmp),
                    static fn($stream) => Maybe::just($stream),
                    static fn($stream, $data) => $stream
                        ->write($data->toEncoding('ASCII'))
                        ->match(
                            static fn($stream) => Maybe::just($stream),
                            static fn() => Maybe::nothing(),
                        ),
                );

                foreach ($chunks as $chunk) {
                    $stream = $stream
                        ->write($chunk)
                        ->match(
                            static fn($stream) => $stream,
                            static fn() => null,
                        );

                    $this->assertInstanceOf(Writable::class, $stream);
                }

                $this->assertSame(
                    \implode('', \array_map(
                        static fn($chunk) => $chunk->toString(),
                        $chunks,
                    )),
                    \stream_get_contents($tmp, null, 0),
                );
            });
    }
}
