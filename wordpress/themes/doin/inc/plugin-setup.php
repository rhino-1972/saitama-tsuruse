<?php
/** Required plugin onboarding using WordPress's standard installer. */
if (!defined('ABSPATH')) {
    exit;
}

function doin_required_plugins() {
    return array(
        array('slug' => 'event-organiser', 'file' => 'event-organiser/event-organiser.php', 'name' => 'Event Organiser', 'purpose' => '修練カレンダー・年間行事'),
        array('slug' => 'nextgen-gallery', 'file' => 'nextgen-gallery/nggallery.php', 'name' => 'NextGEN Gallery', 'purpose' => '修練風景の写真ギャラリー'),
        array('slug' => 'contact-form-7', 'file' => 'contact-form-7/wp-contact-form-7.php', 'name' => 'Contact Form 7', 'purpose' => 'お問い合わせフォーム'),
        array('slug' => 'really-simple-captcha', 'file' => 'really-simple-captcha/really-simple-captcha.php', 'name' => 'Really Simple CAPTCHA', 'purpose' => 'お問い合わせフォームの画像認証'),
    );
}

function doin_plugin_setup_status($plugin, $installed) {
    if (!isset($installed[$plugin['file']])) {
        return 'missing';
    }
    return is_plugin_active($plugin['file']) ? 'active' : 'inactive';
}

function doin_plugins_need_setup() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $installed = get_plugins();
    foreach (doin_required_plugins() as $plugin) {
        if (doin_plugin_setup_status($plugin, $installed) !== 'active') {
            return true;
        }
    }
    return false;
}

add_action('after_switch_theme', 'doin_queue_plugin_setup');
function doin_queue_plugin_setup() {
    if (doin_plugins_need_setup()) {
        update_option('_doin_plugin_setup_pending', 1, false);
    }
}

add_action('admin_init', 'doin_redirect_to_plugin_setup');
function doin_redirect_to_plugin_setup() {
    if (!get_option('_doin_plugin_setup_pending') || !current_user_can('manage_options')
        || wp_doing_ajax() || is_network_admin() || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    delete_option('_doin_plugin_setup_pending');
    if (doin_plugins_need_setup()) {
        wp_safe_redirect(admin_url('themes.php?page=doin-plugin-setup'));
        exit;
    }
}

add_action('admin_menu', 'doin_register_plugin_setup_page');
function doin_register_plugin_setup_page() {
    add_theme_page('必要プラグイン', '必要プラグイン', 'manage_options', 'doin-plugin-setup', 'doin_render_plugin_setup_page');
}

add_action('admin_notices', 'doin_plugin_setup_notice');
function doin_plugin_setup_notice() {
    if (!current_user_can('manage_options') || (isset($_GET['page']) && $_GET['page'] === 'doin-plugin-setup')
        || !doin_plugins_need_setup()) {
        return;
    }
    echo '<div class="notice notice-warning"><p>埼玉鶴瀬道院の必要プラグインが未インストール、または未有効化です。 <a href="' . esc_url(admin_url('themes.php?page=doin-plugin-setup')) . '">必要プラグインを設定する</a></p></div>';
}

function doin_plugin_setup_action_url($plugin, $status) {
    if ($status === 'missing' && current_user_can('install_plugins')) {
        $base = is_multisite() ? network_admin_url('update.php') : admin_url('update.php');
        return wp_nonce_url(add_query_arg(array('action' => 'install-plugin', 'plugin' => $plugin['slug']), $base), 'install-plugin_' . $plugin['slug']);
    }
    if ($status === 'inactive' && current_user_can('activate_plugin', $plugin['file'])) {
        // Activate only on this site, not throughout the multisite network.
        return wp_nonce_url(add_query_arg(array('action' => 'activate', 'plugin' => $plugin['file']), admin_url('plugins.php')), 'activate-plugin_' . $plugin['file']);
    }
    return '';
}

function doin_render_plugin_setup_page() {
    if (!current_user_can('manage_options')) {
        wp_die('この画面を表示する権限がありません。');
    }
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $installed = get_plugins();
    $labels = array('missing' => '未インストール', 'inactive' => '未有効化', 'active' => '有効');
    echo '<div class="wrap"><h1>埼玉鶴瀬道院 必要プラグイン</h1>';
    echo '<p>未インストールのプラグインをインストールし、このサイトで有効化してください。インストール後は、この画面へ戻って状態を確認できます。</p>';
    if (is_multisite() && !current_user_can('install_plugins')) {
        echo '<div class="notice notice-info inline"><p>マルチサイトのプラグインインストールは、ネットワーク管理者に依頼してください。</p></div>';
    }
    if (!doin_plugins_need_setup()) {
        echo '<div class="notice notice-success inline"><p>必要なプラグインはすべて有効です。</p></div>';
    }
    echo '<table class="widefat striped"><thead><tr><th>プラグイン</th><th>用途</th><th>状態</th><th>操作</th></tr></thead><tbody>';
    foreach (doin_required_plugins() as $plugin) {
        $status = doin_plugin_setup_status($plugin, $installed);
        $url = doin_plugin_setup_action_url($plugin, $status);
        echo '<tr><td>' . esc_html($plugin['name']) . '</td><td>' . esc_html($plugin['purpose']) . '</td><td>' . esc_html($labels[$status]) . '</td><td>';
        if ($url) {
            echo '<a class="button" href="' . esc_url($url) . '">' . ($status === 'missing' ? 'インストール' : 'このサイトで有効化') . '</a>';
        } elseif ($status !== 'active') {
            echo '権限のある管理者に依頼してください。';
        } else {
            echo '設定済み';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table><p>プラグイン本体はWordPress公式配布元から取得します。通信、PHPバージョン、ファイル書き込み権限などの問題は、WordPress標準インストール画面に表示されます。</p>';
    echo '<p>フォーム・ギャラリー・イベントのデータは別途設定が必要です。Lightningは親テーマであり、この一覧の対象外です。</p></div>';
}
