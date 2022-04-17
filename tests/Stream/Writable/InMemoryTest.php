<?php
declare(strict_types = 1);

namespace Tests\Innmind\IO\Stream\Writable;

use Innmind\IO\Stream\{
    Writable\InMemory,
    Writable,
};
use Innmind\Immutable\Str;
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
}
