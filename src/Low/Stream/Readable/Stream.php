<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Readable;

use Innmind\IO\Low\Stream\{
    Stream as StreamInterface,
    Stream\Stream as Base,
    Readable,
    Stream\Position,
    Stream\Position\Mode
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

    public function resource()
    {
        return $this->resource;
    }

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

    public function readLine(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<Str> */
            return Maybe::nothing();
        }

        $line = \fgets($this->resource);

        return Maybe::of(\is_string($line) ? Str::of($line) : null);
    }

    public function position(): Position
    {
        return $this->stream->position();
    }

    /** @psalm-suppress InvalidReturnType */
    public function seek(Position $position, ?Mode $mode = null): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->stream->seek($position, $mode)->map(fn() => $this);
    }

    /** @psalm-suppress InvalidReturnType */
    public function rewind(): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->stream->rewind()->map(fn() => $this);
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        return $this->stream->end();
    }

    /**
     * @psalm-mutation-free
     */
    public function size(): Maybe
    {
        return $this->stream->size();
    }

    public function close(): Either
    {
        return $this->stream->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        return $this->stream->closed();
    }

    public function toString(): Maybe
    {
        /** @var Maybe<Str> */
        $data = $this
            ->rewind()
            ->match(
                fn() => $this->read(),
                static fn() => Maybe::nothing(),
            );

        return $data->map(static fn($data) => $data->toString());
    }
}
