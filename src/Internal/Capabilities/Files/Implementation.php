<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities\Files;

use Innmind\IO\{
    Internal\Stream,
    Files\Name,
    Files\Kind,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    Maybe,
    Sequence,
    SideEffect,
};

/**
 * @internal
 */
interface Implementation
{
    /**
     * @return Attempt<Stream>
     */
    public function read(Path $path): Attempt;

    /**
     * @return Attempt<Stream>
     */
    public function write(Path $path): Attempt;

    /**
     * @return Attempt<Stream>
     */
    public function temporary(): Attempt;

    /**
     * @return Maybe<mixed>
     */
    public function require(Path $path): Maybe;

    /**
     * @return Sequence<Name>
     */
    public function list(Path $path): Sequence;

    /**
     * @return Attempt<string>
     */
    public function mediaType(Path $path): Attempt;

    /**
     * @return Attempt<Kind>
     */
    public function kind(Path $path): Attempt;

    public function exists(Path $path): bool;

    /**
     * @return Attempt<SideEffect>
     */
    public function create(Path $path): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(Path $path): Attempt;
}
