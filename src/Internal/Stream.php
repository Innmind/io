<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal;

use Innmind\IO\{
    Stream\Size,
    Exception\InvalidArgumentException,
    Exception\DataPartiallyWritten,
    Exception\FailedToCloseStream,
    Exception\FailedToWriteToStream,
    Exception\PositionNotSeekable,
    Exception\RuntimeException,
};
use Innmind\Validation\{
    Is,
    Of,
    Constraint,
    Failure,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
    SideEffect,
    Validation,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Stream
{
    /** @var resource */
    private $resource;
    private bool $closed = false;
    private bool $seekable = false;
    private bool $syncable = false;

    /**
     * @param resource $resource
     */
    private function __construct($resource)
    {
        /**
         * @psalm-suppress DocblockTypeContradiction
         * @psalm-suppress RedundantConditionGivenDocblockType
         */
        if (!\is_resource($resource) || \get_resource_type($resource) !== 'stream') {
            throw new InvalidArgumentException;
        }

        $this->resource = $resource;
        $meta = \stream_get_meta_data($resource);

        if ($meta['seekable'] && \substr($meta['uri'], 0, 9) !== 'php://std') {
            //stdin, stdout and stderr are not seekable
            $this->seekable = true;
            $this->rewind();
        }

        if ($this->seekable && \substr($meta['uri'] ?? '', 0, 10) !== 'php://temp') {
            $this->syncable = true;
        }
    }

    /**
     * @internal
     *
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self($resource);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function nonBlocking(): Maybe
    {
        $return = \stream_set_blocking($this->resource, false);

        if ($return === false) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        $_ = \stream_set_write_buffer($this->resource, 0);
        $_ = \stream_set_read_buffer($this->resource, 0);

        return Maybe::just(new SideEffect);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function blocking(): Maybe
    {
        $return = \stream_set_blocking($this->resource, false);

        if ($return === false) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        return Maybe::just(new SideEffect);
    }

    /**
     * @psalm-mutation-free
     *
     * @return resource stream
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function rewind(): Attempt
    {
        if (!$this->seekable) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(new PositionNotSeekable);
        }

        if ($this->closed()) {
            /** @var Attempt<SideEffect> */
            return Attempt::result(new SideEffect);
        }

        $status = \fseek($this->resource, 0);

        /** @var Attempt<SideEffect> */
        return match ($status) {
            -1 => Attempt::error(new PositionNotSeekable),
            default => Attempt::result(new SideEffect),
        };
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        if ($this->closed()) {
            return true;
        }

        return \feof($this->resource);
    }

    /**
     * @psalm-mutation-free
     *
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<Size> */
            return Maybe::nothing();
        }

        /** @var Constraint<int, int<0, max>> */
        $positive = Of::callable(static fn(int $size) => match (true) {
            $size >= 0 => Validation::success($size),
            default => Validation::fail(Failure::of('size must be positive')),
        });
        /** @var Constraint<mixed, resource> */
        $resource = Of::callable(static fn(mixed $resource) => match (\is_resource($resource)) {
            true => Validation::success($resource),
            false => Validation::fail(Failure::of('not a resource')),
        });
        $validate = $resource
            ->map(\fstat(...))
            ->and(Is::shape(
                'size',
                Is::string()
                    ->or(Is::int())
                    ->map(static fn($size) => (int) $size)
                    ->and($positive)
                    ->map(Size::of(...)),
            ))
            ->map(static fn(array $stat): mixed => $stat['size']);

        return $validate($this->resource)
            ->maybe()
            ->keep(Instance::of(Size::class));
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function close(): Attempt
    {
        if ($this->closed()) {
            return Attempt::result(new SideEffect);
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $return = \fclose($this->resource);

        if ($return === false) {
            return Attempt::error(new FailedToCloseStream);
        }

        $this->closed = true;

        return Attempt::result(new SideEffect);
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        /** @psalm-suppress DocblockTypeContradiction */
        return $this->closed || !\is_resource($this->resource);
    }

    /**
     * @param int<1, max>|null $length When omitted will read the remaining of the stream
     *
     * @return Attempt<Str>
     */
    public function read(?int $length = null): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<Str> */
            return Attempt::error(new RuntimeException('Stream closed'));
        }

        $data = \stream_get_contents(
            $this->resource,
            $length ?? -1,
        );

        return match ($data) {
            false => Attempt::error(new RuntimeException('Failed to read the stream')),
            default => Attempt::result(Str::of($data)),
        };
    }

    /**
     * @return Attempt<Str>
     */
    public function readLine(): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<Str> */
            return Attempt::error(new RuntimeException('Stream closed'));
        }

        $line = \fgets($this->resource);

        return match ($line) {
            false => Attempt::error(new RuntimeException('Failed to read the stream')),
            default => Attempt::result(Str::of($line)),
        };
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function write(Str $data): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(new FailedToWriteToStream);
        }

        $written = @\fwrite($this->resource, $data->toString());

        if ($written === false) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(new FailedToWriteToStream);
        }

        if ($written !== $data->length()) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(DataPartiallyWritten::of($data, $written));
        }

        /** @var Attempt<SideEffect> */
        return Attempt::result(new SideEffect);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function sync(): Attempt
    {
        if ($this->closed()) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(new FailedToWriteToStream);
        }

        if (!$this->syncable) {
            return Attempt::result(new SideEffect);
        }

        $written = @\fsync($this->resource);

        if ($written === false) {
            /** @var Attempt<SideEffect> */
            return Attempt::error(new FailedToWriteToStream);
        }

        /** @var Attempt<SideEffect> */
        return Attempt::result(new SideEffect);
    }
}
