<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Watch;

use Innmind\IO\Internal\{
    Stream,
    Socket\Server,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 */
final class Ready
{
    /** @var Sequence<Stream|Server> */
    private Sequence $read;
    /** @var Sequence<Stream> */
    private Sequence $write;

    /**
     * @param Sequence<Stream|Server> $read
     * @param Sequence<Stream> $write
     */
    public function __construct(Sequence $read, Sequence $write)
    {
        $this->read = $read;
        $this->write = $write;
    }

    /**
     * @return Sequence<Stream|Server>
     */
    public function toRead(): Sequence
    {
        return $this->read;
    }

    /**
     * @return Sequence<Stream>
     */
    public function toWrite(): Sequence
    {
        return $this->write;
    }
}
