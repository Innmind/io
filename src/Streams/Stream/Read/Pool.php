<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream\Read;

use Innmind\IO\{
    Streams\Stream\Read,
    Internal,
    Internal\Capabilities,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Pair,
    Predicate\Instance,
};

/**
 * @template T
 */
final class Pool
{
    /**
     * @param Map<Internal\Stream, T> $streams
     */
    private function __construct(
        private Capabilities $capabilities,
        private Map $streams,
        private ?Period $timeout,
        private ?Str\Encoding $encoding,
        private bool $blocking,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param A $id
     *
     * @return self<A>
     */
    public static function of(
        Capabilities $capabilities,
        Read $stream,
        mixed $id,
    ): self {
        return new self(
            $capabilities,
            Map::of([$stream->internal(), $id]),
            null,
            null,
            true,
        );
    }

    /**
     * @template U
     *
     * @param U $id
     *
     * @return self<T|U>
     */
    public function with(mixed $id, Read $stream): self
    {
        /** @psalm-suppress InvalidArgument Due to the id union */
        return new self(
            $this->capabilities,
            ($this->streams)($stream->internal(), $id),
            $this->timeout,
            $this->encoding,
            $this->blocking,
        );
    }

    /**
     * @return self<T>
     */
    public function poll(): self
    {
        return $this->timeoutAfter(Period::second(0));
    }

    /**
     * @return self<T>
     */
    public function watch(): self
    {
        return new self(
            $this->capabilities,
            $this->streams,
            null,
            $this->encoding,
            $this->blocking,
        );
    }

    /**
     * @return self<T>
     */
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->capabilities,
            $this->streams,
            $timeout,
            $this->encoding,
            $this->blocking,
        );
    }

    /**
     * @return self<T>
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->capabilities,
            $this->streams,
            $this->timeout,
            $encoding,
            $this->blocking,
        );
    }

    /**
     * @return self<T>
     */
    public function nonBlocking(): self
    {
        return new self(
            $this->capabilities,
            $this->streams,
            $this->timeout,
            $this->encoding,
            false,
        );
    }

    /**
     * @return Sequence<Pair<T, Str>>
     */
    public function chunks(): Sequence
    {
        $blocking = $this->blocking;
        $watch = $this->capabilities->watch();
        $watch = match ($this->timeout) {
            null => $watch->waitForever(),
            default => $watch->timeoutAfter($this->timeout),
        };
        $watch = $this
            ->streams
            ->keys()
            ->filter(static fn($stream) => !$stream->closed())
            ->flatMap(
                static fn($stream) => (match ($blocking) {
                    true => $stream->blocking(),
                    false => $stream->nonBlocking(),
                })
                    ->map(static fn() => $stream)
                    ->toSequence()
                    ->toSet(),
            )
            ->reduce(
                $watch,
                static fn(Internal\Watch $watch, $stream) => $watch->forRead($stream),
            );
        $streams = $this->streams;

        $chunks = $watch()
            ->maybe()
            ->toSequence()
            ->flatMap(static fn($ready) => $ready->toRead())
            ->keep(Instance::of(Internal\Stream::class))
            ->flatMap(
                static fn($stream) => $streams
                    ->get($stream)
                    ->map(static fn($id) => new Pair(
                        $id,
                        $stream->read()->match(
                            static fn($chunk) => $chunk,
                            static fn() => Str::of(''),
                        ),
                    ))
                    ->toSequence(),
            );

        if ($this->encoding) {
            $encoding = $this->encoding;
            $chunks = $chunks->map(static fn($pair) => new Pair(
                $pair->key(),
                $pair->value()->toEncoding($encoding),
            ));
        }

        return $chunks;
    }
}
