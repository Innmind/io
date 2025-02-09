<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class DataPartiallyWritten
{
    private string $message;
    private Str $data;
    private int $written;

    /**
     * @internal
     */
    public function __construct(Str $data, int $written)
    {
        $suggestion = '';

        if ($written > $data->length()) {
            $suggestion = ', it seems you are not using the correct string encoding';
        }

        $this->message = \sprintf(
            '%s out of %s written%s',
            $written,
            $data->length(),
            $suggestion,
        );
        $this->data = $data;
        $this->written = $written;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function data(): Str
    {
        return $this->data;
    }

    public function written(): int
    {
        return $this->written;
    }
}
