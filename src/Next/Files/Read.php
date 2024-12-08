<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Files;

use Innmind\IO\Next\Stream\Size;
use Innmind\Url\Path;
use Innmind\Validation\{
    Is,
    Of,
    Constraint,
    Failure,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    Validation,
    Predicate\Instance,
};

final class Read
{
    /**
     * @param \Closure(): (false|resource) $load
     */
    private function __construct(
        private \Closure $load,
        private ?Str\Encoding $encoding,
        private bool $autoClose,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Path $path): self
    {
        return new self(
            static fn() => \fopen(
                $path->toString(),
                'r',
            ),
            null,
            true,
        );
    }

    /**
     * @internal
     *
     * @param resource $resource
     */
    public static function temporary($resource): self
    {
        return new self(
            static function() use ($resource) {
                match (\fseek($resource, 0)) {
                    0 => null,
                    default => throw new \RuntimeException('Failed to read file'),
                };

                return $resource;
            },
            null,
            false,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(Str\Encoding $encoding): self
    {
        return new self(
            $this->load,
            $encoding,
            $this->autoClose,
        );
    }

    /**
     * This is only useful in case the code is called in an asynchronous context
     * as it allows the current code to inform the event loop we're doing IO.
     *
     * Otherwise this call is useless as files are always ready to be read.
     *
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        // todo
        return $this;
    }

    /**
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
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

        return $validate(($this->load)())
            ->maybe()
            ->keep(Instance::of(Size::class));
    }

    /**
     * @param int<1, max> $size
     *
     * @return Sequence<Str>
     */
    public function chunks(int $size): Sequence
    {
        $load = $this->load;
        $close = $this->close();

        $chunks = Sequence::lazy(static function($cleanup) use ($load, $close, $size) {
            $resource = $load();

            if (!\is_resource($resource)) {
                throw new \RuntimeException('Failed to load resource');
            }

            $cleanup(static fn() => $close($resource));

            do {
                $data = \stream_get_contents($resource, $size);

                if (\is_string($data)) {
                    yield Str::of($data);

                    continue;
                }

                if (\feof($resource)) {
                    // This case happen when reading an empty file or a file
                    // ending with an "end of line" character.
                    // We yield an empty chunk to allow to have at least one
                    // chunk expressed. This way a user may transform that empty
                    // chunk into something.
                    yield Str::of('');

                    break;
                }

                throw new \RuntimeException('Failed to read file');
            } while (!\feof($resource));

            $close($resource);
        });

        if ($this->encoding) {
            // Copy the value to avoid keeping a reference to $this
            $encoding = $this->encoding;
            $chunks = $chunks->map(
                static fn($chunk) => $chunk->toEncoding($encoding),
            );
        }

        return $chunks;
    }

    /**
     * @return Sequence<Str>
     */
    public function lines(): Sequence
    {
        $load = $this->load;
        $close = $this->close();

        $lines = Sequence::lazy(static function($cleanup) use ($load, $close) {
            $resource = $load();

            if (!\is_resource($resource)) {
                throw new \RuntimeException('Failed to load resource');
            }

            $cleanup(static fn() => $close($resource));

            do {
                $data = \fgets($resource);

                if (\is_string($data)) {
                    yield Str::of($data);

                    continue;
                }

                if (\feof($resource)) {
                    // This case happen when reading an empty file or a file
                    // ending with an "end of line" character.
                    // We yield an empty chunk to allow to have at least one
                    // chunk expressed. This way a user may transform that empty
                    // chunk into something.
                    yield Str::of('');

                    break;
                }

                throw new \RuntimeException('Failed to read file');
            } while (!\feof($resource));

            $close($resource);
        });

        if ($this->encoding) {
            // Copy the value to avoid keeping a reference to $this
            $encoding = $this->encoding;
            $lines = $lines->map(
                static fn($chunk) => $chunk->toEncoding($encoding),
            );
        }

        return $lines;
    }

    /**
     * @return callable(resource): void
     */
    private function close(): callable
    {
        return match ($this->autoClose) {
            true => static fn(mixed $resource) => /** @var resource $resource */ match (\fclose($resource)) {
                true => null,
                false => throw new \RuntimeException('Failed to close file'),
            },
            false => static fn() => null,
        };
    }
}
