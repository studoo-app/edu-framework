<?php

namespace Core\Formatter;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Formatter\BufferToServer;

class BufferToServerTest extends TestCase
{
    private const SERVER_LINE = '[Mon May  5 08:02:06 2025] 127.0.0.1:65229 [200]: GET /';

    public function testGetFormatBufferParsesAValidServerLine(): void
    {
        $result = (new BufferToServer(self::SERVER_LINE))->getFormatBuffer();

        $this->assertSame(self::SERVER_LINE, $result['raw']);
        $this->assertSame('Mon May  5 08:02:06 2025', $result['timestamp']);
        $this->assertSame('127.0.0.1:65229', $result['ip_port']);
        $this->assertSame('200', $result['status_code']);
        $this->assertSame('GET', $result['method']);
        $this->assertSame('/', $result['path']);
    }

    public function testGetFormatBufferParsesLineWithTrailingNewLine(): void
    {
        $line = "[Sun Oct  4 12:30:47 2026] 127.0.0.1:59303 [200]: GET /\n";
        $result = (new BufferToServer($line))->getFormatBuffer();

        $this->assertSame('Sun Oct  4 12:30:47 2026', $result['timestamp']);
        $this->assertSame('127.0.0.1:59303', $result['ip_port']);
        $this->assertSame('200', $result['status_code']);
        $this->assertSame('GET', $result['method']);
        $this->assertSame('/', $result['path']);
    }

    public function testGetFormatBufferParsesPostRequestWithStatusCodeAndLongPath(): void
    {
        $line = '[Sun Oct  4 12:30:47 2026] 127.0.0.1:59307 [404]: POST /api/users/42';
        $result = (new BufferToServer($line))->getFormatBuffer();

        $this->assertSame('404', $result['status_code']);
        $this->assertSame('POST', $result['method']);
        $this->assertSame('/api/users/42', $result['path']);
    }

    public function testGetFormatBufferReturnsRawBufferWhenLineDoesNotMatch(): void
    {
        $buffer = "PHP 8.5.11 Development Server (http://localhost:8123) started\n";
        $result = (new BufferToServer($buffer))->getFormatBuffer();

        $this->assertSame($buffer, $result['raw']);
        $this->assertNull($result['timestamp']);
        $this->assertNull($result['ip_port']);
        $this->assertNull($result['status_code']);
        $this->assertNull($result['method']);
        $this->assertNull($result['path']);
    }

    public function testGetBufferReturnsTheOriginalBuffer(): void
    {
        $this->assertSame(self::SERVER_LINE, (new BufferToServer(self::SERVER_LINE))->getBuffer());
    }
}
