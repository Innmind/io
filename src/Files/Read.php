<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\{
    Stream\Size,
    Internal,
    Exception\FailedToLoadStream,
    Internal\Capabilities,
    Internal\Watch,
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
     * @param \Closure(): Internal\Stream $load
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        private \Closure $load,
        private Watch $watch,
        private Maybe $encoding,
        private bool $autoClose,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
        Path $path,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();

        return new self(
            static fn() => $capabilities
                ->files()
                ->read($path)
                ->match(
                    static fn($stream) => $stream,
                    static fn() => throw new \RuntimeException('Failed to read file'),
                ),
            $capabilities->watch(),
            $encoding,
            true,
        );
    }

    /**
     * @internal
     */
    public static function temporary(
        Capabilities $capabilities,
        Internal\Stream $stream,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();

        return new self(
            static fn() => $stream,
            $capabilities->watch(),
            $encoding,
            false,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->load,
            $this->watch,
            Maybe::just($encoding),
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
        return new self(
            $this->load,
            $this->watch->waitForever(),
            $this->encoding,
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
        $load = $this->load;
        $watch = $this->watch;
        $autoClose = $this->autoClose;

        $chunks = Sequence::lazy(static function($register) use ($size, $load, $watch, $autoClose) {
            $stream = $load();
            $wait = Internal\Stream\Wait::of($watch, $stream);
            $rewind = static fn(): null => $stream->rewind()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadStream,
            );

            $register(static fn() => $rewind());
            $rewind();

            do {
                // we yield an empty line when the read() call doesn't return
                // anything otherwise it will fail to load empty streams or
                // streams ending with the "end of line" character
                yield $wait()
                    ->flatMap(static fn($stream) => $stream->read($size))
                    ->match(
                        static fn($chunk) => $chunk,
                        static fn() => match ($stream->end()) {
                            true => Str::of(''),
                            false => throw new FailedToLoadStream,
                        },
                    );
            } while (!$stream->end());

            $rewind();

            if ($autoClose) {
                $stream->close()->match(
                    static fn() => null,
                    static fn() => throw new FailedToLoadStream,
                );
            }
        });

        return $this->encoding->match(
            static fn($encoding) => $chunks->map(
                static fn($chunk) => $chunk->toEncoding($encoding),
            ),
            static fn() => $chunks,
        );
    }

    /**
     * @return Sequence<Str>
     */
    public function lines(): Sequence
    {
        $load = $this->load;
        $watch = $this->watch;
        $autoClose = $this->autoClose;

        $chunks = Sequence::lazy(static function($register) use ($load, $watch, $autoClose) {
            $stream = $load();
            $wait = Internal\Stream\Wait::of($watch, $stream);
            $rewind = static fn(): null => $stream->rewind()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadStream,
            );

            $register(static fn() => $rewind());
            $rewind();

            do {
                // we yield an empty line when the readLine() call doesn't return
                // anything otherwise it will fail to load empty streams or
                // streams ending with the "end of line" character
                yield $wait()
                    ->flatMap(static fn($stream) => $stream->readLine())
                    ->match(
                        static fn($chunk) => $chunk,
                        static fn() => match ($stream->end()) {
                            true => Str::of(''),
                            false => throw new FailedToLoadStream,
                        },
                    );
            } while (!$stream->end());

            $rewind();

            if ($autoClose) {
                $stream->close()->match(
                    static fn() => null,
                    static fn() => throw new FailedToLoadStream,
                );
            }
        });

        return $this->encoding->match(
            static fn($encoding) => $chunks->map(
                static fn($chunk) => $chunk->toEncoding($encoding),
            ),
            static fn() => $chunks,
        );
    }
}
