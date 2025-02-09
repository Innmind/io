<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Stream;

use Innmind\IO\Low\Stream\{
    Stream as StreamInterface,
    Stream\Position\Mode,
    FailedToCloseStream,
    PositionNotSeekable,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    SideEffect,
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

    public function resource()
    {
        return $this->resource;
    }

    public function position(): Position
    {
        if ($this->closed()) {
            return new Position(0);
        }

        return new Position(\ftell($this->resource));
    }

    public function seek(Position $position, ?Mode $mode = null): Either
    {
        if (!$this->seekable) {
            /** @var Either<PositionNotSeekable, StreamInterface> */
            return Either::left(new PositionNotSeekable);
        }

        if ($this->closed()) {
            /** @var Either<PositionNotSeekable, StreamInterface> */
            return Either::right($this);
        }

        $previous = $this->position();
        $mode ??= Mode::fromStart;

        /** @var Either<PositionNotSeekable, StreamInterface> */
        return $this
            ->seekable($position, $mode)
            ->flatMap(fn($stream) => $this->doSeek(
                $stream,
                $position,
                $mode,
                $previous,
            ));
    }

    public function rewind(): Either
    {
        return $this->seek(new Position(0));
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
     */
    public function size(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<Size> */
            return Maybe::nothing();
        }

        /** @psalm-suppress InvalidArgument Psalm doesn't understand the filter */
        return Maybe::of(\fstat($this->resource))
            ->filter(static fn($stats) => \is_array($stats))
            ->flatMap(static fn(array $stats) => Maybe::of($stats['size'] ?? null))
            ->map(static fn($size) => (int) $size)
            ->map(static fn(int $size) => new Size($size));
    }

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
    public function closed(): bool
    {
        /** @psalm-suppress DocblockTypeContradiction */
        return $this->closed || !\is_resource($this->resource);
    }

    /**
     * @return Either<PositionNotSeekable, self>
     */
    private function seekable(Position $position, Mode $mode): Either
    {
        $targetPosition = match ($mode) {
            Mode::fromStart => $position->toInt(),
            Mode::fromCurrentPosition => $this->position()->toInt() + $position->toInt(),
        };

        return $this
            ->size()
            ->filter(static fn($size) => $targetPosition <= $size->toInt())
            ->match(
                fn() => Either::right($this),
                static fn() => Either::left(new PositionNotSeekable),
            );
    }

    /**
     * @return Either<PositionNotSeekable, self>
     */
    private function doSeek(
        self $stream,
        Position $position,
        Mode $mode,
        Position $previous,
    ): Either {
        $status = \fseek(
            $stream->resource,
            $position->toInt(),
            $mode->toInt(),
        );

        if ($status === -1) {
            /** @psalm-suppress ImpureMethodCall */
            \fseek(
                $stream->resource,
                $previous->toInt(),
                Mode::fromStart->toInt(),
            );

            /** @var Either<PositionNotSeekable, self> */
            return Either::left(new PositionNotSeekable);
        }

        /** @var Either<PositionNotSeekable, self> */
        return Either::right($stream);
    }
}
