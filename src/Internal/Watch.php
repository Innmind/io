<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\IO\Internal\{
    Watch\Ready,
    Socket\Server,
};
use Innmind\Immutable\Maybe;

interface Watch
{
    /**
     * @return Maybe<Ready> Returns nothing when it fails to lookup the streams
     */
    public function __invoke(): Maybe;

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
}
