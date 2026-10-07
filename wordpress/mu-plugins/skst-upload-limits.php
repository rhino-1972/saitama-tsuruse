<?php
/**
 * SKST local upload limit adjustments.
 */

if (!defined('ABSPATH')) {
    exit;
}

const SKST_UPLOAD_LIMIT_BYTES = 64 * 1024 * 1024;
const SKST_UPLOAD_LIMIT_KB = SKST_UPLOAD_LIMIT_BYTES / 1024;

add_filter('upload_size_limit', 'skst_upload_size_limit', 20);
function skst_upload_size_limit($size) {
    return max((int) $size, SKST_UPLOAD_LIMIT_BYTES);
}

add_filter('pre_site_option_fileupload_maxk', 'skst_network_fileupload_maxk');
function skst_network_fileupload_maxk($value) {
    return SKST_UPLOAD_LIMIT_KB;
}

