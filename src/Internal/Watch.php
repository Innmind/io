<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\IO\{
    Internal\Watch\Sync,
    Internal\Watch\Async,
    Internal\Watch\Ready,
    Internal\Socket\Server,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 * By default it waits forever for changes
 */
final class Watch
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private Sync|Async $implementation,
    ) {
    }

    /**
     * @return Attempt<Ready>
     */
    public function __invoke(): Attempt
    {
        return ($this->implementation)();
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function sync(): self
    {
        return new self(Sync::new());
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function async(Clock $clock): self
    {
        return new self(Async::new($clock));
    }

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->implementation->timeoutAfter($timeout),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function waitForever(): self
    {
        return new self(
            $this->implementation->waitForever(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function poll(): self
    {
        return $this->timeoutAfter(Period::second(0));
    }

    /**
     * @psalm-mutation-free
     */
    public function forRead(
        Stream|Server $read,
        Stream|Server ...$reads,
    ): self {
        return new self(
            $this->implementation->forRead($read, ...$reads),
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
            $this->implementation->forWrite($write, ...$writes),
        );
    }

    /**
     * The new Watch uses the shortest timeout of the both.
     *
     * @psalm-mutation-free
     */
    public function merge(self $other): self
    {
        $type = \get_class($other->implementation);

        if (!($this->implementation instanceof $type)) {
            throw new \LogicException('Sync and async IO cannot be done at the same time');
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return new self(
            $this->implementation->merge($other->implementation),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream|Server $stream): self
    {
        return new self(
            $this->implementation->unwatch($stream),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function clear(): self
    {
        return new self(
            $this->implementation->clear(),
        );
    }
}
