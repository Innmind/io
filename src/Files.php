<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\{
    Files\Read,
    Files\Temporary,
    Files\Write,
    Files\Name,
    Internal\Capabilities,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Attempt,
    Maybe,
    Sequence,
    SideEffect,
};

final class Files
{
    private function __construct(
        private Capabilities $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Capabilities $capabilities): self
    {
        return new self($capabilities);
    }

    public function read(Path $path): Read
    {
        return Read::of($this->capabilities, $path);
    }

    public function write(Path $path): Write
    {
        return Write::of($this->capabilities, $path);
    }

    /**
     * @return Maybe<mixed>
     */
    public function require(Path $path): Maybe
    {
        return $this->capabilities->files()->require($path);
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Attempt<Temporary>
     */
    public function temporary(Sequence $chunks): Attempt
    {
        $capabilities = $this->capabilities;

        return $this
            ->capabilities
            ->files()
            ->temporary()
            ->flatMap(
                static fn($tmp) => Write::temporary($capabilities, $tmp)
                    ->sink($chunks)
                    ->map(static fn() => Temporary::of($capabilities, $tmp)),
            );
    }

    /**
     * @experimental
     *
     * @return Sequence<Name>
     */
    public function list(Path $path): Sequence
    {
        return $this->capabilities->files()->list($path);
    }

    /**
     * @experimental
     *
     * @return Attempt<string>
     */
    public function mediaType(Path $path): Attempt
    {
        return $this->capabilities->files()->mediaType($path);
    }

    /**
     * @experimental
     */
    public function exists(Path $path): bool
    {
        return $this->capabilities->files()->exists($path);
    }

    /**
     * @experimental
     *
     * @return Attempt<SideEffect>
     */
    public function create(Path $path): Attempt
    {
        return $this->capabilities->files()->create($path);
    }
}
