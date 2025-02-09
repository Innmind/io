<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Watch;

use Innmind\IO\Low\Stream\{
    Watch,
    Readable,
    Writable,
    Stream,
};
use Innmind\Immutable\Maybe;
use Psr\Log\LoggerInterface;

final class Logger implements Watch
{
    private Watch $watch;
    private LoggerInterface $logger;

    private function __construct(Watch $watch, LoggerInterface $logger)
    {
        $this->watch = $watch;
        $this->logger = $logger;
    }

    public function __invoke(): Maybe
    {
        return ($this->watch)()->map(fn($ready) => $this->log($ready));
    }

    public static function psr(Watch $watch, LoggerInterface $logger): self
    {
        return new self($watch, $logger);
    }

    /**
     * @psalm-mutation-free
     */
    public function forRead(Readable $read, Readable ...$reads): Watch
    {
        /** @psalm-suppress ImpureMethodCall */
        $this->logger->debug(
            'Adding {count} streams to watch for read',
            ['count' => \count($reads) + 1],
        );

        return new self(
            $this->watch->forRead($read, ...$reads),
            $this->logger,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forWrite(Writable $write, Writable ...$writes): Watch
    {
        /** @psalm-suppress ImpureMethodCall */
        $this->logger->debug(
            'Adding {count} streams to watch for write',
            ['count' => \count($writes) + 1],
        );

        return new self(
            $this->watch->forWrite($write, ...$writes),
            $this->logger,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream $stream): Watch
    {
        /** @psalm-suppress ImpureMethodCall */
        $this->logger->debug('Removing a stream from watch list');

        return new self(
            $this->watch->unwatch($stream),
            $this->logger,
        );
    }

    private function log(Ready $ready): Ready
    {
        $this->logger->debug(
            'Streams ready: {read} for read, {write} for write',
            [
                'read' => $ready->toRead()->size(),
                'write' => $ready->toWrite()->size(),
            ],
        );

        return $ready;
    }
}
