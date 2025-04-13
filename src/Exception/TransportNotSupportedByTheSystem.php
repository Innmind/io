<?php
declare(strict_types = 1);

namespace Innmind\IO\Exception;

final class TransportNotSupportedByTheSystem extends RuntimeException
{
    public function __construct(string $transport, string ...$allowed)
    {
        parent::__construct(\sprintf(
            '%s not supported (allowed: %s)',
            $transport,
            \implode(', ', $allowed),
        ));
    }
}
