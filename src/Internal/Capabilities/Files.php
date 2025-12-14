<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\{
    Internal\Stream,
    Files\Name,
    Exception\RuntimeException,
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
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    /**
     * @return Attempt<Stream>
     */
    public function read(Path $path): Attempt
    {
        return $this->open($path->toString(), 'r');
    }

    /**
     * @return Attempt<Stream>
     */
    public function write(Path $path): Attempt
    {
        return $this->open($path->toString(), 'w');
    }

    /**
     * @return Attempt<Stream>
     */
    public function temporary(): Attempt
    {
        return $this->open('php://temp', 'r+');
    }

    /**
     * @return Maybe<mixed>
     */
    public function require(Path $path): Maybe
    {
        $path = $path->toString();

        if (!\file_exists($path) || \is_dir($path)) {
            /** @var Maybe<mixed> */
            return Maybe::nothing();
        }

        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-suppress MixedArgument
         * @var Maybe<mixed>
         */
        return Maybe::just(require $path);
    }

    /**
     * @return Sequence<Name>
     */
    public function list(Path $path): Sequence
    {
        return Sequence::lazy(static function() use ($path): \Generator {
            $files = new \FilesystemIterator($path->toString());

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isLink()) {
                    continue;
                }

                $name = $file->getBasename();

                if ($name === '') {
                    continue;
                }

                yield Name::of(
                    $name,
                    $file->isDir(),
                );
            }
        });
    }

    /**
     * @return Attempt<string>
     */
    public function mediaType(Path $path): Attempt
    {
        $mediaType = @\mime_content_type($path->toString());

        return match ($mediaType) {
            false => Attempt::error(new \RuntimeException('Failed to access media type')),
            default => Attempt::result($mediaType),
        };
    }

    public function exists(Path $path): bool
    {
        if (!\file_exists($path->toString())) {
            return false;
        }

        if (\is_link($path->toString())) {
            return false;
        }

        return match ($path->directory()) {
            false => true,
            true => \is_dir($path->toString()),
        };
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function create(Path $path): Attempt
    {
        return match ($path->directory()) {
            true => $this->createDirectory($path),
            false => $this->touch($path),
        };
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(Path $path): Attempt
    {
        if (!\file_exists($path->toString())) {
            return Attempt::result(SideEffect::identity);
        }

        if ($path->directory() && \is_dir($path->toString())) {
            return $this->rmdir($path->toString());
        }

        $path = $path->toString();

        if (\is_dir($path)) {
            return $this->rmdir($path.'/');
        }

        return $this->unlink($path);
    }

    /**
     * @return Attempt<Stream>
     */
    private function open(string $path, string $mode): Attempt
    {
        $stream = \fopen($path, $mode);

        if ($stream === false) {
            /** @var Attempt<Stream> */
            return Attempt::error(new RuntimeException("Failed to open file '$path'"));
        }

        return Attempt::result(Stream::file($stream));
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function createDirectory(Path $path): Attempt
    {
        $path = $path->toString();

        // We do not check the result of this function as it will return false
        // if the path already exist. This can lead to race conditions where
        // another process created the directory between the condition that
        // checked if it existed and the call to this method. The only important
        // part is to check wether the directory exists or not afterward.
        @\mkdir($path, recursive: true);

        if (!\is_dir($path)) {
            return Attempt::error(new \RuntimeException(\sprintf(
                "Failed to create directory '%s'",
                $path,
            )));
        }

        return Attempt::result(SideEffect::identity);
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function touch(Path $path): Attempt
    {
        $path = $path->toString();

        if (!@\touch($path)) {
            return Attempt::error(new \RuntimeException(\sprintf(
                "Failed to create file '%s'",
                $path,
            )));
        }

        if (!\file_exists($path)) {
            return Attempt::error(new \RuntimeException(\sprintf(
                "Failed to create file '%s'",
                $path,
            )));
        }

        return Attempt::result(SideEffect::identity);
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function rmdir(string $path): Attempt
    {
        return $this
            ->list(Path::of($path))
            ->map(static fn($name) => \sprintf(
                '%s%s%s',
                $path,
                $name->toString(),
                match ($name->directory()) {
                    true => '/',
                    false => '',
                },
            ))
            ->map(Path::of(...))
            ->sink(SideEffect::identity)
            ->attempt(fn($_, $file) => $this->remove($file))
            ->map(static fn() => @\rmdir($path))
            ->flatMap(static fn($removed) => match ($removed) {
                true => Attempt::result(SideEffect::identity),
                false => Attempt::error(new \RuntimeException(\sprintf(
                    "Failed to remove directory '%s'",
                    $path,
                ))),
            });
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function unlink(string $path): Attempt
    {
        return match (@\unlink($path)) {
            true => Attempt::result(SideEffect::identity),
            false => Attempt::error(new \RuntimeException(\sprintf(
                "Failed to remove file '%s'",
                $path,
            ))),
        };
    }
}
