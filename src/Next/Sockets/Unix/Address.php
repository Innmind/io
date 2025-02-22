<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Sockets\Unix;

use Innmind\Url\Path;

/**
 * @psalm-immutable
 */
final class Address
{
    private function __construct(private Path $path)
    {
    }

    /**
     * @psalm-pure
     */
    public static function of(Path $path): self
    {
        return new self($path);
    }

    public function toString(): string
    {
        /** @var array{dirname: string, filename: string} */
        $parts = \pathinfo($this->path->toString());

        return \sprintf(
            '%s/%s.sock',
            $parts['dirname'],
            $parts['filename'],
        );
    }
}
