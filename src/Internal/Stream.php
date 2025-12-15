<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\IO\{
    Internal\Stream\Implementation,
    Internal\Stream\AmbientAuthority,
    Internal\Stream\Simulated,
    Stream\Size,
};
use Innmind\Validation\Of;
use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
final class Stream
{
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @internal
     *
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self(AmbientAuthority::of($resource));
    }

    /**
     * @internal
     *
     * @param resource $resource
     */
    public static function file($resource): self
    {
        return new self(AmbientAuthority::file($resource));
    }

    /**
     * @internal
     */
    public static function simulated(self $stream): self
    {
        return new self(Simulated::of($stream->implementation));
    }

    public function isFile(): bool
    {
        return $this->implementation->isFile();
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function nonBlocking(): Maybe
    {
        return $this->implementation->nonBlocking();
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function blocking(): Maybe
    {
        return $this->implementation->blocking();
    }

    /**
     * @psalm-mutation-free
     *
     * @return resource stream
     */
    public function resource()
    {
        return $this->implementation->resource();
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function rewind(): Attempt
    {
        return $this->implementation->rewind();
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        return $this->implementation->end();
    }

    /**
     * @psalm-mutation-free
     *
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        return $this->implementation->size();
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function close(): Attempt
    {
        return $this->implementation->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        return $this->implementation->closed();
    }

    /**
     * @param int<1, max>|null $length When omitted will read the remaining of the stream
     *
     * @return Attempt<Str>
     */
    public function read(?int $length = null): Attempt
    {
        return $this->implementation->read($length);
    }

    /**
     * @return Attempt<Str>
     */
    public function readLine(): Attempt
    {
        return $this->implementation->readLine();
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function write(Str $data): Attempt
    {
        return $this->implementation->write($data);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function sync(): Attempt
    {
        return $this->implementation->sync();
    }
}
