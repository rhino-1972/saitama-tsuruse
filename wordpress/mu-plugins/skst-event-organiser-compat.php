<?php
/**
 * Plugin Name: SKST Event Organiser Compatibility
 * Description: Preload translations for Event Organiser's early capability labels.
 */

if (!defined('ABSPATH')) {
    exit;
}

// EO 3.12.10 translates capability labels while its plugin file is loading.
add_action('muplugins_loaded', function () {
    $plugin = 'event-organiser/event-organiser.php';
    if (!in_array($plugin, (array) get_option('active_plugins'), true)) {
        return;
    }
    $locale = determine_locale();
    $files = array(
        WP_LANG_DIR . '/plugins/eventorganiser-' . $locale . '.mo',
        WP_LANG_DIR . '/event-organiser/eventorganiser-' . $locale . '.mo',
        WP_LANG_DIR . '/eventorganiser-' . $locale . '.mo',
        WP_PLUGIN_DIR . '/event-organiser/languages/eventorganiser-' . $locale . '.mo',
    );
    foreach ($files as $file) {
        if (is_readable($file) && load_textdomain('eventorganiser', $file, $locale)) {
            break;
        }
    }
});
