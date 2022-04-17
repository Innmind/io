<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams;

use Innmind\IO\{
    Streams,
    Stream\Writable,
    Exception\RuntimeException,
};
use Innmind\OperatingSystem\Sockets;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\{
    Writable as LowLevel,
    Selectable,
};
use Innmind\Immutable\{
    Maybe,
    Str,
    SideEffect,
};

final class Concrete implements Streams
{
    private Sockets $sockets;

    private function __construct(Sockets $sockets)
    {
        $this->sockets = $sockets;
    }

    public static function of(Sockets $sockets): self
    {
        return new self($sockets);
    }

    public function writeTo(LowLevel $stream): Writable
    {
        /** @psalm-suppress InvalidArgument We cheat with the purity here */
        return Writable\Stream::of(
            $stream,
            $this->availableForWrite(...),
            $this->write(...),
            $this->close(...),
        );
    }

    /**
     * @param LowLevel&Selectable $stream
     *
     * @return Maybe<LowLevel>
     */
    private function availableForWrite(
        LowLevel $stream,
        ?ElapsedPeriod $timeout,
    ): Maybe {
        $watch = $this
            ->sockets
            ->watch($timeout)
            ->forWrite($stream);

        /** @var Maybe<LowLevel> */
        return $watch()
            ->flatMap(static fn($ready) => $ready->toWrite()->find(
                static fn($ready) => $ready === $stream,
            ))
            ->match(
                static fn($stream) => Maybe::just($stream),
                static fn() => Maybe::nothing(),
            );
    }

    private function write(LowLevel $stream, Str $data): Maybe
    {
        /** @var Maybe<LowLevel> */
        return $stream
            ->write($data)
            ->match(
                static fn($stream) => Maybe::just($stream),
                static fn() => Maybe::nothing(),
            );
    }

    /**
     * @throws RuntimeException
     */
    private function close(LowLevel $stream): SideEffect
    {
        return $stream
            ->close()
            ->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => $stream->closed() ? new SideEffect : throw new RuntimeException('Cannot close the stream'),
            );
    }
}
