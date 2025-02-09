<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Socket;

use Innmind\IO\Low\Socket\Server\Connection;
use Innmind\IO\Low\Stream\Readable;
use Innmind\Immutable\Maybe;

/**
 * It only implements Readable to be usable with Stream\Watch
 *
 * Read methods are not expected to be called
 */
interface Server extends Readable
{
    /**
     * @return Maybe<Connection>
     */
    public function accept(): Maybe;
}
