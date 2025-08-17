<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Watch;

use Innmind\IO\{
    Internal\Stream,
    Internal\Socket\Server,
    Internal\Async\Suspended,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Attempt,
};

/**
 * @internal
 * By default it waits forever for changes
 */
final class Async
{
    /**
     * @psalm-mutation-free
     *
     * @param Maybe<Period> $timeout
     * @param Sequence<Stream|Server> $read
     * @param Sequence<Stream> $write
     */
    private function __construct(
        private Clock $clock,
        private Maybe $timeout,
        private Sequence $read,
        private Sequence $write,
    ) {
    }

    /**
     * @return Attempt<Ready>
     */
    public function __invoke(): Attempt
    {
        /** @var Attempt<Ready> */
        return \Fiber::suspend(Suspended::of(
            $this->clock->now(),
            $this->timeout,
            $this->read,
            $this->write,
        ));
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function new(Clock $clock): self
    {
        /** @var Maybe<Period> */
        $timeout = Maybe::nothing();

        return new self(
            $clock,
            $timeout,
            Sequence::of(),
            Sequence::of(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->clock,
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
            $this->clock,
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
        return new self(
            $this->clock,
            $this->timeout,
            $this
                ->read
                ->append(Sequence::of($read, ...$reads))
                ->distinct(),
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
        return new self(
            $this->clock,
            $this->timeout,
            $this->read,
            $this
                ->write
                ->append(Sequence::of($write, ...$writes))
                ->distinct(),
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
            $this->clock,
            Maybe::all($ownTimeout, $otherTimeout)
                ->map(static fn(Period $own, Period $other) => match (true) {
                    $own->asElapsedPeriod()->longerThan($other->asElapsedPeriod()) => $other,
                    default => $own,
                })
                ->otherwise(static fn() => $ownTimeout)
                ->otherwise(static fn() => $otherTimeout),
            $this->read->append($other->read)->distinct(),
            $this->write->append($other->write)->distinct(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream|Server $stream): self
    {
        return new self(
            $this->clock,
            $this->timeout,
            $this->read->exclude(static fn($known) => $known === $stream),
            $this->write->exclude(static fn($known) => $known === $stream),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function clear(): self
    {
        return self::new($this->clock);
    }
}
