<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Watch;

use Innmind\IO\Internal\{
    Watch,
    Stream,
    Socket\Server,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Map,
    Sequence,
    Maybe,
};

final class Select implements Watch
{
    /**
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

    #[\Override]
    public function __invoke(): Maybe
    {
        if (
            $this->read->empty() &&
            $this->write->empty()
        ) {
            /** @var Sequence<Stream|Server> */
            $read = Sequence::of();
            /** @var Sequence<Stream> */
            $write = Sequence::of();

            return Maybe::just(new Ready($read, $write));
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
            /** @var Maybe<Ready> */
            return Maybe::nothing();
        }

        $readable = $this
            ->read
            ->filter(static fn($resource) => \in_array($resource, $read, true))
            ->values();
        $writable = $this
            ->write
            ->filter(static fn($resource) => \in_array($resource, $write, true))
            ->values();

        return Maybe::just(new Ready($readable, $writable));
    }

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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function poll(): self
    {
        return $this->timeoutAfter(Period::second(0));
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function forRead(
        Stream|Server $read,
        Stream|Server ...$reads,
    ): Watch {
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
    #[\Override]
    public function forWrite(
        Stream $write,
        Stream ...$writes,
    ): Watch {
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
     * @psalm-mutation-free
     */
    #[\Override]
    public function unwatch(Stream|Server $stream): Watch
    {
        $resource = $stream->resource();

        return new self(
            $this->timeout,
            $this->read->remove($resource),
            $this->write->remove($resource),
        );
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
