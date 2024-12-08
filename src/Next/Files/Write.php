<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Files;

use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    SideEffect,
};

final class Write
{
    private function __construct(
        private Path $path,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Path $path): self
    {
        return new self($path);
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
