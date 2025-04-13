<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
};

/**
 * @internal
 */
final class Reader
{
    /**
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        private Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        private Maybe $encoding,
    ) {
    }

    /**
     * @internal
     *
     * @param Maybe<Str\Encoding> $encoding
     */
    public static function of(
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
    ): self {
        return new self($wait, $encoding);
    }

    /**
     * @param ?int<1, max> $size
     *
     * @return Attempt<Str>
     */
    public function read(?int $size = null): Attempt
    {
        $encoding = $this->encoding;

        return ($this->wait)()
            ->flatMap(
                static fn($stream) => $stream
                    ->read($size)
                    ->recover(static fn($e) => match ($stream->end()) {
                        true => Attempt::result(Str::of('')),
                        false => Attempt::error($e),
                    }),
            )
            ->map(static fn($chunk) => $encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));
    }

    /**
     * @return Attempt<Str>
     */
    public function readLine(): Attempt
    {
        $encoding = $this->encoding;

        return ($this->wait)()
            ->flatMap(
                static fn($stream) => $stream
                    ->readLine()
                    ->recover(static fn($e) => match ($stream->end()) {
                        true => Attempt::result(Str::of('')),
                        false => Attempt::error($e),
                    }),
            )
            ->map(static fn($chunk) => $encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));
    }
}
