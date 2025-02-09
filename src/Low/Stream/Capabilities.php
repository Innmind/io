<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream;

interface Capabilities
{
    public function temporary(): Capabilities\Temporary;
    public function readable(): Capabilities\Readable;
    public function writable(): Capabilities\Writable;
    public function watch(): Capabilities\Watch;
}
