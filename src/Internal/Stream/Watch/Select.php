<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Watch;

use Innmind\IO\Internal\{
    Stream\Watch,
    Stream\Stream,
    Socket\Server,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Immutable\{
    Map,
    Sequence,
    Maybe,
};

final class Select implements Watch
{
    /** @var Maybe<ElapsedPeriod> */
    private Maybe $timeout;
    /** @var Map<resource, Stream|Server> */
    private Map $read;
    /** @var Map<resource, Stream> */
    private Map $write;
    /** @var list<resource> */
    private array $readResources;
    /** @var list<resource> */
    private array $writeResources;

    private function __construct(?ElapsedPeriod $timeout = null)
    {
        $this->timeout = Maybe::of($timeout);
        /** @var Map<resource, Stream|Server> */
        $this->read = Map::of();
        /** @var Map<resource, Stream> */
        $this->write = Map::of();
        $this->readResources = [];
        $this->writeResources = [];
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

        $read = $this->readResources;
        $write = $this->writeResources;
        $outOfBand = [];
        [$seconds, $microseconds] = $this
            ->timeout
            ->match(
                $this->timeout(...),
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

    public static function timeoutAfter(ElapsedPeriod $timeout): self
    {
        return new self($timeout);
    }

    public static function waitForever(): self
    {
        return new self;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function forRead(
        Stream|Server $read,
        Stream|Server ...$reads,
    ): Watch {
        $self = clone $this;
        $self->read = ($self->read)(
            $read->resource(),
            $read,
        );
        $self->readResources[] = $read->resource();

        foreach ($reads as $read) {
            $self->read = ($self->read)(
                $read->resource(),
                $read,
            );
            $self->readResources[] = $read->resource();
        }

        return $self;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function forWrite(
        Stream $write,
        Stream ...$writes,
    ): Watch {
        $self = clone $this;
        $self->write = ($self->write)(
            $write->resource(),
            $write,
        );
        $self->writeResources[] = $write->resource();

        foreach ($writes as $write) {
            $self->write = ($self->write)(
                $write->resource(),
                $write,
            );
            $self->writeResources[] = $write->resource();
        }

        return $self;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function unwatch(Stream|Server $stream): Watch
    {
        $resource = $stream->resource();
        $self = clone $this;
        $self->read = $self->read->remove($resource);
        $self->write = $self->write->remove($resource);
        /** @var list<resource> */
        $self->readResources = \array_values(\array_filter(
            $self->readResources,
            static function($read) use ($resource): bool {
                return $read !== $resource;
            },
        ));
        /** @var list<resource> */
        $self->writeResources = \array_values(\array_filter(
            $self->writeResources,
            static function($write) use ($resource): bool {
                return $write !== $resource;
            },
        ));

        return $self;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function timeout(ElapsedPeriod $timeout): array
    {
        $period = $timeout->asPeriod();
        $seconds = $period->seconds();
        $microseconds = ($period->milliseconds() * 1_000) + $period->microseconds();

        return [$seconds, $microseconds];
    }
}
