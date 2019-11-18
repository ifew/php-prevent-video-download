<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ob_start();
$crc = filter_var($_GET['crc']);
$file = $_SESSION['defaprotect' . $crc];
if ($headerurl = @get_headers($file, 1)['Location']) {
    if (!empty($headerurl)) {
        $file = $headerurl;
    }

}
$size   = filesize($file); // File size
$length = $size;           // Content length
$start  = 0;               // Start byte
$end    = $size - 1;       // End byte

header('Content-type: video/mp4');
header("Accept-Ranges: 0-$length");
if (isset($_SERVER['HTTP_RANGE'])) {
    if (isset($_SERVER['HTTP_RANGE'])) {
        $c_start = $start;
        $c_end   = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        if ($range == '-') {
            $c_start = $size - substr($range, 1);
        }else{
            $range  = explode('-', $range);
            $c_start = $range[0];
            $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
        }
        $c_end = ($c_end > $end) ? $end : $c_end;
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        $start  = $c_start;
        $end    = $c_end;
        $length = $end - $start + 1;
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: ".$length);

    $opts['http']['header'] = "Range: bytes=" . ($begin-$end)/$size . ",
    Content-Type: video/mp4,
    Accept-Ranges: bytes,
    Content-Disposition: inline;,
    Content-Transfer-Encoding: binary\n,".
    $header_http.",".
    "Connection: close";

    $opts['http']['method'] = "GET";
    $cong = stream_context_create($opts);
    ob_end_clean();
    
    readfile($file, false, $cong);
    
    exit();
}
