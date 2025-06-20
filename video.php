<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

$crc  = filter_input(INPUT_GET, 'crc', FILTER_SANITIZE_STRING);
$file = $_SESSION['defaprotect' . $crc] ?? '';

$headers = @get_headers($file, true);
if ($headers !== false && !empty($headers['Location'])) {
    $file = $headers['Location'];
}

$size   = filesize($file);
$length = $size;
$start  = 0;
$end    = $size - 1;
$fp     = @fopen($file, 'rb');

header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');

if (isset($_SERVER['HTTP_RANGE'])) {
    $cStart = $start;
    $cEnd   = $end;

    [, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
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
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }

    $start  = $cStart;
    $end    = $cEnd;
    $length = $end - $start + 1;
    fseek($fp, $start);

    header('HTTP/1.1 206 Partial Content');
}

header("Content-Range: bytes $start-$end/$size");
header("Content-Length: $length");

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
$cong = stream_context_create($opts);

ob_end_clean();

readfile($file, false, $cong);

exit;
