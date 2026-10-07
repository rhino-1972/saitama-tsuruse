<?php
/** Isolated status and installer-link tests; no live plugins are changed. */
define('ABSPATH', '/unused/');
$active_plugins = array();
$capabilities = array();
$multisite = false;
function add_action($hook, $callback) {}
function is_plugin_active($file) { return in_array($file, $GLOBALS['active_plugins'], true); }
function current_user_can($capability, ...$args) { return !empty($GLOBALS['capabilities'][$capability]); }
function is_multisite() { return $GLOBALS['multisite']; }
function admin_url($path) { return 'https://example.org/wp-admin/' . $path; }
function network_admin_url($path) { return 'https://example.org/wp-admin/network/' . $path; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function wp_nonce_url($url, $action) { return $url . '&_wpnonce=' . rawurlencode($action); }
require __DIR__ . '/../wp-content/themes/doin/inc/plugin-setup.php';
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$plugins = doin_required_plugins();
check(count($plugins) === 4, 'Exactly four dependencies');
check(count(array_unique(array_column($plugins, 'slug'))) === 4, 'Unique dependencies');
foreach ($plugins as $plugin) {
    $installed = array($plugin['file'] => array());
    $active_plugins = array();
    check(doin_plugin_setup_status($plugin, array()) === 'missing', 'Missing detection');
    check(doin_plugin_setup_status($plugin, $installed) === 'inactive', 'Inactive detection');
    $active_plugins = array($plugin['file']);
    check(doin_plugin_setup_status($plugin, $installed) === 'active', 'Active detection');
    $capabilities = array();
    check(doin_plugin_setup_action_url($plugin, 'missing') === '', 'Install permissions');
    check(doin_plugin_setup_action_url($plugin, 'inactive') === '', 'Activation permissions');
    $capabilities = array('install_plugins' => true, 'activate_plugin' => true);
    foreach (array(false, true) as $multisite) {
        $url = doin_plugin_setup_action_url($plugin, 'missing');
        check(str_contains($url, $multisite ? '/network/update.php?' : '/wp-admin/update.php?'), 'Correct installer');
        check(str_contains($url, rawurlencode('install-plugin_' . $plugin['slug'])), 'Install nonce');
        $url = doin_plugin_setup_action_url($plugin, 'inactive');
        check(str_contains($url, '/wp-admin/plugins.php?'), 'Site activation');
        check(!str_contains($url, 'networkwide'), 'No network activation');
        check(str_contains($url, rawurlencode('activate-plugin_' . $plugin['file'])), 'Activation nonce');
    }
    check(doin_plugin_setup_action_url($plugin, 'active') === '', 'Never reinstall active plugins');
}
echo "Required plugin tests passed\n";
