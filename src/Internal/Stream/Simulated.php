<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\{
    Stream\Size,
    Exception\FailedToWriteToStream,
    Exception\RuntimeException,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
    SideEffect,
};

/**
 * @internal
 *
 * This wrapper allows to not really close the underlying stream as it's a
 * temporary stream that needs to stay open otherwise we lose the data stored.
 */
final class Simulated implements Implementation
{
    private function __construct(
        private Implementation $implementation,
        private bool $closed,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Implementation $implementation): self
    {
        return new self($implementation, false);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function isFile(): bool
    {
        // We know it's a temporary stream, thus not a pointer to a real file.
        return false;
    }

    #[\Override]
    public function nonBlocking(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        return $this->implementation->nonBlocking();
    }

    #[\Override]
    public function blocking(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        return $this->implementation->blocking();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function resource()
    {
        return $this->implementation->resource();
    }

    #[\Override]
    public function rewind(): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<SideEffect> */
            return Attempt::result(SideEffect::identity);
        }

        return $this->implementation->rewind();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function end(): bool
    {
        if ($this->closed()) {
            return true;
        }

        return $this->implementation->end();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function size(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<Size> */
            return Maybe::nothing();
        }

        return $this->implementation->size();
    }

    #[\Override]
    public function close(): Attempt
    {
        if ($this->closed()) {
            return Attempt::result(SideEffect::identity);
        }

        $this->closed = true;

        return Attempt::result(SideEffect::identity);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function closed(): bool
    {
        return $this->closed || $this->implementation->closed();
    }

    #[\Override]
    public function read(?int $length = null): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<Str> */
            return Attempt::error(new RuntimeException('Stream closed'));
        }

        return $this->implementation->read($length);
    }

    #[\Override]
    public function readLine(): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<Str> */
            return Attempt::error(new RuntimeException('Stream closed'));
        }

        return $this->implementation->readLine();
    }

    #[\Override]
    public function write(Str $data): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(new FailedToWriteToStream);
        }

        return $this->implementation->write($data);
    }

    #[\Override]
    public function sync(): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(new FailedToWriteToStream);
        }

        return $this->implementation->sync();
    }
}
