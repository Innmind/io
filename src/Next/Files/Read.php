<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Files;

use Innmind\IO\{
    Next\Stream\Size,
    Previous\IO as Previous,
    Internal,
    Internal\Capabilities,
    Previous\Readable,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

final class Read
{
    /**
     * @param \Closure(): Readable\Stream $load
     */
    private function __construct(
        private \Closure $load,
        private bool $autoClose,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Previous $io,
        Capabilities $capabilities,
        Path $path,
    ): self {
        return new self(
            static fn() => $capabilities
                ->files()
                ->read($path)
                ->map($io->readable()->wrap(...))
                ->match(
                    static fn($stream) => $stream,
                    static fn() => throw new \RuntimeException('Failed to read file'),
                ),
            true,
        );
    }

    /**
     * @internal
     */
    public static function temporary(Readable $io, Internal\Stream $stream): self
    {
        return new self(
            static fn() => $stream
                ->rewind()
                ->map(static fn() => $stream)
                ->map($io->wrap(...))
                ->match(
                    static fn($stream) => $stream,
                    static fn() => throw new \RuntimeException('Failed to read file'),
                ),
            false,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        $load = $this->load;

        return new self(
            static fn() => $load()->toEncoding($encoding),
            $this->autoClose,
        );
    }

    /**
     * This is only useful in case the code is called in an asynchronous context
     * as it allows the current code to inform the event loop we're doing IO.
     *
     * Otherwise this call is useless as files are always ready to be read.
     *
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        $load = $this->load;

        return new self(
            static fn() => $load()->watch(),
            $this->autoClose,
        );
    }

    /**
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        return ($this->load)()->size();
    }

    /**
     * @param int<1, max> $size
     *
     * @return Sequence<Str>
     */
    public function chunks(int $size): Sequence
    {
        return ($this->load)()
            ->chunks($size)
            ->lazy()
            ->rewindable() // todo handle auto close when dealing with a path
            ->sequence();
    }

    /**
     * @return Sequence<Str>
     */
    public function lines(): Sequence
    {
        return ($this->load)()
            ->lines()
            ->lazy()
            ->rewindable() // todo handle auto close when dealing with a path
            ->sequence();
    }
}
