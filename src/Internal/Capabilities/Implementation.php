<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Watch;

/**
 * @internal
 */
interface Implementation
{
    public function files(): Files;
    public function streams(): Streams;
    public function sockets(): Sockets;
    public function watch(): Watch;
}
