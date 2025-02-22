<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Watch;

use Innmind\IO\Internal\{
    Stream\Implementation,
    Socket\Client,
    Socket\Server,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Ready
{
    /** @var Sequence<Implementation|Client|Server|Server\Connection> */
    private Sequence $read;
    /** @var Sequence<Implementation|Client|Server\Connection> */
    private Sequence $write;

    /**
     * @param Sequence<Implementation|Client|Server|Server\Connection> $read
     * @param Sequence<Implementation|Client|Server\Connection> $write
     */
    public function __construct(Sequence $read, Sequence $write)
    {
        $this->read = $read;
        $this->write = $write;
    }

    /**
     * @return Sequence<Implementation|Client|Server|Server\Connection>
     */
    public function toRead(): Sequence
    {
        return $this->read;
    }

    /**
     * @return Sequence<Implementation|Client|Server\Connection>
     */
    public function toWrite(): Sequence
    {
        return $this->write;
    }
}
