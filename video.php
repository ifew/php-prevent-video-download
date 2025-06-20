<?php

declare(strict_types=1);

if (!function_exists('defaprotect_serve_video')) {
    /**
     * Process a video request and return streaming information.
     *
     * @return array{headers: array<int, string>, file: string, context: resource|null}
     */
    function defaprotect_serve_video(array &$session, array $server, string $crc, callable $headerFn, callable $readfileFn): array
    {
        ob_start();

        $file = $session['defaprotect' . $crc] ?? '';

        $headers = @get_headers($file, true);
        if ($headers !== false && !empty($headers['Location'])) {
            $file = $headers['Location'];
        }

        $size   = filesize($file);
        $length = $size;
        $start  = 0;
        $end    = $size - 1;
        $fp     = @fopen($file, 'rb');

        $headersOut = [];
        $headerFn('Content-Type: video/mp4');
        $headersOut[] = 'Content-Type: video/mp4';
        $headerFn('Accept-Ranges: bytes');
        $headersOut[] = 'Accept-Ranges: bytes';

        if (isset($server['HTTP_RANGE'])) {
            $cStart = $start;
            $cEnd   = $end;

            [, $range] = explode('=', $server['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                $headerFn('HTTP/1.1 416 Requested Range Not Satisfiable');
                $headerFn("Content-Range: bytes $start-$end/$size");
                $headersOut[] = 'HTTP/1.1 416 Requested Range Not Satisfiable';
                $headersOut[] = "Content-Range: bytes $start-$end/$size";
                ob_end_clean();
                return ['headers' => $headersOut, 'file' => $file, 'context' => null];
            }

            if ($range === '-') {
                $cStart = $size - substr($range, 1);
            } else {
                $rangeParts = explode('-', $range);
                $cStart     = (int) $rangeParts[0];
                $cEnd       = isset($rangeParts[1]) && is_numeric($rangeParts[1])
                    ? (int) $rangeParts[1]
                    : $size;
            }

            $cEnd = ($cEnd > $end) ? $end : $cEnd;
            if ($cStart > $cEnd || $cStart > $size - 1 || $cEnd >= $size) {
                $headerFn('HTTP/1.1 416 Requested Range Not Satisfiable');
                $headerFn("Content-Range: bytes $start-$end/$size");
                $headersOut[] = 'HTTP/1.1 416 Requested Range Not Satisfiable';
                $headersOut[] = "Content-Range: bytes $start-$end/$size";
                ob_end_clean();
                return ['headers' => $headersOut, 'file' => $file, 'context' => null];
            }

            $start  = $cStart;
            $end    = $cEnd;
            $length = $end - $start + 1;
            if ($fp !== false) {
                fseek($fp, $start);
            }

            $headerFn('HTTP/1.1 206 Partial Content');
            $headersOut[] = 'HTTP/1.1 206 Partial Content';
        }

        $headerFn("Content-Range: bytes $start-$end/$size");
        $headersOut[] = "Content-Range: bytes $start-$end/$size";
        $headerFn("Content-Length: $length");
        $headersOut[] = "Content-Length: $length";

        $opts = [
            'http' => [
                'header' => sprintf(
                    "Range: bytes=%d-%d\r\nContent-Type: video/mp4\r\nAccept-Ranges: bytes\r\nContent-Disposition: inline\r\nContent-Transfer-Encoding: binary\r\nConnection: close",
                    $start,
                    $end
                ),
                'method' => 'GET',
            ],
        ];
        $context = stream_context_create($opts);

        ob_end_clean();

        $readfileFn($file, false, $context);

        return ['headers' => $headersOut, 'file' => $file, 'context' => $context];
    }
}

if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $crc = filter_input(INPUT_GET, 'crc', FILTER_SANITIZE_STRING) ?? '';
    defaprotect_serve_video($_SESSION, $_SERVER, $crc, 'header', 'readfile');
    exit;
}
