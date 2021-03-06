<?php
declare(strict_types = 1);

namespace Tests\Innmind\IO\Stream\Writable;

use Innmind\IO\Stream\{
    Writable\InMemory,
    Writable,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Immutable\{
    Str,
    SideEffect,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class InMemoryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Writable::class, InMemory::open());
    }

    public function testAlwaysWritable()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($string) => Str::of($string),
                    Set\Unicode::strings(),
                ),
            ))
            ->then(function($chunks) {
                $stream = InMemory::open();

                foreach ($chunks as $chunk) {
                    $stream = $stream
                        ->write($chunk)
                        ->match(
                            static fn($stream) => $stream,
                            static fn() => null,
                        );

                    $this->assertInstanceOf(InMemory::class, $stream);
                }
            });
    }

    public function testExtractWrittenData()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($string) => Str::of($string),
                    Set\Unicode::strings(),
                ),
            ))
            ->then(function($chunks) {
                $stream = InMemory::open();

                foreach ($chunks as $chunk) {
                    $stream = $stream
                        ->write($chunk)
                        ->match(
                            static fn($stream) => $stream,
                            static fn() => null,
                        );
                }

                $this->assertSame($chunks, $stream->chunks()->toList());
            });
    }

    public function testToEncoding()
    {
        $this
            ->forAll(
                Set\Sequence::of(
                    Set\Decorate::immutable(
                        static fn($string) => Str::of($string),
                        Set\Unicode::strings(),
                    ),
                ),
                Set\Elements::of('UTF-8', 'ASCII'),
            )
            ->then(function($chunks, $encoding) {
                $stream = InMemory::open()->toEncoding($encoding);

                foreach ($chunks as $chunk) {
                    $stream = $stream
                        ->write($chunk)
                        ->match(
                            static fn($stream) => $stream,
                            static fn() => null,
                        );
                }

                $stream->chunks()->foreach(fn($chunk) => $this->assertSame(
                    $encoding,
                    $chunk->encoding()->toString(),
                ));
            });
    }

    public function testTimeoutHasNoEffect()
    {
        $this
            ->forAll(Set\Integers::between(0, 1_000_000))
            ->then(function($timeout) {
                $stream = InMemory::open();

                $this->assertSame(
                    $stream,
                    $stream->timeoutAfter(new ElapsedPeriod($timeout)),
                );
            });
    }

    public function testTerminate()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($string) => Str::of($string),
                    Set\Unicode::strings(),
                ),
            ))
            ->then(function($chunks) {
                $stream = InMemory::open();

                foreach ($chunks as $chunk) {
                    $stream = $stream
                        ->write($chunk)
                        ->match(
                            static fn($stream) => $stream,
                            static fn() => null,
                        );
                }

                $this->assertInstanceOf(SideEffect::class, $stream->terminate());
            });
    }
}
