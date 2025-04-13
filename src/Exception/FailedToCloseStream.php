<?php
declare(strict_types = 1);

namespace Innmind\IO\Exception;

final class FailedToCloseStream extends RuntimeException
{
    /**
     * @internal
     */
    public function __construct()
    {
        parent::__construct();
    }
}
