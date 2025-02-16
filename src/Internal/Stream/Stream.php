<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\Internal\Stream\{
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    SideEffect,
};

interface Stream
{
    /**
     * @psalm-mutation-free
     *
     * @return resource stream
     */
    public function resource();

    /**
     * It returns a SideEffect instead of the stream on the right hand size
     * because you should no longer use the stream once it's closed
     *
     * @return Either<FailedToCloseStream, SideEffect>
     */
    public function close(): Either;

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool;
    public function position(): Position;

    /**
     * @return Either<PositionNotSeekable, self>
     */
    public function seek(Position $position, ?Mode $mode = null): Either;

    /**
     * @return Either<PositionNotSeekable, self>
     */
    public function rewind(): Either;

    /**
     * @psalm-mutation-free
     */
    public function end(): bool;

    /**
     * @psalm-mutation-free
     *
     * @return Maybe<Size>
     */
    public function size(): Maybe;
}
