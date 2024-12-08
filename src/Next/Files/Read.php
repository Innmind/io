<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Files;

use Innmind\IO\Next\Stream\Size;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

final class Read
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
    public function toEncoding(Str\Encoding $encoding): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return $this;
    }

    /**
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        /** @var Maybe<Size> */
        return Maybe::nothing();
    }

    /**
     * @param int<1, max> $size
     *
     * @return Sequence<Str>
     */
    public function chunks(int $size): Sequence
    {
        return Sequence::of();
    }

    /**
     * @return Sequence<Str>
     */
    public function lines(): Sequence
    {
        return Sequence::of();
    }
}
