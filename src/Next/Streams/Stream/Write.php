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
        private Internal\Stream\Stream $stream,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Internal\Stream\Stream $stream): self
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
        $stream = $this->stream;

        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink(new SideEffect)
            ->maybe(
                static fn($_, $chunk) => $stream
                    ->write($chunk)
                    ->maybe(),
            );
    }
}
