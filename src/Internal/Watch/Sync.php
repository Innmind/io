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
    Sequence,
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
        if (
            $this->read->empty() &&
            $this->write->empty()
        ) {
            /** @var Sequence<Stream|Server> */
            $read = Sequence::of();
            /** @var Sequence<Stream> */
            $write = Sequence::of();

            return Attempt::result(new Ready($read, $write));
        }

        $read = $this->read->keys()->toList();
        $write = $this->write->keys()->toList();
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
            ->values();
        $writable = $this
            ->write
            ->filter(static fn($resource) => \in_array($resource, $write, true))
            ->values();

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
    public function forRead(
        Stream|Server $read,
        Stream|Server ...$reads,
    ): self {
        $streams = ($this->read)(
            $read->resource(),
            $read,
        );

        foreach ($reads as $read) {
            $streams = $streams(
                $read->resource(),
                $read,
            );
        }

        return new self(
            $this->timeout,
            $streams,
            $this->write,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forWrite(
        Stream $write,
        Stream ...$writes,
    ): self {
        $streams = ($this->write)(
            $write->resource(),
            $write,
        );

        foreach ($writes as $write) {
            $streams = $streams(
                $write->resource(),
                $write,
            );
        }

        return new self(
            $this->timeout,
            $this->read,
            $streams,
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
