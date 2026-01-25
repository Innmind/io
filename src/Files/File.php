<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\Internal\Capabilities;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class File
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
        return new self($capabilities, $path);
    }

    #[\NoDiscard]
    public function read(): Read
    {
        return Read::of($this->capabilities, $this->path);
    }

    #[\NoDiscard]
    public function write(): Write
    {
        return Write::of($this->capabilities, $this->path);
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

    /**
     * @return Attempt<string>
     */
    #[\NoDiscard]
    public function mediaType(): Attempt
    {
        return $this
            ->capabilities
            ->files()
            ->mediaType($this->path);
    }
}
