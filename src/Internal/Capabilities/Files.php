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
}
