<?php
// Router script for PHP built-in server — handles WordPress pretty permalinks
$root  = __DIR__;
$path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file  = $root . $path;

// Serve static files directly
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}
// Otherwise hand to WordPress index.php
require_once $root . '/index.php';
