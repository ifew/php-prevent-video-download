<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class VideoTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $this->file = __DIR__ . '/../small.mp4';
    }

    public function testServeVideoWithoutRange(): void
    {
        $crc = substr(sha1('small.mp4'), -8, -1);
        $_SESSION['defaprotect' . $crc] = $this->file;

        $headers = [];
        $calledFile = null;
        $result = defaprotect_serve_video(
            $_SESSION,
            [],
            $crc,
            function (string $h) use (&$headers) { $headers[] = $h; },
            function (string $file) use (&$calledFile) { $calledFile = $file; }
        );

        $this->assertSame($this->file, $result['file']);
        $this->assertSame($this->file, $calledFile);
        $this->assertContains('Content-Type: video/mp4', $headers);
        $this->assertContains('Accept-Ranges: bytes', $headers);
        $this->assertContains('Content-Length: ' . filesize($this->file), $headers);
    }

    public function testServeVideoWithRange(): void
    {
        $crc = substr(sha1('small.mp4'), -8, -1);
        $_SESSION['defaprotect' . $crc] = $this->file;

        $headers = [];
        $result = defaprotect_serve_video(
            $_SESSION,
            ['HTTP_RANGE' => 'bytes=0-1'],
            $crc,
            function (string $h) use (&$headers) { $headers[] = $h; },
            function () { /* ignore */ }
        );

        $this->assertContains('HTTP/1.1 206 Partial Content', $headers);
        $this->assertContains('Content-Range: bytes 0-1/' . filesize($this->file), $headers);
        $this->assertContains('Content-Length: 2', $headers);
    }

    public function testServeVideoInvalidRange(): void
    {
        $crc = substr(sha1('small.mp4'), -8, -1);
        $_SESSION['defaprotect' . $crc] = $this->file;

        $headers = [];
        $result = defaprotect_serve_video(
            $_SESSION,
            ['HTTP_RANGE' => 'bytes=1000000-1000001'],
            $crc,
            function (string $h) use (&$headers) { $headers[] = $h; },
            function () { /* ignore */ }
        );

        $this->assertContains('HTTP/1.1 416 Requested Range Not Satisfiable', $headers);
    }
}
