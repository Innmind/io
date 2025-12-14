<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\{
    Internal\Stream,
    Internal\Capabilities\Files\Implementation,
    Internal\Capabilities\Files\AmbientAuthority,
    Internal\Capabilities\Files\Simulation,
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
final class Files
{
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @internal
     */
    public static function fromAmbientAuthority(): self
    {
        return new self(AmbientAuthority::of());
    }

    /**
     * @internal
     */
    public static function simulation(self $files): self
    {
        return new self(Simulation::of($files->implementation));
    }

    /**
     * @return Attempt<Stream>
     */
    public function read(Path $path): Attempt
    {
        return $this->implementation->read($path);
    }

    /**
     * @return Attempt<Stream>
     */
    public function write(Path $path): Attempt
    {
        return $this->implementation->write($path);
    }

    /**
     * @return Attempt<Stream>
     */
    public function temporary(): Attempt
    {
        return $this->implementation->temporary();
    }

    /**
     * @return Maybe<mixed>
     */
    public function require(Path $path): Maybe
    {
        return $this->implementation->require($path);
    }

    /**
     * @return Sequence<Name>
     */
    public function list(Path $path): Sequence
    {
        return $this->implementation->list($path);
    }

    /**
     * @return Attempt<string>
     */
    public function mediaType(Path $path): Attempt
    {
        return $this->implementation->mediaType($path);
    }

    /**
     * @return Attempt<Kind>
     */
    public function kind(Path $path): Attempt
    {
        return $this->implementation->kind($path);
    }

    public function exists(Path $path): bool
    {
        return $this->implementation->exists($path);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function create(Path $path): Attempt
    {
        return $this->implementation->create($path);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(Path $path): Attempt
    {
        return $this->implementation->remove($path);
    }
}
