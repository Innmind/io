<?php
declare(strict_types = 1);

namespace Tests\Innmind\IO;

use Innmind\IO\{
    IO,
    Readable\Frame,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Socket\{
    Server,
    Client,
    Address,
};
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\{
    Sequence,
    Fold,
    Str,
    Monoid\Concat,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class FunctionalTest extends TestCase
{
    use BlackBox;

    public function testReadChunks()
    {
        $this
            ->forAll(Set\Elements::of(
                [1, 'z', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z']],
                [2, 'z', ['fo', 'ob', 'ar', 'ba', 'z']],
                [3, 'baz', ['foo', 'bar', 'baz']],
            ))
            ->then(function($in) {
                [$size, $quit, $expected] = $in;

                $stream = Stream::ofContent('foobarbaz');
                $chunks = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->watch()
                    ->chunks($size)
                    ->fold(
                        Fold::with([]),
                        static fn($chunks, $chunk) => match ($chunk->toString()) {
                            $quit => Fold::result(\array_merge($chunks, [$chunk->toString()])),
                            default => Fold::with(\array_merge($chunks, [$chunk->toString()])),
                        },
                    )
                    ->flatMap(static fn($result) => $result->maybe())
                    ->match(
                        static fn($chunks) => $chunks,
                        static fn() => null,
                    );

                $this->assertSame($expected, $chunks);
            });
    }

    public function testReadChunksEncoding()
    {
        $stream = Stream::ofContent('foob');
        $chunks = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->chunks(1)
            ->fold(
                Fold::with([]),
                static fn($chunks, $chunk) => match ($chunk->toString()) {
                    'b' => Fold::result(\array_merge($chunks, [$chunk->encoding()->toString()])),
                    default => Fold::with(\array_merge($chunks, [$chunk->encoding()->toString()])),
                },
            )
            ->flatMap(static fn($result) => $result->maybe())
            ->match(
                static fn($chunks) => $chunks,
                static fn() => null,
            );

        $this->assertSame(
            ['ASCII', 'ASCII', 'ASCII', 'ASCII'],
            $chunks,
        );
    }

    public function testReadChunksWithALazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    [1, 'foobarbaz', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z', '']],
                    [2, 'foobarbaz', ['fo', 'ob', 'ar', 'ba', 'z']],
                    [3, 'foobarbaz', ['foo', 'bar', 'baz', '']],
                    [1, '', ['']],
                    [1, "\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$size, $content, $expected] = $in;

                $stream = Stream::ofContent($content);
                $chunks = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->toEncoding($encoding)
                    ->watch()
                    ->chunks($size)
                    ->lazy()
                    ->rewindable()
                    ->sequence();
                $values = $chunks
                    ->map(static fn($chunk) => $chunk->toString())
                    ->toList();
                $encodings = $chunks
                    ->map(static fn($chunk) => $chunk->encoding()->toString())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding->toString()], $encodings);
                $this->assertSame(0, $stream->position()->toInt());
                $this->assertFalse($stream->end());
            });
    }

    public function testReadChunksWithANonRewindableLazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    [1, 'foobarbaz', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z', '']],
                    [2, 'foobarbaz', ['fo', 'ob', 'ar', 'ba', 'z']],
                    [3, 'foobarbaz', ['foo', 'bar', 'baz', '']],
                    [1, '', ['']],
                    [1, "\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$size, $content, $expected] = $in;

                $stream = Stream::ofContent($content);
                $chunks = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->toEncoding($encoding)
                    ->watch()
                    ->chunks($size)
                    ->lazy()
                    ->sequence();
                $values = $chunks
                    ->map(static fn($chunk) => $chunk->toString())
                    ->toList();
                $encodings = $chunks
                    ->map(static fn($chunk) => $chunk->encoding()->toString())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding->toString()], $encodings);
                $this->assertTrue($stream->end());
            });
    }

    public function testReadLinesWithALazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    ['foobarbaz', ['foobarbaz']],
                    ["fo\nob\nar\nba\nz", ["fo\n", "ob\n", "ar\n", "ba\n", 'z']],
                    ["foo\nbar\nbaz\n", ["foo\n", "bar\n", "baz\n"]],
                    ['', ['']],
                    ["\n", ["\n"]],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$content, $expected] = $in;

                $stream = Stream::ofContent($content);
                $lines = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->toEncoding($encoding)
                    ->watch()
                    ->lines()
                    ->lazy()
                    ->rewindable()
                    ->sequence();
                $values = $lines
                    ->map(static fn($line) => $line->toString())
                    ->toList();
                $encodings = $lines
                    ->map(static fn($line) => $line->encoding()->toString())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding->toString()], $encodings);
                $this->assertSame(0, $stream->position()->toInt());
                $this->assertFalse($stream->end());
            });
    }

    public function testReadLinesWithANonRewindableLazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    ['foobarbaz', ['foobarbaz']],
                    ["fo\nob\nar\nba\nz", ["fo\n", "ob\n", "ar\n", "ba\n", 'z']],
                    ["foo\nbar\nbaz\n", ["foo\n", "bar\n", "baz\n"]],
                    ['', ['']],
                    ["\n", ["\n"]],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$content, $expected] = $in;

                $stream = Stream::ofContent($content);
                $lines = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->toEncoding($encoding)
                    ->watch()
                    ->lines()
                    ->lazy()
                    ->sequence();
                $values = $lines
                    ->map(static fn($line) => $line->toString())
                    ->toList();
                $encodings = $lines
                    ->map(static fn($line) => $line->encoding()->toString())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding->toString()], $encodings);
                $this->assertTrue($stream->end());
            });
    }

    public function testReadRealFileByLines()
    {
        $stream = Stream::open(Path::of(\dirname(__DIR__).'/LICENSE'));
        $lines = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->lines()
            ->lazy()
            ->sequence()
            ->toList();

        $this->assertCount(22, $lines);
        $this->assertSame("MIT License\n", $lines[0]->toString());
        $this->assertSame("SOFTWARE.\n", $lines[20]->toString());
        $this->assertSame('', $lines[21]->toString());
    }

    public function testSize()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($content) {
                $stream = Stream::ofContent($content);
                $size = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->size()
                    ->match(
                        static fn($size) => $size->toInt(),
                        static fn() => null,
                    );

                $this->assertNotNull($size);
                $this->assertSame($size, $stream->size()->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                ));
            });
    }

    public function testReadFrame()
    {
        $http = <<<RAW
        POST /some-form HTTP/1.1\r
        User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)\r
        Host: innmind.com\r
        Content-Type: application/x-www-form-urlencoded\r
        Content-Length: 23\r
        Accept-Language: fr-fr\r
        Accept-Encoding: gzip, deflate\r
        Connection: Keep-Alive\r
        \r
        some[key]=value&foo=bar\r
        \r

        RAW;

        $stream = Stream::ofContent($http);
        $request = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(Frame\Composite::of(
                static fn($firstLine, $headersAndBody) => [$firstLine, ...$headersAndBody],
                Frame\Line::new()->map(static fn($line) => $line->trim()->toString()),
                Frame\Sequence::of(
                    Frame\Line::new()->map(static fn($line) => $line->trim()),
                )
                    ->until(static fn($line) => $line->empty())
                    ->flatMap(
                        static fn($headers) => Frame\Chunk::of(
                            (int) $headers
                                ->toList()[3]
                                ->takeEnd(2)
                                ->toString(),
                        )
                            ->map(static fn($body) => [
                                $headers
                                    ->filter(static fn($header) => !$header->empty())
                                    ->map(static fn($header) => $header->toString())
                                    ->toList(),
                                $body->toString(),
                            ]),
                    ),
            ))
            ->one()
            ->match(
                static fn($request) => $request,
                static fn() => null,
            );

        $this->assertNotNull($request);
        $this->assertSame('POST /some-form HTTP/1.1', $request[0]);
        $this->assertSame(
            [
                'User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)',
                'Host: innmind.com',
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: 23',
                'Accept-Language: fr-fr',
                'Accept-Encoding: gzip, deflate',
                'Connection: Keep-Alive',
            ],
            $request[1],
        );
        $this->assertSame('some[key]=value&foo=bar', $request[2]);
    }

    public function testReadFrames()
    {
        $someStream = <<<RAW
        --PAYLOAD START--
        some payload A
        --PAYLOAD END--
        --PAYLOAD START--
        some payload B
        --PAYLOAD END--
        --PAYLOAD START--
        some payload C
        --PAYLOAD END--
        RAW;

        $stream = Stream::ofContent($someStream);
        $payloads = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(Frame\Composite::of(
                static fn($start, $payload, $end) => $payload->toString(),
                Frame\Line::new(),
                Frame\Line::new()->map(static fn($line) => $line->trim()),
                Frame\Line::new(),
            ))
            ->sequence()
            ->toList();

        $this->assertSame(
            [
                'some payload A',
                'some payload B',
                'some payload C',
            ],
            $payloads,
        );
    }

    public function testFilterOutFrame()
    {
        $someStream = <<<RAW
        --PAYLOAD START--
        some payload A
        --PAYLOAD END--
        --PAYLOAD START--
        some payload B
        --PAYLOAD END--
        --PAYLOAD START--
        some payload C
        --PAYLOAD END--
        RAW;

        $stream = Stream::ofContent($someStream);
        $payload = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(Frame\Composite::of(
                static fn($start, $payload, $end) => $payload->toString(),
                Frame\Line::new(),
                Frame\Line::new()
                    ->map(static fn($line) => $line->trim())
                    ->filter(static fn($line) => $line->endsWith('B')),
                Frame\Line::new(),
            ))
            ->one()
            ->match(
                static fn($payload) => $payload,
                static fn() => null,
            );

        $this->assertNull($payload);
    }

    public function testReadingSequenceOfLineFrames()
    {
        $someStream = <<<RAW
        --PAYLOAD START--
        some payload A
        --PAYLOAD END--

        RAW;

        $stream = Stream::ofContent($someStream);
        $payload = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(Frame\Sequence::of(
                Frame\Line::new(),
            )->until(static fn($line) => $line->empty()))
            ->one()
            ->match(
                static fn($payload) => $payload,
                static fn() => null,
            );

        $this->assertNotNull($payload);
        $this->assertSame(
            [
                "--PAYLOAD START--\n",
                "some payload A\n",
                "--PAYLOAD END--\n",
                '',
            ],
            $payload
                ->map(static fn($line) => $line->toString())
                ->toList(),
        );
    }

    public function testSocketClientSend()
    {
        @\unlink('/tmp/foo.sock');
        $address = Address\Unix::of('/tmp/foo');
        $server = Server\Unix::recoverable($address)->match(
            static fn($server) => $server,
            static fn() => null,
        );

        $this->assertNotNull($server);

        $client = Client\Unix::of($address)->match(
            static fn($socket) => $socket,
            static fn() => null,
        );

        $this->assertNotNull($client);

        $sent = IO::of(Select::waitForever(...))
            ->sockets()
            ->clients()
            ->wrap($client)
            ->watch()
            ->toEncoding(Str\Encoding::ascii)
            ->send(Sequence::of(
                Str::of("GET / HTTP/1.1\n"),
                Str::of("Host: example.com\n"),
                Str::of("\n"),
            ))
            ->match(
                static fn() => true,
                static fn() => false,
            );

        $this->assertTrue($sent);

        $read = $server
            ->accept()
            ->flatMap(static fn($client) => $client->read())
            ->match(
                static fn($data) => $data->toString(),
                static fn() => null,
            );

        $this->assertSame(
            <<<HTTP
            GET / HTTP/1.1
            Host: example.com


            HTTP,
            $read,
        );
        $client->close();
        $server->close();
    }

    public function testSocketClientHeartbeatWithSocketClosing()
    {
        @\unlink('/tmp/foo.sock');
        $address = Address\Unix::of('/tmp/foo');
        $server = Server\Unix::recoverable($address)->match(
            static fn($server) => $server,
            static fn() => null,
        );

        $this->assertNotNull($server);

        $client = Client\Unix::of($address)->match(
            static fn($socket) => $socket,
            static fn() => null,
        );

        $this->assertNotNull($client);

        $heartbeats = 0;
        $result = IO::of(Select::timeoutAfter(...))
            ->sockets()
            ->clients()
            ->wrap($client)
            ->timeoutAfter(ElapsedPeriod::of(500))
            ->toEncoding(Str\Encoding::ascii)
            ->heartbeatWith(function() use (&$heartbeats, $server, $client) {
                if ($heartbeats === 0) {
                    ++$heartbeats;

                    return Sequence::of(Str::of('foo'));
                }

                if ($heartbeats === 1) {
                    ++$heartbeats;
                    $this->assertSame(
                        'foo',
                        $server
                            ->accept()
                            ->flatMap(static fn($client) => $client->read())
                            ->match(
                                static fn($data) => $data->toString(),
                                static fn() => null,
                            ),
                    );
                    $client->close();
                }

                return Sequence::of();
            })
            ->frames(Frame\Chunk::of(1))
            ->one()
            ->match(
                static fn() => true,
                static fn() => false,
            );

        $this->assertSame(2, $heartbeats);
        $this->assertTrue($client->closed());
        $this->assertFalse($result, 'It should fail due to the closing of the socket');
        $server->close();
    }

    public function testSocketClientHeartbeat_()
    {
        @\unlink('/tmp/foo.sock');
        $address = Address\Unix::of('/tmp/foo');
        $server = Server\Unix::recoverable($address)->match(
            static fn($server) => $server,
            static fn() => null,
        );

        $this->assertNotNull($server);

        $client = Client\Unix::of($address)->match(
            static fn($socket) => $socket,
            static fn() => null,
        );

        $this->assertNotNull($client);

        $heartbeats = 0;
        $result = IO::of(Select::timeoutAfter(...))
            ->sockets()
            ->clients()
            ->wrap($client)
            ->timeoutAfter(ElapsedPeriod::of(500))
            ->toEncoding(Str\Encoding::ascii)
            ->heartbeatWith(function() use (&$heartbeats, $server, $client) {
                if ($heartbeats === 0) {
                    ++$heartbeats;

                    return Sequence::of(Str::of('foo'));
                }

                if ($heartbeats === 1) {
                    ++$heartbeats;
                    $this->assertSame(
                        'foo',
                        $server
                            ->accept()
                            ->flatMap(static fn($client) => $client->write(Str::of('bar'))->maybe())
                            ->flatMap(static fn($client) => $client->read())
                            ->match(
                                static fn($data) => $data->toString(),
                                static fn() => null,
                            ),
                    );
                }

                return Sequence::of();
            })
            ->frames(Frame\Chunk::of(3))
            ->one()
            ->match(
                static fn($response) => $response->toString(),
                static fn() => null,
            );

        $this->assertSame(2, $heartbeats);
        $this->assertSame('bar', $result);
        $client->close();
        $server->close();
    }
}
