<?php
declare(strict_types = 1);

namespace Innmind\IO\Files\Temporary;

use Innmind\IO\{
    Stream\Size,
    Internal,
    Internal\Capabilities,
    Internal\Watch,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
};

final class Pull
{
    /**
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        private Internal\Stream $stream,
        private Watch $watch,
        private Maybe $encoding,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
        Internal\Stream $stream,
    ): self {
        /** @var Maybe<Str\Encoding> */
        $encoding = Maybe::nothing();

        return new self(
            $stream,
            $capabilities->watch(),
            $encoding,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->stream,
            $this->watch,
            Maybe::just($encoding),
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
            $this->stream,
            $this->watch->waitForever(),
            $this->encoding,
        );
    }

    /**
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        return $this->stream->size();
    }

    /**
     * @param int<1, max> $size
     *
     * @return Attempt<Str>
     */
    public function chunk(int $size): Attempt
    {
        $stream = $this->stream;
        $wait = Internal\Stream\Wait::of($this->watch, $stream);

        // we yield an empty line when the read() call doesn't return anything
        // otherwise it will fail to load empty streams or streams ending with
        // the "end of line" character
        $chunk = $wait()
            ->flatMap(static fn($stream) => $stream->read($size))
            ->recover(static fn($e) => match ($stream->end()) {
                true => Attempt::result(Str::of('')),
                false => Attempt::error($e),
            });

        return $this->encoding->match(
            static fn($encoding) => $chunk->map(
                static fn($chunk) => $chunk->toEncoding($encoding),
            ),
            static fn() => $chunk,
        );
    }
}
