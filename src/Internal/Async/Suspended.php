<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Async;

use Innmind\IO\Internal\{
    Watch,
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
    Attempt,
};

/**
 * @internal
 */
final class Suspended
{
    /**
     * @psalm-mutation-free
     *
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
     * @psalm-pure
     *
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
     * @psalm-mutation-free
     */
    public function watch(): Watch
    {
        $watch = Watch::sync();
        $watch = $this->timeout->match(
            $watch->timeoutAfter(...),
            static fn() => $watch,
        );
        $watch = $this->read->reduce(
            $watch,
            static fn(Watch $watch, $stream) => $watch->forRead($stream),
        );
        $watch = $this->write->reduce(
            $watch,
            static fn(Watch $watch, $stream) => $watch->forWrite($stream),
        );

        return $watch;
    }

    /**
     * @param Attempt<Ready> $ready
     */
    public function next(
        Clock $clock,
        Attempt $ready,
    ): self|Resumable {
        $error = $ready->match(
            static fn() => true,
            static fn() => false,
        );

        if ($error) {
            // The drawback of resuming with the error is that an error occuring
            // due to another Fiber will affect all of them as for now there is
            // no way to distinguish due which Fiber the watch failed.
            // This will need real world experience to know if this approach is
            // ok or not.
            return Resumable::of($ready);
        }

        $ready = $ready->unwrap();

        $read = $this->read->intersect($ready->toRead());
        $write = $this->write->intersect($ready->toWrite());

        if (!$read->empty() || !$write->empty()) {
            return Resumable::of(Attempt::result(new Ready($read, $write)));
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
            return Resumable::of(Attempt::result(new Ready(
                Sequence::of(),
                Sequence::of(),
            )));
        }

        return $this;
    }
}
