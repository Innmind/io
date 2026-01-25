<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

/**
 * @psalm-immutable
 */
enum Kind
{
    case file;
    case directory;
    case link;
}
