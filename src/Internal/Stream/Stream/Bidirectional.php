<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Stream;

use Innmind\IO\Internal\Stream\{
    Readable,
    Writable,
    Bidirectional as BidirectionalInterface,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class Bidirectional implements BidirectionalInterface
{
    private Readable $read;
    private Writable $write;
    /** @var resource */
    private $resource;

    /**
     * @param resource $resource
     */
    private function __construct($resource)
    {
        $this->read = Readable\NonBlocking::of(
            Readable\Stream::of($resource),
        );
        $this->write = Writable\Stream::of($resource);
        $this->resource = $resource;
    }

    /**
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self($resource);
    }

    #[\Override]
    public function resource()
    {
        return $this->resource;
    }

    #[\Override]
    public function close(): Either
    {
        return $this->write->close();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function closed(): bool
    {
        return $this->write->closed();
    }

    #[\Override]
    public function rewind(): Either
    {
        return $this->read->rewind();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function end(): bool
    {
        return $this->read->end();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function size(): Maybe
    {
        return $this->read->size();
    }

    #[\Override]
    public function read(?int $length = null): Maybe
    {
        return $this->read->read($length);
    }

    #[\Override]
    public function readLine(): Maybe
    {
        return $this->read->readLine();
    }

    #[\Override]
    public function write(Str $data): Either
    {
        return $this->write->write($data);
    }
}
