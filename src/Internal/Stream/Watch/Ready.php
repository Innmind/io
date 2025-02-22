<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Watch;

use Innmind\IO\Internal\{
    Stream\Implementation,
    Socket\Server,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Ready
{
    /** @var Sequence<Implementation|Server> */
    private Sequence $read;
    /** @var Sequence<Implementation> */
    private Sequence $write;

    /**
     * @param Sequence<Implementation|Server> $read
     * @param Sequence<Implementation> $write
     */
    public function __construct(Sequence $read, Sequence $write)
    {
        $this->read = $read;
        $this->write = $write;
    }

    /**
     * @return Sequence<Implementation|Server>
     */
    public function toRead(): Sequence
    {
        return $this->read;
    }

    /**
     * @return Sequence<Implementation>
     */
    public function toWrite(): Sequence
    {
        return $this->write;
    }
}
