<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Internal\Reader;
use Innmind\Immutable\Attempt;

/**
 * @internal
 * @template-covariant T
 */
interface Implementation
{
    /**
     * @return Attempt<T>
     */
    public function __invoke(Reader|Reader\Buffer $reader): Attempt;
}
