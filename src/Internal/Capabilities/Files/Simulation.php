<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities\Files;

use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    Maybe,
    Sequence,
};

/**
 * @internal
 */
final class Simulation implements Implementation
{
    private function __construct(
        private Implementation $files,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Implementation $files): self
    {
        return new self($files);
    }

    #[\Override]
    public function read(Path $path): Attempt
    {
        return $this->files->read($path);
    }

    #[\Override]
    public function write(Path $path): Attempt
    {
        return $this->files->write($path);
    }

    #[\Override]
    public function temporary(): Attempt
    {
        return $this->files->temporary();
    }

    #[\Override]
    public function require(Path $path): Maybe
    {
        return $this->files->require($path);
    }

    #[\Override]
    public function list(Path $path): Sequence
    {
        return $this->files->list($path);
    }

    #[\Override]
    public function mediaType(Path $path): Attempt
    {
        return Attempt::error(new \LogicException('Media types not supported in simulated environment'));
    }

    #[\Override]
    public function kind(Path $path): Attempt
    {
        return $this->files->kind($path);
    }

    #[\Override]
    public function exists(Path $path): bool
    {
        return $this->files->exists($path);
    }

    #[\Override]
    public function create(Path $path): Attempt
    {
        return $this->files->create($path);
    }

    #[\Override]
    public function remove(Path $path): Attempt
    {
        return $this->files->remove($path);
    }
}
