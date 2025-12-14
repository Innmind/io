<?php
declare(strict_types = 1);

namespace Innmind\IO\Simulation\Disk;

use Innmind\Mutable\Map;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
final class Directory
{
    /**
     * @param Map<non-empty-string, self|File> $files
     */
    private function __construct(
        private Map $files,
    ) {
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self(Map::of());
    }

    /**
     * @param non-empty-string $name
     *
     * @return Attempt<self|File>
     */
    public function get(string $name): Attempt
    {
        return $this
            ->files
            ->get($name)
            ->attempt(static fn() => new \RuntimeException('No such file or directory'));
    }

    /**
     * @param non-empty-string $name
     *
     * @return Attempt<SideEffect>
     */
    public function add(string $name, self|File $child): Attempt
    {
        $this->files->put($name, $child);

        return Attempt::result(SideEffect::identity);
    }

    /**
     * @param non-empty-string $name
     *
     * @return Attempt<SideEffect>
     */
    public function remove(string $name): Attempt
    {
        $this->files->remove($name);

        return Attempt::result(SideEffect::identity);
    }

    /**
     * @return Map<non-empty-string, self|File>
     */
    public function list(): Map
    {
        return $this->files;
    }
}
