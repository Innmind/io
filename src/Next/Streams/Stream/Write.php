<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream;

use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    SideEffect,
};

final class Write
{
    /**
     * @param resource $resource
     */
    private function __construct(
        private $resource,
    ) {
    }

    /**
     * @internal
     *
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self($resource);
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return $this;
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<SideEffect>
     */
    public function sink(Sequence $chunks): Maybe
    {
        return Maybe::just(new SideEffect);
    }
}
