<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities\Files;

use Innmind\IO\{
    Simulation\Disk,
    Files\Kind,
    Files\Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    Maybe,
    Sequence,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Simulation implements Implementation
{
    private function __construct(
        private Implementation $files,
        private Disk $disk,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Implementation $files, Disk $disk): self
    {
        return new self($files, $disk);
    }

    #[\Override]
    public function read(Path $path): Attempt
    {
        return $this
            ->disk
            ->access($path)
            ->flatMap(static fn($file) => match (true) {
                $file instanceof Disk\File => $file->content()->stream(),
                default => Attempt::error(new \RuntimeException('No such file')),
            });
    }

    #[\Override]
    public function write(Path $path): Attempt
    {
        // simulated files streams must be readable and writable at the same time
        return $this->read($path);
    }

    #[\Override]
    public function temporary(): Attempt
    {
        return $this->files->temporary();
    }

    #[\Override]
    public function require(Path $path): Maybe
    {
        return $this
            ->disk
            ->access($path)
            ->maybe()
            ->keep(Instance::of(Disk\File::class))
            ->map(static fn($file) => $file->content()->read())
            ->map(static fn($file): mixed => eval($file));
    }

    #[\Override]
    public function list(Path $path): Sequence
    {
        return Sequence::lazy(function() use ($path) {
            // to make sure to have the current state of the filesystem
            yield $this
                ->disk
                ->list($path)
                ->snapshot()
                ->map(static fn($name, $file) => match (true) {
                    $file instanceof Disk\Directory => Name::of($name, Kind::directory),
                    $file instanceof Disk\File => Name::of($name, Kind::file),
                })
                ->values();
        })->flatMap(static fn($files) => $files);
    }

    #[\Override]
    public function mediaType(Path $path): Attempt
    {
        return Attempt::error(new \LogicException('Media types not supported in simulated environment'));
    }

    #[\Override]
    public function kind(Path $path): Attempt
    {
        return $this
            ->disk
            ->access($path)
            ->map(static fn($file) => match (true) {
                $file instanceof Disk\File => Kind::file,
                $file instanceof Disk\Directory => Kind::directory,
            });
    }

    #[\Override]
    public function exists(Path $path): bool
    {
        return $this->disk->exists($path);
    }

    #[\Override]
    public function create(Path $path): Attempt
    {
        // todo should the IO be injected at the creation of the disk ?
        return $this->disk->create($this->files, $path);
    }

    #[\Override]
    public function remove(Path $path): Attempt
    {
        return $this->disk->remove($path);
    }
}
