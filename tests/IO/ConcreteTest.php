<?php
declare(strict_types = 1);

namespace Tests\Innmind\IO\IO;

use Innmind\IO\{
    IO\Concrete,
    IO,
    Stream\Writable,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Stream\Writable\Stream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ConcreteTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(IO::class, Concrete::of(Factory::build()));
    }

    public function testWriteToStream()
    {
        $this
            ->forAll(Set\Unicode::strings())
            ->then(function($string) {
                $tmp = \tmpfile();
                $stream = Concrete::of(Factory::build())
                    ->streams()
                    ->writeTo(Stream::of($tmp))
                    ->toEncoding('ASCII')
                    ->write(Str::of($string))
                    ->match(
                        static fn($stream) => $stream,
                        static fn() => null,
                    );

                $this->assertInstanceOf(Writable::class, $stream);
                $this->assertSame(
                    $string,
                    \stream_get_contents($tmp, null, 0),
                );
            });
    }
}
