<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Stream;
use Innmind\Url\Path;
use Innmind\Immutable\Maybe;

final class Files
{
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    /**
     * @return Maybe<Stream>
     */
    public function read(Path $path): Maybe
    {
        return $this->open($path->toString(), 'r');
    }

    /**
     * @return Maybe<Stream>
     */
    public function write(Path $path): Maybe
    {
        return $this->open($path->toString(), 'w');
    }

    /**
     * @return Maybe<Stream>
     */
    public function temporary(): Maybe
    {
        return $this->open('php://temp', 'r+');
    }

    /**
     * @param resource $resource
     */
    public function acquire($resource): Stream
    {
        return Stream::of($resource);
    }

    /**
     * @return Maybe<Stream>
     */
    private function open(string $path, string $mode): Maybe
    {
        $stream = \fopen($path, $mode);

        if ($stream === false) {
            /** @var Maybe<Stream> */
            return Maybe::nothing();
        }

        return Maybe::just(Stream::of($stream));
    }
}
