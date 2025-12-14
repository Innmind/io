<?php
declare(strict_types = 1);

namespace Innmind\IO\Simulation;

use Innmind\IO\{
    Simulation\Disk\Directory,
    Simulation\Disk\File,
    Internal\Capabilities\Files\Implementation as Files,
};
use Innmind\Url\{
    Path,
    RelativePath,
};
use Innmind\Immutable\{
    Attempt,
    Sequence,
    Map,
    SideEffect,
};

/**
 * @internal
 */
final class Disk
{
    private function __construct(
        private Directory $root,
    ) {
    }

    /**
     * @return Attempt<File|Directory>
     */
    public function access(Path $path): Attempt
    {
        if ($path instanceof RelativePath) {
            return Attempt::error(new \LogicException(\sprintf(
                'Path "%s" must absolute',
                $path->toString(),
            )));
        }

        $parts = \explode('/', $path->toString());
        /** @var Directory|File */
        $parent = $this->root;

        return Sequence::of(...$parts)
            ->exclude(static fn($part) => $part === '')
            ->sink($parent)
            ->attempt(static fn($parent, $part) => match (true) {
                $parent instanceof File => Attempt::error(new \RuntimeException('No such file or directory')),
                $parent instanceof Directory => $parent->get($part),
            });
    }

    /**
     * @internal
     *
     * @return Attempt<SideEffect>
     */
    public function create(Files $files, Path $path): Attempt
    {
        return Attempt::result(SideEffect::identity);
    }

    /**
     * @internal
     *
     * @return Attempt<SideEffect>
     */
    public function remove(Path $path): Attempt
    {
        return Attempt::result(SideEffect::identity);
    }

    /**
     * @internal
     */
    public function exists(Path $path): bool
    {
        return false;
    }

    /**
     * @return Map<non-empty-string, File|Directory>
     */
    public function list(Path $path): Map
    {
        return Map::of();
    }
}
