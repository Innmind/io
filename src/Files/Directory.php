<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\Internal\Capabilities;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Attempt,
    SideEffect,
};

final class Directory
{
    private function __construct(
        private Capabilities $capabilities,
        private Path $path,
    ) {
    }

    /**
     * @internal
     */
    #[\NoDiscard]
    public static function of(
        Capabilities $capabilities,
        Path $path,
    ): self {
        if (!$path->directory()) {
            $path = Path::of($path->toString().'/');
        }

        return new self($capabilities, $path);
    }

    /**
     * @return Sequence<Name>
     */
    #[\NoDiscard]
    public function list(): Sequence
    {
        return $this
            ->capabilities
            ->files()
            ->list($this->path);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function remove(): Attempt
    {
        return $this
            ->capabilities
            ->files()
            ->remove($this->path);
    }
}
