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
        private Kind $kind,
    ) {
    }

    /**
     * @internal
     *
     * @param non-empty-string $name
     */
    public static function of(string $name, Kind $kind): self
    {
        return new self($name, $kind);
    }

    public function kind(): Kind
    {
        return $this->kind;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->name;
    }
}
