<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Socket\Internet;

use Innmind\IO\Low\Socket\Exception\TransportNotSupportedByTheSystem;
use Innmind\Immutable\Map;

final class Transport
{
    private string $transport;
    /** @var Map<string, int|bool|float|string|array> */
    private Map $options;

    private function __construct(string $transport)
    {
        $allowed = \stream_get_transports();

        if (!\in_array($transport, $allowed, true)) {
            throw new TransportNotSupportedByTheSystem($transport, ...$allowed);
        }

        $this->transport = $transport;
        /** @var Map<string, int|bool|float|string|array> */
        $this->options = Map::of();
    }

    public static function tcp(): self
    {
        return new self('tcp');
    }

    public static function ssl(): self
    {
        return new self('ssl');
    }

    public static function sslv3(): self
    {
        return new self('sslv3');
    }

    public static function sslv2(): self
    {
        return new self('sslv2');
    }

    public static function tls(): self
    {
        return new self('tls');
    }

    public static function tlsv10(): self
    {
        return new self('tlsv1.0');
    }

    public static function tlsv11(): self
    {
        return new self('tlsv1.1');
    }

    public static function tlsv12(): self
    {
        return new self('tlsv1.2');
    }

    /**
     * @psalm-mutation-free
     */
    public function withOption(string $key, int|bool|float|string|array $value): self
    {
        $self = clone $this;
        $self->options = ($this->options)($key, $value);

        return $self;
    }

    /**
     * @return Map<string, int|bool|float|string|array>
     */
    public function options(): Map
    {
        return $this->options;
    }

    public function toString(): string
    {
        return $this->transport;
    }
}
