<?php
/**
 * Plugin Name: SKST Local Native Gettext Compatibility
 * Description: Prevents macOS Local PHP-FPM crashes during native gettext locale discovery.
 */

if (!defined('ABSPATH')) {
    exit;
}

$skst_gettext_host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
$skst_gettext_local = $skst_gettext_host === 'localhost'
    || $skst_gettext_host === '127.0.0.1'
    || str_ends_with($skst_gettext_host, '.local');

if (PHP_OS_FAMILY === 'Darwin' && PHP_SAPI === 'fpm-fcgi' && $skst_gettext_local
    && (getenv('LC_ALL') === false || getenv('LC_ALL') === '')) {
    // Avoid libintl's macOS preference lookup inside a forked PHP-FPM worker.
    // WordPress uses its own translation locale; this only affects native gettext.
    putenv('LC_ALL=C');
}

unset($skst_gettext_host, $skst_gettext_local);
