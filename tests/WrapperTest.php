<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WrapperTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testTransformsVideoSource(): void
    {
        ob_start();
        include __DIR__ . '/../wrapper.php';
        echo '<video src="small.mp4"></video>';
        $output = ob_get_clean();

        $crc = substr(sha1('small.mp4'), -8, -1);
        $expected = '<video src="/video.php?crc=' . $crc . '"></video>';

        $this->assertSame($expected, $output);
        $this->assertSame('small.mp4', $_SESSION['defaprotect' . $crc]);
    }

    public function testIgnoresSafeTag(): void
    {
        ob_start();
        include __DIR__ . '/../wrapper.php';
        echo '<safe><video src="small.mp4"></video></safe>';
        $output = ob_get_clean();

        $this->assertSame('<safe><video src="small.mp4"></video></safe>', $output);
        $this->assertEmpty($_SESSION);
    }
}
