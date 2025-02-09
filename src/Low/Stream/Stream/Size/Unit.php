<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Stream\Size;

/**
 * @psalm-immutable
 */
enum Unit
{
    case bytes;
    case kilobytes;
    case megabytes;
    case gigabytes;
    case terabytes;
    case petabytes;

    /**
     * @psalm-pure
     */
    public static function for(int $size): self
    {
        if ($size < 1024) {
            return self::bytes;
        }

        if ($size < 1024 ** 2) {
            return self::kilobytes;
        }

        if ($size < 1024 ** 3) {
            return self::megabytes;
        }

        if ($size < 1024 ** 4) {
            return self::gigabytes;
        }

        if ($size < 1024 ** 5) {
            return self::terabytes;
        }

        return self::petabytes;
    }

    /**
     * @psalm-pure
     */
    public static function format(int $size): string
    {
        $unit = self::for($size);

        return match ($unit) {
            self::bytes => $size.'B',
            default => \sprintf(
                '%s%s',
                \round($size / $unit->lowerBound(), 3),
                $unit->unit(),
            ),
        };
    }

    public function times(int $value): int
    {
        return match ($this) {
            self::bytes => $value,
            default => $value * $this->lowerBound(),
        };
    }

    private function lowerBound(): int
    {
        return match ($this) {
            self::bytes => 0,
            self::kilobytes => 1024,
            self::megabytes => 1024 ** 2,
            self::gigabytes => 1024 ** 3,
            self::terabytes => 1024 ** 4,
            self::petabytes => 1024 ** 5,
        };
    }

    private function unit(): string
    {
        return match ($this) {
            self::bytes => 'B',
            self::kilobytes => 'KB',
            self::megabytes => 'MB',
            self::gigabytes => 'GB',
            self::terabytes => 'TB',
            self::petabytes => 'PB',
        };
    }
}
