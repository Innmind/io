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
use Innmind\Validation\Is;
use Innmind\Mutable\Map;
use Innmind\Immutable\{
    Attempt,
    Sequence,
    SideEffect,
};

final class Disk
{
    private function __construct(
        private Directory $root,
    ) {
    }

    public static function new(): self
    {
        return new self(Directory::new());
    }

    /**
     * @internal
     *
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

        /** @var Directory|File */
        $parent = $this->root;

        return self::parts($path)
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
        return $this
            ->parent($path)
            ->flatMap(
                static fn($parent) => self::parts($path)
                    ->last()
                    ->attempt(static fn() => new \LogicException('Empty path'))
                    ->flatMap(static fn($name) => match ($path->directory()) {
                        true => $parent->add($name, Directory::new()),
                        false => File::new($files)->flatMap(
                            static fn($file) => $parent->add($name, $file),
                        ),
                    }),
            );
    }

    /**
     * @internal
     *
     * @return Attempt<SideEffect>
     */
    public function remove(Path $path): Attempt
    {
        if ($path->equals(Path::of('/'))) {
            return Attempt::error(new \RuntimeException('Root directory cannot be removed'));
        }

        return $this
            ->parent($path)
            ->flatMap(
                static fn($parent) => self::parts($path)
                    ->last()
                    ->attempt(static fn() => new \LogicException('Empty path'))
                    ->flatMap($parent->remove(...)),
            );
    }

    /**
     * @internal
     */
    public function exists(Path $path): bool
    {
        return $this->access($path)->match(
            static fn() => true,
            static fn() => false,
        );
    }

    /**
     * @return Map<non-empty-string, File|Directory>
     */
    public function list(Path $path): Map
    {
        return $this
            ->access($path)
            ->flatMap(static fn($file) => match (true) {
                $file instanceof Directory => Attempt::result($file),
                default => Attempt::error(new \Exception),
            })
            ->match(
                static fn($directory) => $directory->list(),
                static fn() => Map::of(),
            );
    }

    /**
     * @return Attempt<Directory>
     */
    private function parent(Path $path): Attempt
    {
        if ($path instanceof RelativePath) {
            return Attempt::error(new \LogicException(\sprintf(
                'Path "%s" must absolute',
                $path->toString(),
            )));
        }

        /** @var Directory|File */
        $parent = $this->root;

        return self::parts($path)
            ->dropEnd(1)
            ->sink($parent)
            ->attempt(static fn($parent, $part) => match (true) {
                $parent instanceof File => Attempt::error(new \RuntimeException('No such file or directory')),
                $parent instanceof Directory => $parent->get($part),
            })
            ->flatMap(static fn($file) => match (true) {
                $file instanceof File => Attempt::error(new \RuntimeException('No such file or directory')),
                $file instanceof Directory => Attempt::result($file),
            });
    }

    /**
     * @return Sequence<non-empty-string>
     */
    private static function parts(Path $path): Sequence
    {
        return Sequence::of(...\explode('/', $path->toString()))
            ->keep(Is::string()->nonEmpty()->asPredicate());
    }
}
