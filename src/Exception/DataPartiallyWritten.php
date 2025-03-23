<?php
declare(strict_types = 1);

namespace Innmind\IO\Exception;

use Innmind\Immutable\Str;

final class DataPartiallyWritten extends RuntimeException
{
    private function __construct(Str $data, int $written)
    {
        parent::__construct(\sprintf(
            '%s out of %s written',
            $written,
            $data->length(),
        ));
    }

    /**
     * @internal
     */
    public static function of(Str $data, int $written): self
    {
        return new self($data, $written);
    }
}
