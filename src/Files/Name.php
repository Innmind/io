<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

/**
 * @psalm-immutable
 */
final class Name
{
    /**
     * @param non-empty-string $name
     */
    private function __construct(
        private string $name,
        private bool $directory,
    ) {
    }

    /**
     * @internal
     *
     * @param non-empty-string $name
     */
    public static function of(string $name, bool $directory): self
    {
        return new self($name, $directory);
    }

    public function directory(): bool
    {
        return $this->directory;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->name;
    }
}
