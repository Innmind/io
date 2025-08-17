<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Watch;

use Innmind\IO\{
    Internal\Stream,
    Internal\Socket\Server,
    Exception\RuntimeException,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Map,
    Maybe,
    Attempt
};

/**
 * @internal
 * By default it waits forever for changes
 */
final class Sync
{
    /**
     * @psalm-mutation-free
     *
     * @param Maybe<Period> $timeout
     * @param Map<resource, Stream|Server> $read
     * @param Map<resource, Stream> $write
     */
    private function __construct(
        private Maybe $timeout,
        private Map $read,
        private Map $write,
    ) {
    }

    /**
     * @return Attempt<Ready>
     */
    public function __invoke(): Attempt
    {
        // File streams are never watched as the default implementation of PHP
        // when using stream_select is that the files are always ready.
        // In a standard synchronous process watching them would have no impact.
        // However when watching files across multiple Fibers (thus doing async
        // code) then the files are _synchronized_. File synchronization can be
        // described with a Fiber TA watching a file FA and another Fiber TB
        // watching a file FB. If TA reaches the end of FA before TB reaches the
        // end of FB, stream_select will not return FA as ready until TB reached
        // the end of FB. This means that TA will hang until TB reached the end
        // of FB.
        // At scale this means that all tasks will go as slow as the slowest one
        // of all. The process will hang most of the time.
        // This is a circumvention while this package doesn't truely support
        // async file IO (via ev or uv for example).
        $read = $this->read->exclude(
            static fn($_, $stream) => $stream instanceof Stream && $stream->isFile(),
        );
        $write = $this->write->exclude(
            static fn($_, $stream) => $stream->isFile(),
        );
        $readFiles = $this->read->values()->filter(
            static fn($stream) => $stream instanceof Stream && $stream->isFile(),
        );
        $writeFiles = $this->write->values()->filter(
            static fn($stream) => $stream->isFile(),
        );

        if (
            $read->empty() &&
            $write->empty()
        ) {
            return Attempt::result(new Ready(
                $readFiles,
                $writeFiles,
            ));
        }

        $read = $read->keys()->toList();
        $write = $write->keys()->toList();
        $outOfBand = [];
        [$seconds, $microseconds] = $this
            ->timeout
            ->match(
                self::timeout(...),
                static fn() => [null, null],
            );

        $return = @\stream_select(
            $read,
            $write,
            $outOfBand,
            $seconds,
            $microseconds,
        );

        if ($return === false) {
            /** @var Attempt<Ready> */
            return Attempt::error(new RuntimeException);
        }

        $readable = $this
            ->read
            ->filter(static fn($resource) => \in_array($resource, $read, true))
            ->values()
            ->append($readFiles);
        $writable = $this
            ->write
            ->filter(static fn($resource) => \in_array($resource, $write, true))
            ->values()
            ->append($writeFiles);

        return Attempt::result(new Ready($readable, $writable));
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function new(): self
    {
        /** @var Maybe<Period> */
        $timeout = Maybe::nothing();

        return new self(
            $timeout,
            Map::of(),
            Map::of(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            Maybe::just($timeout),
            $this->read,
            $this->write,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function waitForever(): self
    {
        /** @var Maybe<Period> */
        $timeout = Maybe::nothing();

        return new self(
            $timeout,
            $this->read,
            $this->write,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forRead(Stream|Server $read): self
    {
        return new self(
            $this->timeout,
            ($this->read)(
                $read->resource(),
                $read,
            ),
            $this->write,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forWrite(Stream $write): self
    {
        return new self(
            $this->timeout,
            $this->read,
            ($this->write)(
                $write->resource(),
                $write,
            ),
        );
    }

    /**
     * The new Watch uses the shortest timeout of the both.
     *
     * @psalm-mutation-free
     */
    public function merge(self $other): self
    {
        $ownTimeout = $this->timeout;
        $otherTimeout = $other->timeout;

        return new self(
            Maybe::all($ownTimeout, $otherTimeout)
                ->map(static fn(Period $own, Period $other) => match (true) {
                    $own->asElapsedPeriod()->longerThan($other->asElapsedPeriod()) => $other,
                    default => $own,
                })
                ->otherwise(static fn() => $ownTimeout)
                ->otherwise(static fn() => $otherTimeout),
            $this->read->merge($other->read),
            $this->write->merge($other->write),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream|Server $stream): self
    {
        $resource = $stream->resource();

        return new self(
            $this->timeout,
            $this->read->remove($resource),
            $this->write->remove($resource),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function clear(): self
    {
        return self::new();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function timeout(Period $timeout): array
    {
        $seconds = $timeout->seconds();
        $microseconds = ($timeout->milliseconds() * 1_000) + $timeout->microseconds();

        return [$seconds, $microseconds];
    }
}
