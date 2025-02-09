<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Watch;

use Innmind\IO\Low\Stream\{
    Readable,
    Writable,
};
use Innmind\Immutable\Set;

/**
 * @psalm-immutable
 */
final class Ready
{
    /** @var Set<Readable> */
    private Set $read;
    /** @var Set<Writable> */
    private Set $write;

    /**
     * @param Set<Readable> $read
     * @param Set<Writable> $write
     */
    public function __construct(Set $read, Set $write)
    {
        $this->read = $read;
        $this->write = $write;
    }

    /**
     * @return Set<Readable>
     */
    public function toRead(): Set
    {
        return $this->read;
    }

    /**
     * @return Set<Writable>
     */
    public function toWrite(): Set
    {
        return $this->write;
    }
}
