<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\Internal\Capabilities;
use Innmind\Url\Path;
use Innmind\Immutable\Attempt;

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
    public static function of(
        Capabilities $capabilities,
        Path $path,
    ): self {
        return new self($capabilities, $path);
    }

    public function read(): Read
    {
        return Read::of($this->capabilities, $this->path);
    }

    public function write(): Write
    {
        return Write::of($this->capabilities, $this->path);
    }

    /**
     * @return Attempt<string>
     */
    public function mediaType(): Attempt
    {
        return $this
            ->capabilities
            ->files()
            ->mediaType($this->path);
    }
}
