<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\Immutable\{
    Str,
    Maybe,
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
     * @return Maybe<Str>
     */
    public function read(?int $size = null): Maybe
    {
        $encoding = $this->encoding;

        return ($this->wait)()
            ->flatMap(
                static fn($stream) => $stream
                    ->read($size)
                    ->otherwise(static fn() => Maybe::just(Str::of(''))->filter(
                        static fn() => $stream->end(),
                    )),
            )
            ->map(static fn($chunk) => $encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));
    }

    /**
     * @return Maybe<Str>
     */
    public function readLine(): Maybe
    {
        $encoding = $this->encoding;

        return ($this->wait)()
            ->flatMap(
                static fn($stream) => $stream
                    ->readLine()
                    ->otherwise(static fn() => Maybe::just(Str::of(''))->filter(
                        static fn() => $stream->end(),
                    )),
            )
            ->map(static fn($chunk) => $encoding->match(
                static fn($encoding) => $chunk->toEncoding($encoding),
                static fn() => $chunk,
            ));
    }
}
