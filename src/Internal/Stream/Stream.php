<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\Internal\Stream\Stream\Size;
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

    /**
     * @return Either<PositionNotSeekable, SideEffect>
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
