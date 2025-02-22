<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\IO\Internal\{
    Watch\Ready,
    Socket\Server,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\Maybe;

/**
 * By default a Watch must wait forever for changes
 */
interface Watch
{
    /**
     * @return Maybe<Ready> Returns nothing when it fails to lookup the streams
     */
    public function __invoke(): Maybe;

    /**
     * @psalm-mutation-free
     */
    public function timeoutAfter(Period $timeout): self;

    /**
     * @psalm-mutation-free
     */
    public function waitForever(): self;

    /**
     * @psalm-mutation-free
     */
    public function poll(): self;

    /**
     * @psalm-mutation-free
     */
    public function forRead(
        Stream|Server $read,
        Stream|Server ...$reads,
    ): self;

    /**
     * @psalm-mutation-free
     */
    public function forWrite(
        Stream $write,
        Stream ...$writes,
    ): self;

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream|Server $stream): self;

    /**
     * @psalm-mutation-free
     */
    public function clear(): self;
}
