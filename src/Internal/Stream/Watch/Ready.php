<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Watch;

use Innmind\IO\Internal\Stream\{
    Readable,
    Writable,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Ready
{
    /** @var Sequence<Readable> */
    private Sequence $read;
    /** @var Sequence<Writable> */
    private Sequence $write;

    /**
     * @param Sequence<Readable> $read
     * @param Sequence<Writable> $write
     */
    public function __construct(Sequence $read, Sequence $write)
    {
        $this->read = $read;
        $this->write = $write;
    }

    /**
     * @return Sequence<Readable>
     */
    public function toRead(): Sequence
    {
        return $this->read;
    }

    /**
     * @return Sequence<Writable>
     */
    public function toWrite(): Sequence
    {
        return $this->write;
    }
}
