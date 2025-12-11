<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\{
    Internal\Stream,
    Exception\RuntimeException,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    Maybe,
};

/**
 * @internal
 */
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
     * @return Attempt<Stream>
     */
    public function read(Path $path): Attempt
    {
        return $this->open($path->toString(), 'r');
    }

    /**
     * @return Attempt<Stream>
     */
    public function write(Path $path): Attempt
    {
        return $this->open($path->toString(), 'w');
    }

    /**
     * @return Attempt<Stream>
     */
    public function temporary(): Attempt
    {
        return $this->open('php://temp', 'r+');
    }

    /**
     * @return Maybe<mixed>
     */
    public function require(Path $path): Maybe
    {
        $path = $path->toString();

        if (!\file_exists($path) || \is_dir($path)) {
            /** @var Maybe<mixed> */
            return Maybe::nothing();
        }

        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-suppress MixedArgument
         * @var Maybe<mixed>
         */
        return Maybe::just(require $path);
    }

    /**
     * @return Attempt<Stream>
     */
    private function open(string $path, string $mode): Attempt
    {
        $stream = \fopen($path, $mode);

        if ($stream === false) {
            /** @var Attempt<Stream> */
            return Attempt::error(new RuntimeException("Failed to open file '$path'"));
        }

        return Attempt::result(Stream::file($stream));
    }
}
