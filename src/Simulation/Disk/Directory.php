<?php
declare(strict_types = 1);

namespace Innmind\IO\Simulation\Disk;

use Innmind\Mutable\Map;
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Directory
{
    /**
     * @param Map<string, self|File> $files
     */
    private function __construct(
        private Map $files,
    ) {
    }

    public static function new(): self
    {
        return new self(Map::of());
    }

    /**
     * @return Attempt<self|File>
     */
    public function get(string $name): Attempt
    {
        return $this
            ->files
            ->get($name)
            ->attempt(static fn() => new \RuntimeException('No such file or directory'));
    }
}
