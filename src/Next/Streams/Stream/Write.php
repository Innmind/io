<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream;

use Innmind\IO\Internal;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    SideEffect,
};

final class Write
{
    private function __construct(
        private Internal\Stream\Writable $stream,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Internal\Stream\Writable $stream): self
    {
        return new self($stream);
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        // todo
        return $this;
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<SideEffect>
     */
    public function sink(Sequence $chunks): Maybe
    {
        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink($this->stream)
            ->maybe(
                static fn($stream, $chunk) => $stream
                    ->write($chunk)
                    ->maybe(),
            )
            ->map(static fn() => new SideEffect);
    }
}
