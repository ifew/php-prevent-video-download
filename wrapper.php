<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start(
    static function (string $output): string {
        if (
            (strpos($output, '<video') > -1 ||
                strpos($output, '<audio') > -1 ||
                strpos($output, '<source') > -1) &&
            strpos($output, '<safe') === false
        ) {
            $getUrl = static function (array $matches): string {
                $crc = substr(sha1($matches[2]), -8, -1);
                $_SESSION['defaprotect' . $crc] = $matches[2];

                return $matches[1] . '/video.php?crc=' . $crc;
            };

            $output = preg_replace_callback(
                '/(<video[^>]*src *= *[\"\']?)([^\"\']*)/i',
                $getUrl,
                $output
            );
            $output = preg_replace_callback(
                '/(<source[^>]*src *= *[\"\']?)([^\"\']*)/i',
                $getUrl,
                $output
            );
            $output = preg_replace_callback(
                '/(<audio[^>]*src *= *[\"\']?)([^\"\']*)/i',
                $getUrl,
                $output
            );
        }

        return $output;
    }
);

