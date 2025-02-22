<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\Internal\{
    Stream\Watch\Ready,
    Socket\Client,
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
        Implementation|Client|Server|Server\Connection $read,
        Implementation|Client|Server|Server\Connection ...$reads,
    ): self;

    /**
     * @psalm-mutation-free
     */
    public function forWrite(
        Implementation|Client|Server\Connection $write,
        Implementation|Client|Server\Connection ...$writes,
    ): self;

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Implementation|Client|Server|Server\Connection $stream): self;
}
