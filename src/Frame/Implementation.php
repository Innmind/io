<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Internal\Reader;
use Innmind\Immutable\Maybe;

/**
 * @internal
 * @template-covariant T
 */
interface Implementation
{
    /**
     * @return Maybe<T>
     */
    public function __invoke(Reader|Reader\Buffer $reader): Maybe;
}
