<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams\Stream;

use Innmind\IO\Next\{
    Streams\Stream\Read\Frames,
    Frame,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\Str;

final class Read
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
    public function nonBlocking(): self
    {
        return $this;
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
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function buffer(): self
    {
        return $this;
    }

    /**
     * @template T
     *
     * @param Frame<T> $frame
     *
     * @return Frames<T>
     */
    public function frames(Frame $frame): Frames
    {
        return Frames::of($this->resource, $frame);
    }
}
