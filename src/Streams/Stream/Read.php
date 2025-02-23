<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream;

use Innmind\IO\{
    Streams\Stream\Read\Frames,
    Streams\Stream\Read\Pool,
    Frame,
    Internal\Capabilities,
    Internal\Stream,
    Internal\Watch,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Read
{
    /**
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        private Capabilities $capabilities,
        private Stream $stream,
        private Watch $watch,
        private Maybe $encoding,
        private bool $blocking,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
        Stream $stream,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();

        return new self(
            $capabilities,
            $stream,
            $capabilities->watch(),
            $encoding,
            true,
        );
    }

    /**
     * @internal
     */
    public function internal(): Stream
    {
        return $this->stream;
    }

    /**
     * @psalm-mutation-free
     */
    public function nonBlocking(): self
    {
        return new self(
            $this->capabilities,
            $this->stream,
            $this->watch,
            $this->encoding,
            false,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->capabilities,
            $this->stream,
            $this->watch,
            Maybe::just($encoding),
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->capabilities,
            $this->stream,
            $this->watch->waitForever(),
            $this->encoding,
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $period): self
    {
        return new self(
            $this->capabilities,
            $this->stream,
            $this->watch->timeoutAfter($period),
            $this->encoding,
            $this->blocking,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function poll(): self
    {
        return $this->timeoutAfter(Period::second(0));
    }

    /**
     * @psalm-mutation-free
     */
    public function buffer(): self
    {
        // todo
        return $this;
    }

    /**
     * @template T
     *
     * @param T $id
     *
     * @return Pool<T>
     */
    public function pool(mixed $id): Pool
    {
        return Pool::of(
            $this->capabilities,
            $this,
            $id,
        );
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
        return Frames::of(
            $this->stream,
            $this->watch,
            $this->encoding,
            $frame,
            $this->blocking,
        );
    }
}
