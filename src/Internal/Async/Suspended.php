<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Async;

use Innmind\IO\Internal\{
    Watch\Ready,
    Stream,
    Socket\Server,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Period,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

/**
 * @internal
 */
final class Suspended
{
    /**
     * @param Maybe<Period> $timeout
     * @param Sequence<Stream|Server> $read
     * @param Sequence<Stream> $write
     */
    private function __construct(
        private PointInTime $at,
        private Maybe $timeout,
        private Sequence $read,
        private Sequence $write,
    ) {
    }

    /**
     * @param Maybe<Period> $timeout
     * @param Sequence<Stream|Server> $read
     * @param Sequence<Stream> $write
     */
    public static function of(
        PointInTime $at,
        Maybe $timeout,
        Sequence $read,
        Sequence $write,
    ): self {
        return new self(
            $at,
            $timeout,
            $read,
            $write,
        );
    }

    /**
     * @return Maybe<Period>
     */
    public function timeout(): Maybe
    {
        return $this->timeout;
    }

    /**
     * @return Sequence<Stream|Server>
     */
    public function read(): Sequence
    {
        return $this->read;
    }

    /**
     * @return Sequence<Stream>
     */
    public function write(): Sequence
    {
        return $this->write;
    }

    public function next(
        Clock $clock,
        Ready $ready,
    ): self|Resumable {
        $read = $this->read->intersect($ready->toRead());
        $write = $this->write->intersect($ready->toWrite());

        if (!$read->empty() || !$write->empty()) {
            return Resumable::of(new Ready($read, $write));
        }

        $timedout = $this
            ->timeout
            ->map(static fn($period) => $period->asElapsedPeriod())
            ->filter(
                fn($threshold) => $clock
                    ->now()
                    ->elapsedSince($this->at)
                    ->longerThan($threshold),
            )
            ->match(
                static fn() => true,
                static fn() => false,
            );

        if ($timedout) {
            return Resumable::of(new Ready(
                Sequence::of(),
                Sequence::of(),
            ));
        }

        return $this;
    }
}
