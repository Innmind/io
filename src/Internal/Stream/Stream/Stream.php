<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Stream;

use Innmind\IO\Internal\Stream\{
    Stream as StreamInterface,
    FailedToCloseStream,
    PositionNotSeekable,
    Exception\InvalidArgumentException,
};
use Innmind\Validation\{
    Is,
    Of,
    Constraint,
    Failure,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    SideEffect,
    Validation,
    Predicate\Instance,
};

final class Stream implements StreamInterface
{
    /** @var resource */
    private $resource;
    private bool $closed = false;
    private bool $seekable = false;

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
    public function rewind(): Either
    {
        if (!$this->seekable) {
            /** @var Either<PositionNotSeekable, SideEffect> */
            return Either::left(new PositionNotSeekable);
        }

        if ($this->closed()) {
            /** @var Either<PositionNotSeekable, SideEffect> */
            return Either::right(new SideEffect);
        }

        $status = \fseek($this->resource, 0);

        /** @var Either<PositionNotSeekable, SideEffect> */
        return match ($status) {
            -1 => Either::left(new PositionNotSeekable),
            default => Either::right(new SideEffect),
        };
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

        return \feof($this->resource);
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

    #[\Override]
    public function close(): Either
    {
        if ($this->closed()) {
            return Either::right(new SideEffect);
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $return = \fclose($this->resource);

        if ($return === false) {
            return Either::left(new FailedToCloseStream);
        }

        $this->closed = true;

        return Either::right(new SideEffect);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function closed(): bool
    {
        /** @psalm-suppress DocblockTypeContradiction */
        return $this->closed || !\is_resource($this->resource);
    }
}
