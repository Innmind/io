<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Readable;

use Innmind\IO\Internal\Stream\{
    Stream as StreamInterface,
    Stream\Stream as Base,
    Readable,
    Stream\Position
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class Stream implements Readable
{
    /** @var resource */
    private $resource;
    private StreamInterface $stream;

    /**
     * @param resource $resource
     */
    private function __construct($resource)
    {
        $this->stream = Base::of($resource);
        $this->resource = $resource;
    }

    /**
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self($resource);
    }

    public static function open(Path $path): self
    {
        return new self(\fopen($path->toString(), 'r'));
    }

    public static function ofContent(string $content): self
    {
        $resource = \fopen('php://temp', 'r+');
        \fwrite($resource, $content);

        return new self($resource);
    }

    #[\Override]
    public function resource()
    {
        return $this->resource;
    }

    #[\Override]
    public function read(?int $length = null): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<Str> */
            return Maybe::nothing();
        }

        $data = \stream_get_contents(
            $this->resource,
            $length ?? -1,
        );

        return Maybe::of(\is_string($data) ? Str::of($data) : null);
    }

    #[\Override]
    public function readLine(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<Str> */
            return Maybe::nothing();
        }

        $line = \fgets($this->resource);

        return Maybe::of(\is_string($line) ? Str::of($line) : null);
    }

    #[\Override]
    public function position(): Position
    {
        return $this->stream->position();
    }

    #[\Override]
    public function rewind(): Either
    {
        return $this->stream->rewind();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function end(): bool
    {
        return $this->stream->end();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function size(): Maybe
    {
        return $this->stream->size();
    }

    #[\Override]
    public function close(): Either
    {
        return $this->stream->close();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function closed(): bool
    {
        return $this->stream->closed();
    }
}
