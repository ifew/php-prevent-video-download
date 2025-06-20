<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../video.php';
require __DIR__ . '/../vendor/autoload.php';

