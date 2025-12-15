<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\Stream\Size;
use Innmind\Validation\Of;
use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
interface Implementation
{
    /**
     * @psalm-mutation-free
     */
    public function isFile(): bool;

    /**
     * @return Maybe<SideEffect>
     */
    public function nonBlocking(): Maybe;

    /**
     * @return Maybe<SideEffect>
     */
    public function blocking(): Maybe;

    /**
     * @psalm-mutation-free
     *
     * @return resource stream
     */
    public function resource();

    /**
     * @return Attempt<SideEffect>
     */
    public function rewind(): Attempt;

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

    /**
     * @return Attempt<SideEffect>
     */
    public function close(): Attempt;

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool;

    /**
     * @param int<1, max>|null $length When omitted will read the remaining of the stream
     *
     * @return Attempt<Str>
     */
    public function read(?int $length = null): Attempt;

    /**
     * @return Attempt<Str>
     */
    public function readLine(): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function write(Str $data): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function sync(): Attempt;
}
