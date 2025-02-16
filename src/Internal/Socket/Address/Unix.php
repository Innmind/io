<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Address;

use Innmind\Url\Path;

/**
 * @psalm-immutable
 */
final class Unix
{
    private string $path;

    public function __construct(Path $path)
    {
        /** @var array{dirname: string, filename: string} */
        $parts = \pathinfo($path->toString());

        $this->path = \sprintf(
            '%s/%s.sock',
            $parts['dirname'],
            $parts['filename'],
        );
    }

    public static function of(string $path): self
    {
        return new self(Path::of($path));
    }

    public function toString(): string
    {
        return $this->path;
    }
}
