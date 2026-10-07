<?php

add_action('wp_enqueue_scripts', 'tsuruse_enqueue_theme_styles', 20);
function tsuruse_enqueue_theme_styles() {
    $styles = array(
        'base',
        'page-header',
        'event',
        'sidebar',
        'home',
        'post-archive',
        'comments',
        'application-form',
        'footer',
    );

    $base_dir = get_stylesheet_directory();
    $base_uri = get_stylesheet_directory_uri();

    foreach ($styles as $style) {
        $relative_path = '/css/' . $style . '.css';
        $absolute_path = $base_dir . $relative_path;
        $version       = file_exists($absolute_path) ? (string) filemtime($absolute_path) : null;

        wp_enqueue_style(
            'tsuruse-' . $style,
            $base_uri . $relative_path,
            array(),
            $version
        );

        if ('page-header' === $style) {
            wp_add_inline_style('tsuruse-page-header', tsuruse_dynamic_page_header_css());
        }
    }
}

function tsuruse_site_upload_base_url() {
    $uploads = wp_get_upload_dir();

    if (!empty($uploads['baseurl'])) {
        return trailingslashit((string) $uploads['baseurl']);
    }

    return trailingslashit(content_url('uploads'));
}

function tsuruse_site_upload_asset_url($relative_path) {
    return tsuruse_site_upload_base_url() . ltrim((string) $relative_path, '/');
}

function tsuruse_site_logo_url() {
    return tsuruse_site_upload_asset_url('2026/02/logo.png');
}

/**
 * 1. ログイン画面のロゴを画像に変更し、スタイルを調整
 */
add_action('login_enqueue_scripts', function () {
    $logo_url = tsuruse_site_logo_url();

    ?>
    <style>
        /* ロゴ画像の設定（テキストを画像に切り替え） */
        body.login div#login h1 a {
            background-image: url('<?php echo esc_url($logo_url); ?>') !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: center bottom !important;
            width: 100% !important;
            height: 100px !important; /* ロゴの高さに合わせて調整 */
            text-indent: -9999px !important; /* テキストを隠す */
            margin-bottom: 10px !important;
            display: block !important;
        }

        /* フォーム下のナビゲーション（左寄せ & 余白調整） */
        .login #nav {
            text-align: left !important;
            padding: 8px 0 0 !important; /* フォームとの隙間を詰める */
            margin: 0 !important;
        }

        /* 各リンクの共通スタイル（パスワード忘れ & 新規申込） */
        .login #nav a,
        #custom_reg_link a {
            display: block !important;
            margin-bottom: 8px !important;
            color: #72777c !important;
            text-decoration: none !important;
            font-size: 14px !important;
        }

        .login #nav a:hover,
        #custom_reg_link a:hover {
            color: #2271b1 !important;
        }

        /* 不要な要素（サイトへ戻る・言語切替）を完全に非表示 */
        #backtoblog, .language-switcher {
            display: none !important;
        }
    </style>
    <?php
});

/**
 * 2. ロゴのリンク先とツールチップを変更
 */
add_filter('login_headerurl', function () { return home_url(); });
add_filter('login_headertext', function () { return get_bloginfo('name'); });

/**
 * 3. 言語ドロップダウンを非表示
 */
add_filter('login_display_language_dropdown', '__return_false');

function tsuruse_filter_lightning_theme_options($options) {
    if (!is_array($options)) {
        $options = array();
    }

    $options['head_logo'] = tsuruse_site_logo_url();

    return $options;
}

add_filter('option_lightning_theme_options', 'tsuruse_filter_lightning_theme_options');
add_filter('default_option_lightning_theme_options', 'tsuruse_filter_lightning_theme_options');
add_filter('lightning_get_theme_options', 'tsuruse_filter_lightning_theme_options');
add_filter('lightning_head_logo_image_url', function () {
    return tsuruse_site_logo_url();
});

add_filter('lightning_footerCopyRightCustom', function () {
    return '<p>Copyright &copy; <a href="https://dream-times.net">どり〜む・たいむす</a> All Rights Reserved.</p>';
});

add_filter('lightning_footerPoweredCustom', '__return_empty_string');

if (!defined('TSURUSE_HOME_MESSAGE_PAGE_SLUG')) {
    define('TSURUSE_HOME_MESSAGE_PAGE_SLUG', 'top-message');
}

if (!defined('TSURUSE_HOME_MESSAGE_PAGE_TITLE')) {
    define('TSURUSE_HOME_MESSAGE_PAGE_TITLE', 'トップページ案内文');
}

if (!defined('TSURUSE_HOME_MESSAGE_PAGE_OPTION')) {
    define('TSURUSE_HOME_MESSAGE_PAGE_OPTION', 'tsuruse_home_message_page_id');
}

if (!defined('TSURUSE_HOME_MESSAGE_PAGE_VERSION_OPTION')) {
    define('TSURUSE_HOME_MESSAGE_PAGE_VERSION_OPTION', 'tsuruse_home_message_page_seed_version');
}

if (!defined('TSURUSE_HOME_MESSAGE_PAGE_VERSION')) {
    define('TSURUSE_HOME_MESSAGE_PAGE_VERSION', '20260414_home_message_v1');
}

function tsuruse_home_message_default_content() {
    return implode(
        "\n\n",
        array(
            '<p>本サイトは、埼玉鶴瀬道院にて開催されましたイベントの記録を順次掲載します。道院内のイベントであり、プライバシの事もありますので、ログイン後の閲覧とさせて頂きます。</p>',
            '<p>また、志木スポーツ少年団　佐藤勝己支部長の承認に伴い合同で開催されましたイベントも掲載いたします。</p>',
            '<p>閲覧したい方は、右上のログインより新規登録にてアカウント登録をお願いします。</p>',
        )
    );
}

function tsuruse_get_front_page_post() {
    $page_id = (int) get_option('page_on_front');
    if ($page_id <= 0) {
        return null;
    }

    $page = get_post($page_id);
    if (!$page instanceof WP_Post || 'page' !== $page->post_type) {
        return null;
    }

    return $page;
}

function tsuruse_strip_legacy_home_message_content($content) {
    if (!is_string($content) || '' === trim($content)) {
        return '';
    }

    $patterns = array(
        '/\s*<p>本サイトは、埼玉鶴瀬道院にて開催されましたイベントの記録を順次掲載します。道院内のイベントであり、プライバシの事もありますので、ログイン後の閲覧とさせて頂きます。<\/p>\s*<p>また、志木スポーツ少年団　佐藤勝己支部長の承認に伴い合同で開催されましたイベントも掲載いたします。<\/p>\s*<p>閲覧したい方は、右上のログインより新規登録にてアカウント登録をお願いします。<\/p>\s*/u',
        '/\s*<p>本サイトでは、埼玉鶴瀬道院で開催された各種イベントの様子を、順次掲載しております。<br\s*\/?>掲載内容は道院内での活動記録となるため、プライバシー保護の観点から、ログイン後にご覧いただく形式としております。<\/p>\s*<p>また、志木スポーツ少年団　佐藤勝己支部長のご承認のもと、合同で開催されたイベントについても掲載しております。<\/p>\s*<p>閲覧をご希望の方は、画面右上の「ログイン」より新規登録を行い、アカウントを作成していただきますようお願いいたします。<\/p>\s*/u',
    );

    return trim((string) preg_replace($patterns, '', $content));
}

function tsuruse_split_front_page_content($content) {
    $content = tsuruse_strip_legacy_home_message_content((string) $content);

    if ('' === $content) {
        return array(
            'intro' => '',
            'body'  => '',
        );
    }

    $heading_pattern = '/(?:<!--\s*wp:heading(?:\s+[^>]*)?\s*-->\s*)?<h[1-6][^>]*>\s*What(?:\'|’)?s New\s*<\/h[1-6]>(?:\s*<!--\s*\/wp:heading\s*-->)?/iu';
    if (preg_match($heading_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $offset = (int) $matches[0][1];

        return array(
            'intro' => trim(substr($content, 0, $offset)),
            'body'  => trim(substr($content, $offset)),
        );
    }

    $shortcode_pattern = '/\[showwhatsnew[^\]]*\]/i';
    if (preg_match($shortcode_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $offset = (int) $matches[0][1];

        return array(
            'intro' => trim(substr($content, 0, $offset)),
            'body'  => trim(substr($content, $offset)),
        );
    }

    return array(
        'intro' => trim($content),
        'body'  => '',
    );
}

function tsuruse_get_front_page_content_parts() {
    $page = tsuruse_get_front_page_post();
    if (!$page instanceof WP_Post) {
        return array(
            'intro' => '',
            'body'  => '',
        );
    }

    return tsuruse_split_front_page_content((string) $page->post_content);
}

function tsuruse_get_home_message_page() {
    $page_id = (int) get_option(TSURUSE_HOME_MESSAGE_PAGE_OPTION);

    if ($page_id > 0) {
        $page = get_post($page_id);
        if ($page instanceof WP_Post && 'page' === $page->post_type) {
            return $page;
        }
    }

    $page = get_page_by_path(TSURUSE_HOME_MESSAGE_PAGE_SLUG, OBJECT, 'page');
    if ($page instanceof WP_Post) {
        update_option(TSURUSE_HOME_MESSAGE_PAGE_OPTION, (int) $page->ID, false);
        return $page;
    }

    return null;
}

add_action('init', 'tsuruse_maybe_seed_home_message_page', 20);
function tsuruse_maybe_seed_home_message_page() {
    $page = tsuruse_get_home_message_page();

    if (!$page instanceof WP_Post) {
        $page_id = wp_insert_post(
            array(
                'post_type'    => 'page',
                'post_status'  => 'private',
                'post_title'   => TSURUSE_HOME_MESSAGE_PAGE_TITLE,
                'post_name'    => TSURUSE_HOME_MESSAGE_PAGE_SLUG,
                'post_content' => tsuruse_home_message_default_content(),
            ),
            true
        );

        if (!is_wp_error($page_id) && $page_id > 0) {
            update_option(TSURUSE_HOME_MESSAGE_PAGE_OPTION, (int) $page_id, false);
            update_option(TSURUSE_HOME_MESSAGE_PAGE_VERSION_OPTION, TSURUSE_HOME_MESSAGE_PAGE_VERSION, false);
        }

        return;
    }

    update_option(TSURUSE_HOME_MESSAGE_PAGE_OPTION, (int) $page->ID, false);

    if (TSURUSE_HOME_MESSAGE_PAGE_VERSION !== get_option(TSURUSE_HOME_MESSAGE_PAGE_VERSION_OPTION)) {
        update_option(TSURUSE_HOME_MESSAGE_PAGE_VERSION_OPTION, TSURUSE_HOME_MESSAGE_PAGE_VERSION, false);
    }
}

function tsuruse_home_slider_slides() {
    return array(
        array(
            'image' => '2026/02/IMG_0500-scaled-1.jpeg',
            'alt'   => '埼玉鶴瀬道院のイベント風景',
        ),
        array(
            'image' => '2026/02/IMG_0401-scaled-1.jpeg',
            'alt'   => '埼玉鶴瀬道院の演武風景',
        ),
        array(
            'image' => '2026/02/DSC_4200-scaled-1.jpg',
            'alt'   => '表彰の様子',
        ),
        array(
            'image' => '2026/04/IMG_3006.jpeg',
            'alt'   => '富士見市表彰式の集合写真',
        ),
        array(
            'image' => '2026/02/IMG_0358-scaled-1.jpeg',
            'alt'   => 'イベントの記念花束贈呈',
        ),
    );
}

function tsuruse_home_slider_image_url($relative_path) {
    return tsuruse_site_upload_asset_url($relative_path);
}

function tsuruse_category_event_header_image_url() {
    return tsuruse_site_upload_asset_url('2026/02/IMG_0402-2048x1161.jpeg');
}

function tsuruse_default_page_header_image_url() {
    return tsuruse_site_upload_asset_url('2026/03/DSC_0261-scaled-1-2048x1366.jpg');
}

function tsuruse_dynamic_page_header_css() {
    return implode(
        "\n",
        array(
            sprintf(
                ".archive.category.category-event .page-header{background-image:linear-gradient(rgba(11, 92, 117, 0.54), rgba(11, 92, 117, 0.54)),url('%s');}",
                esc_url_raw(tsuruse_category_event_header_image_url())
            ),
            sprintf(
                ".tsuruse-post-archive-page .page-header,.single-post .page-header,.single-pictures .page-header,.post-type-archive-pictures .page-header{background-image:linear-gradient(rgba(11, 92, 117, 0.52), rgba(11, 92, 117, 0.52)),url('%s');}",
                esc_url_raw(tsuruse_default_page_header_image_url())
            ),
        )
    );
}

add_filter('lightning_top_slide_count_max', 'tsuruse_home_slider_count_max');
function tsuruse_home_slider_count_max($count) {
    return max((int) $count, count(tsuruse_home_slider_slides()));
}

add_filter('lightning_g3_slider_default_options', 'tsuruse_home_slider_default_options');
function tsuruse_home_slider_default_options($options) {
    $slide_keys = array(
        'top_slide_image',
        'top_slide_image_mobile',
        'top_slide_alt',
        'top_slide_cover_color',
        'top_slide_cover_opacity',
        'top_slide_url',
        'top_slide_link_blank',
        'top_slide_text_title',
        'top_slide_text_caption',
        'top_slide_text_btn',
        'top_slide_text_align',
        'top_slide_text_color',
        'top_slide_text_shadow_use',
        'top_slide_text_shadow_color',
    );

    $options['top_slide_display'] = 'display';
    $options['top_slide_time']    = 5000;
    $options['top_slide_effect']  = 'fade';
    $options['top_slide_speed']   = 900;

    $slide_count = count(tsuruse_home_slider_slides());

    for ($i = 1; $i <= $slide_count; $i++) {
        foreach ($slide_keys as $key) {
            $options[$key . '_' . $i] = in_array($key, array('top_slide_link_blank', 'top_slide_text_shadow_use'), true) ? false : '';
        }
    }

    foreach (tsuruse_home_slider_slides() as $index => $slide) {
        $slide_number = $index + 1;
        $image_url    = tsuruse_home_slider_image_url($slide['image']);

        $options['top_slide_image_' . $slide_number]        = $image_url;
        $options['top_slide_image_mobile_' . $slide_number] = $image_url;
        $options['top_slide_alt_' . $slide_number]          = $slide['alt'];
        $options['top_slide_text_align_' . $slide_number]   = 'center';
        $options['top_slide_text_color_' . $slide_number]   = '#ffffff';
        $options['top_slide_text_shadow_use_' . $slide_number] = false;
        $options['top_slide_text_shadow_color_' . $slide_number] = '';
    }

    return $options;
}

add_action('lightning_site_body_before', 'tsuruse_render_home_message_section', 5);
function tsuruse_render_home_message_section() {
    if (!is_front_page()) {
        return;
    }

    $front_page_parts = tsuruse_get_front_page_content_parts();
    if ('' !== $front_page_parts['intro']) {
        $source  = 'front-page';
        $content = $front_page_parts['intro'];
    } else {
        $page    = tsuruse_get_home_message_page();
        $source  = $page instanceof WP_Post ? 'page' : 'fallback';
        $content = 'page' === $source ? $page->post_content : tsuruse_home_message_default_content();
    }

    if ('' === trim(wp_strip_all_tags($content))) {
        return;
    }
    ?>
    <section class="tsuruse-home-intro" data-home-message-source="<?php echo esc_attr($source); ?>">
        <div class="container">
            <div class="tsuruse-home-intro__content">
                <?php echo apply_filters('the_content', $content); ?>
            </div>
        </div>
    </section>
    <?php
}

add_filter('the_content', 'tsuruse_filter_front_page_content', 1);
function tsuruse_filter_front_page_content($content) {
    if (is_admin() || !is_front_page() || !is_main_query() || !in_the_loop()) {
        return $content;
    }

    $front_page = tsuruse_get_front_page_post();
    if (!$front_page instanceof WP_Post || (int) get_the_ID() !== (int) $front_page->ID) {
        return $content;
    }

    $parts = tsuruse_get_front_page_content_parts();
    if ('' === $parts['body']) {
        return '';
    }

    return $parts['body'];
}

function tsuruse_event_archive_path() {
    return '/event/';
}

function tsuruse_event_archive_url() {
    return home_url(tsuruse_event_archive_path());
}

function tsuruse_post_archive_path() {
    return '/archives/';
}

function tsuruse_post_archive_url() {
    return home_url(tsuruse_post_archive_path());
}

add_filter('query_vars', 'tsuruse_post_archive_query_vars');
function tsuruse_post_archive_query_vars($vars) {
    $vars[] = 'tsuruse_post_archive';

    return $vars;
}

add_action('init', 'tsuruse_register_post_archive_rewrite', 20);
function tsuruse_register_post_archive_rewrite() {
    add_rewrite_rule('^archives/?$', 'index.php?post_type=post&tsuruse_post_archive=1', 'top');
    add_rewrite_rule('^archives/page/([0-9]+)/?$', 'index.php?post_type=post&tsuruse_post_archive=1&paged=$matches[1]', 'top');
}

function tsuruse_is_post_archive_query($query = null) {
    if ($query instanceof WP_Query) {
        return !is_admin()
            && $query->is_main_query()
            && '1' === (string) $query->get('tsuruse_post_archive');
    }

    return '1' === (string) get_query_var('tsuruse_post_archive');
}

add_filter('body_class', 'tsuruse_post_archive_body_class');
function tsuruse_post_archive_body_class($classes) {
    if (tsuruse_is_post_archive_query()) {
        $classes[] = 'tsuruse-post-archive-page';
    }

    return $classes;
}

function tsuruse_should_prioritize_sticky_posts($query) {
    return $query instanceof WP_Query
        && !is_admin()
        && $query->is_main_query()
        && (tsuruse_is_post_archive_query($query) || $query->is_category() || $query->is_home());
}

add_filter('posts_orderby', 'tsuruse_prioritize_sticky_posts_orderby', 10, 2);
function tsuruse_prioritize_sticky_posts_orderby($orderby, $query) {
    if (!tsuruse_should_prioritize_sticky_posts($query)) {
        return $orderby;
    }

    $sticky_posts = array_values(array_filter(array_map('absint', (array) get_option('sticky_posts'))));
    if ([] === $sticky_posts) {
        return $orderby;
    }

    global $wpdb;

    $sticky_orderby = sprintf(
        'CASE WHEN %1$s.ID IN (%2$s) THEN 0 ELSE 1 END',
        $wpdb->posts,
        implode(', ', $sticky_posts)
    );

    $orderby = trim((string) $orderby);

    if ('' === $orderby) {
        return $sticky_orderby . sprintf(', %s.post_date DESC', $wpdb->posts);
    }

    if (false !== strpos($orderby, $sticky_orderby)) {
        return $orderby;
    }

    return $sticky_orderby . ', ' . $orderby;
}

add_action('template_redirect', 'tsuruse_redirect_legacy_event_urls', 1);
function tsuruse_redirect_legacy_event_urls() {
    if (is_admin() || empty($_SERVER['REQUEST_URI'])) {
        return;
    }

    $request_uri = wp_unslash((string) $_SERVER['REQUEST_URI']);
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $legacy_path = untrailingslashit((string) wp_parse_url(home_url('/archives/pictures/'), PHP_URL_PATH));

    if ('' === $request_path || 0 !== strpos($request_path, $legacy_path)) {
        return;
    }

    $suffix = ltrim(substr($request_path, strlen($legacy_path)), '/');
    $target = tsuruse_event_archive_url();

    if ('' !== $suffix) {
        $target = trailingslashit($target) . $suffix;
    }

    $query = (string) wp_parse_url($request_uri, PHP_URL_QUERY);
    if ('' !== $query) {
        $target .= '?' . $query;
    }

    wp_safe_redirect($target, 301);
    exit;
}

/**
 * 4. 「新規申込はこちら」リンクをJavaScriptで適切な位置に挿入
 */
add_action('login_footer', function () {
    $register_url = home_url('/application/');
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nav = document.querySelector('.login #nav');
            if (nav) {
                const register = document.createElement('p');
                register.id = 'custom_reg_link';
                register.style.margin = '0';
                register.innerHTML = '<a href="<?php echo esc_url($register_url); ?>">新規申込はこちら</a>';
                // nav（パスワード忘れリンクの親要素）の最後に追加
                nav.appendChild(register);
            }
        });
    </script>
    <?php
});

/**
 * 5. ログイン後のリダイレクト処理
 */
add_filter('login_redirect', 'tsuruse_login_redirect', 10, 3);
function tsuruse_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (!isset($user->ID)) {
        return $redirect_to;
    }

    $user_id = (int) $user->ID;

    if (user_can($user_id, 'administrator')) {
        return admin_url();
    }

    if (tsuruse_user_has_gallery_access($user_id)) {
        return tsuruse_event_archive_url();
    }

    return home_url('/pending/');
}

add_filter('authenticate', 'tsuruse_block_disabled_member_login', 35);
function tsuruse_block_disabled_member_login($user) {
    if (!$user instanceof WP_User) {
        return $user;
    }

    if (!tsuruse_is_managed_member_user((int) $user->ID)) {
        return $user;
    }

    if (!tsuruse_is_member_account_disabled((int) $user->ID)) {
        return $user;
    }

    return new WP_Error(
        'tsuruse_account_disabled',
        'このアカウントは長期間ログインが確認できないため無効化されています。サイト管理者へお問い合わせください。'
    );
}

add_action('wp_login', 'tsuruse_record_member_login', 10, 2);
function tsuruse_record_member_login($user_login, $user) {
    if (!$user instanceof WP_User || !tsuruse_is_managed_member_user((int) $user->ID)) {
        return;
    }

    $timestamp = current_time('timestamp');
    update_user_meta($user->ID, '_tsuruse_last_login_at', date_i18n('Y-m-d H:i:s', $timestamp));
    update_user_meta($user->ID, '_tsuruse_last_login_ts', $timestamp);
    delete_user_meta($user->ID, '_tsuruse_account_delete_candidate');
    delete_user_meta($user->ID, '_tsuruse_account_delete_candidate_at');
}

add_action('set_user_role', 'tsuruse_handle_member_role_change', 10, 3);
function tsuruse_handle_member_role_change($user_id, $new_role, $old_roles) {
    if ('pending' === $new_role) {
        return;
    }

    if (!tsuruse_is_managed_member_user((int) $user_id)) {
        return;
    }

    tsuruse_sync_member_affiliation_roles((int) $user_id);
}

add_action('publish_post', 'notify_members_by_category', 10, 2);

function notify_members_by_category($post_id, $post) {

    if (wp_is_post_revision($post_id)) return;

    // カテゴリー → UAMグループ
    $map = array(
        'tsuruse' => 'tsuruse',
        'wakoku'  => 'wakoku',
        'saitama' => 'saitama'
    );

    $categories = get_the_category($post_id);
    if (!$categories) return;

    foreach ($categories as $cat) {

        $slug = $cat->slug;

        if (!isset($map[$slug])) continue;

        $target_group = $map[$slug];

        $users = get_users();

        foreach ($users as $user) {

            // UAMグループ取得
            $groups = get_user_meta($user->ID, 'uam_usergroups', true);

            if (!$groups) continue;

            if (in_array($target_group, (array)$groups)) {

                $subject = '【イベント写真館_新着投稿】' . $post->post_title;

                $message  = "新しいイベントが公開されました。\n\n";
                $message .= "タイトル：" . $post->post_title . "\n";
                $message .= "URL：" . get_permalink($post_id) . "\n\n";
                $message .= "※閲覧にはログインが必要です";

                wp_mail($user->user_email, $subject, $message);
            }
        }
    }
}

if (!defined('TSURUSE_EVENT_POST_TYPE')) {
    define('TSURUSE_EVENT_POST_TYPE', 'event');
}

if (!defined('TSURUSE_EVENT_TAXONOMY')) {
    define('TSURUSE_EVENT_TAXONOMY', 'event_category');
}

if (!defined('TSURUSE_EVENT_HONBU_TAG_TAXONOMY')) {
    define('TSURUSE_EVENT_HONBU_TAG_TAXONOMY', 'event_honbu_tag');
}

if (!defined('TSURUSE_EVENT_TAIKAI_TAG_TAXONOMY')) {
    define('TSURUSE_EVENT_TAIKAI_TAG_TAXONOMY', 'event_taikai_tag');
}

if (!defined('TSURUSE_EVENT_GYOJI_TAG_TAXONOMY')) {
    define('TSURUSE_EVENT_GYOJI_TAG_TAXONOMY', 'event_gyoji_tag');
}

if (!defined('TSURUSE_EVENT_TRANSFER_TERM_NAME')) {
    define('TSURUSE_EVENT_TRANSFER_TERM_NAME', 'イベント転記');
}

if (!defined('TSURUSE_EVENT_TRANSFER_TERM_SLUG')) {
    define('TSURUSE_EVENT_TRANSFER_TERM_SLUG', 'event-transfer');
}

if (!defined('TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY')) {
    define('TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY', 'ngg_tag');
}

if (!defined('TSURUSE_PICTURE_MEMBER_TAG_PAGE_PATH')) {
    define('TSURUSE_PICTURE_MEMBER_TAG_PAGE_PATH', 'タグページ');
}

if (!defined('TSURUSE_LEGACY_MEMBER_ROLE')) {
    define('TSURUSE_LEGACY_MEMBER_ROLE', 'members');
}

if (!defined('TSURUSE_ACCOUNT_APPLICATION_POST_TYPE')) {
    define('TSURUSE_ACCOUNT_APPLICATION_POST_TYPE', 'tsuruse_application');
}

if (!defined('TSURUSE_ACCOUNT_APPLICATION_FORM_ID')) {
    define('TSURUSE_ACCOUNT_APPLICATION_FORM_ID', 1610);
}

if (!defined('TSURUSE_ACCOUNT_APPLICATION_CAPABILITY')) {
    define('TSURUSE_ACCOUNT_APPLICATION_CAPABILITY', 'edit_users');
}

if (!defined('TSURUSE_PHOTO_PROVISION_POST_TYPE')) {
    define('TSURUSE_PHOTO_PROVISION_POST_TYPE', 'tsuruse_photo_req');
}

if (!defined('TSURUSE_PHOTO_PROVISION_CAPABILITY')) {
    define('TSURUSE_PHOTO_PROVISION_CAPABILITY', 'edit_posts');
}

if (!defined('TSURUSE_ACCOUNT_INACTIVITY_MONTHS')) {
    define('TSURUSE_ACCOUNT_INACTIVITY_MONTHS', 24);
}

if (!defined('TSURUSE_ACCOUNT_DELETE_CANDIDATE_MONTHS')) {
    define('TSURUSE_ACCOUNT_DELETE_CANDIDATE_MONTHS', 12);
}

if (!defined('TSURUSE_EVENT_DEADLINE_META')) {
    define('TSURUSE_EVENT_DEADLINE_META', '_tsuruse_event_deadline');
    define('TSURUSE_EVENT_APPLICATION_URL_META', '_tsuruse_event_application_url');
}

function tsuruse_event_meta_fields() {
    return array(
        TSURUSE_EVENT_DEADLINE_META        => array(
            'label'       => '申込締切',
            'type'        => 'date',
            'description' => '申込が不要な場合は空欄で構いません。',
        ),
        TSURUSE_EVENT_APPLICATION_URL_META => array(
            'label'       => '申込URL',
            'type'        => 'url',
            'description' => '外部フォームなどのURLを設定できます。',
        ),
    );
}

function tsuruse_event_group_taxonomies() {
    return array(
        TSURUSE_EVENT_HONBU_TAG_TAXONOMY => array(
            'label'       => '本部行事',
            'rewrite_slug'=> 'honbu-event',
            'description' => '本部行事タグです。',
        ),
        TSURUSE_EVENT_TAIKAI_TAG_TAXONOMY => array(
            'label'       => '大会行事',
            'rewrite_slug'=> 'taikai-event',
            'description' => '大会行事タグです。',
        ),
        TSURUSE_EVENT_GYOJI_TAG_TAXONOMY => array(
            'label'       => '行事',
            'rewrite_slug'=> 'gyoji-event',
            'description' => '行事タグです。',
        ),
    );
}

function tsuruse_picture_member_tag_taxonomy_config() {
    return array(
        'label'        => '少年部',
        'rewrite_slug' => 'shonenbu',
        'description'  => '写真に含まれる少年部タグです。',
    );
}

function tsuruse_picture_member_tag_order() {
    return array(
        'りお',
        'ななこ',
        'ももか',
        'なつめ',
        'こうしろう',
        'そうし',
        'いぶき',
        'ゆうや',
        'ぜんた',
        'いちか',
        'あずさ',
        'せんと',
        'しんのすけ',
    );
}

function tsuruse_event_group_term_orders() {
    return array(
        TSURUSE_EVENT_TAIKAI_TAG_TAXONOMY => array(
            '富士見市大会',
            '志木大会',
            '錬成大会',
            '埼玉県大会',
            'インターハイ',
            '全国大会',
        ),
    );
}

function tsuruse_filter_public_event_terms($terms, $taxonomy = '') {
    if (empty($terms) || !is_array($terms)) {
        return array();
    }

    $filtered_terms = array_values(
        array_filter(
            $terms,
            function ($term) use ($taxonomy) {
                if (!$term instanceof WP_Term) {
                    return false;
                }

                $term_taxonomy = '' !== (string) $taxonomy && empty($term->taxonomy)
                    ? (string) $taxonomy
                    : (string) $term->taxonomy;

                if (TSURUSE_EVENT_GYOJI_TAG_TAXONOMY !== $term_taxonomy) {
                    return true;
                }

                return TSURUSE_EVENT_TRANSFER_TERM_SLUG !== (string) $term->slug;
            }
        )
    );

    return $filtered_terms;
}

function tsuruse_post_sidebar_category_order() {
    return array(
        'news',
        'doin',
        'festival',
        'event',
    );
}

function tsuruse_picture_sidebar_taxonomies() {
    return array_merge(
        array_keys(tsuruse_event_group_taxonomies()),
        array(TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY)
    );
}

function tsuruse_sort_terms_by_name_order($terms, $ordered_names) {
    if (empty($terms) || empty($ordered_names)) {
        return $terms;
    }

    $weight_map = array_flip(array_values($ordered_names));

    usort($terms, function ($left, $right) use ($weight_map) {
        $left_weight  = array_key_exists($left->name, $weight_map) ? $weight_map[$left->name] : PHP_INT_MAX;
        $right_weight = array_key_exists($right->name, $weight_map) ? $weight_map[$right->name] : PHP_INT_MAX;

        if ($left_weight === $right_weight) {
            return strnatcasecmp($left->name, $right->name);
        }

        return $left_weight <=> $right_weight;
    });

    return $terms;
}

function tsuruse_sort_terms_by_slug_order($terms, $ordered_slugs) {
    if (empty($terms) || empty($ordered_slugs)) {
        return $terms;
    }

    $weight_map = array_flip(array_values($ordered_slugs));

    usort($terms, function ($left, $right) use ($weight_map) {
        $left_weight  = array_key_exists($left->slug, $weight_map) ? $weight_map[$left->slug] : PHP_INT_MAX;
        $right_weight = array_key_exists($right->slug, $weight_map) ? $weight_map[$right->slug] : PHP_INT_MAX;

        if ($left_weight === $right_weight) {
            return strnatcasecmp($left->name, $right->name);
        }

        return $left_weight <=> $right_weight;
    });

    return $terms;
}

function tsuruse_get_picture_member_tag_terms() {
    if (!taxonomy_exists(TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY)) {
        return array();
    }

    $terms = get_terms(
        array(
            'taxonomy'   => TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    return tsuruse_sort_terms_by_name_order($terms, tsuruse_picture_member_tag_order());
}

function tsuruse_get_sidebar_terms($taxonomy) {
    if (!taxonomy_exists($taxonomy)) {
        return array();
    }

    $hide_empty = !in_array($taxonomy, tsuruse_picture_sidebar_taxonomies(), true);

    $terms = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => $hide_empty,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    $terms = tsuruse_filter_public_event_terms($terms, $taxonomy);
    if (empty($terms)) {
        return array();
    }

    if (TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY === $taxonomy) {
        return tsuruse_sort_terms_by_name_order($terms, tsuruse_picture_member_tag_order());
    }

    $term_orders = tsuruse_event_group_term_orders();
    if (isset($term_orders[$taxonomy])) {
        return tsuruse_sort_terms_by_name_order($terms, $term_orders[$taxonomy]);
    }

    return $terms;
}

function tsuruse_get_picture_member_tag_page_url() {
    $page = get_page_by_path(TSURUSE_PICTURE_MEMBER_TAG_PAGE_PATH, OBJECT, 'page');

    if ($page instanceof WP_Post) {
        return get_permalink($page);
    }

    return home_url('/' . trim((string) TSURUSE_PICTURE_MEMBER_TAG_PAGE_PATH, '/') . '/');
}

function tsuruse_get_picture_member_tag_term_link($term) {
    $base_url = untrailingslashit((string) tsuruse_get_picture_member_tag_page_url());
    $slug     = isset($term->slug) ? rawurlencode((string) $term->slug) : '';

    if ('' === $slug) {
        return '';
    }

    return $base_url . '/nggallery/tags/' . $slug;
}

function tsuruse_get_sidebar_term_link($taxonomy, $term) {
    if (TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY === $taxonomy) {
        return tsuruse_get_picture_member_tag_term_link($term);
    }

    return get_term_link($term);
}

function tsuruse_get_post_sidebar_sections() {
    $categories = get_categories(
        array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
        )
    );

    if (empty($categories) || is_wp_error($categories)) {
        return array();
    }

    $categories = tsuruse_sort_terms_by_slug_order($categories, tsuruse_post_sidebar_category_order());
    $sections   = array();

    foreach ($categories as $category) {
        $posts = get_posts(
            array(
                'post_type'              => 'post',
                'post_status'            => 'publish',
                'posts_per_page'         => -1,
                'category__in'           => array((int) $category->term_id),
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        if (empty($posts)) {
            continue;
        }

        $sections[] = array(
            'term'  => $category,
            'posts' => $posts,
        );
    }

    return $sections;
}

function tsuruse_is_picture_sidebar_context() {
    return is_singular(TSURUSE_PICTURES_POST_TYPE)
        || is_post_type_archive(TSURUSE_PICTURES_POST_TYPE)
        || is_tax(tsuruse_picture_sidebar_taxonomies());
}

function tsuruse_is_post_sidebar_context() {
    return is_singular('post') || is_category() || tsuruse_is_post_archive_query();
}

function tsuruse_register_event_post_type() {
    // The site now uses only the "pictures" post type for event information.
    return;
}

if (!defined('TSURUSE_PICTURES_POST_TYPE')) {
    define('TSURUSE_PICTURES_POST_TYPE', 'pictures');
}

function tsuruse_pictures_post_type_args() {
    return array(
        'labels' => array(
            'name'               => 'イベント情報',
            'singular_name'      => 'イベント情報',
            'menu_name'          => 'イベント情報',
            'name_admin_bar'     => 'イベント情報',
            'add_new'            => '新規追加',
            'add_new_item'       => 'イベント情報を追加',
            'edit_item'          => 'イベント情報を編集',
            'new_item'           => '新しいイベント情報',
            'view_item'          => 'イベント情報を表示',
            'search_items'       => 'イベント情報を検索',
            'not_found'          => 'イベント情報が見つかりませんでした。',
            'not_found_in_trash' => 'ゴミ箱にイベント情報はありません。',
            'all_items'          => 'イベント情報一覧',
            'archives'           => 'イベント情報一覧',
        ),
        'public'              => true,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'menu_position'       => 7,
        'menu_icon'           => 'dashicons-format-gallery',
        'has_archive'         => trim(tsuruse_event_archive_path(), '/'),
        'rewrite'             => array(
            'slug'       => trim(tsuruse_event_archive_path(), '/'),
            'with_front' => false,
        ),
        'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'comments', 'revisions'),
        'taxonomies'          => tsuruse_picture_sidebar_taxonomies(),
        'publicly_queryable'  => true,
        'show_in_nav_menus'   => true,
        'exclude_from_search' => false,
    );
}

function tsuruse_sync_existing_pictures_post_type() {
    $post_type_object = get_post_type_object(TSURUSE_PICTURES_POST_TYPE);
    if (!$post_type_object instanceof WP_Post_Type) {
        return;
    }

    $args = tsuruse_pictures_post_type_args();

    foreach (array(
        'public',
        'show_ui',
        'show_in_rest',
        'menu_position',
        'menu_icon',
        'has_archive',
        'rewrite',
        'supports',
        'taxonomies',
        'publicly_queryable',
        'show_in_nav_menus',
        'exclude_from_search',
    ) as $property) {
        if (array_key_exists($property, $args)) {
            $post_type_object->{$property} = $args[$property];
        }
    }

    if (!empty($args['labels']) && is_array($args['labels'])) {
        $post_type_object->labels = (object) array_merge((array) $post_type_object->labels, $args['labels']);
        if (!empty($args['labels']['name'])) {
            $post_type_object->label = $args['labels']['name'];
        }
    }
}

add_action('init', 'tsuruse_register_pictures_post_type');
function tsuruse_register_pictures_post_type() {
    if (post_type_exists(TSURUSE_PICTURES_POST_TYPE)) {
        tsuruse_sync_existing_pictures_post_type();

        foreach (tsuruse_picture_sidebar_taxonomies() as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                register_taxonomy_for_object_type($taxonomy, TSURUSE_PICTURES_POST_TYPE);
            }
        }
        return;
    }

    register_post_type(
        TSURUSE_PICTURES_POST_TYPE,
        tsuruse_pictures_post_type_args()
    );

    foreach (tsuruse_picture_sidebar_taxonomies() as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            register_taxonomy_for_object_type($taxonomy, TSURUSE_PICTURES_POST_TYPE);
        }
    }
}

add_action('init', 'tsuruse_register_pictures_rewrite_rules', 21);
function tsuruse_register_pictures_rewrite_rules() {
    $archive_slug = trim(tsuruse_event_archive_path(), '/');

    if ('' === $archive_slug) {
        return;
    }

    add_rewrite_rule('^' . preg_quote($archive_slug, '/') . '/?$', 'index.php?post_type=' . TSURUSE_PICTURES_POST_TYPE, 'top');
    add_rewrite_rule('^' . preg_quote($archive_slug, '/') . '/page/([0-9]+)/?$', 'index.php?post_type=' . TSURUSE_PICTURES_POST_TYPE . '&paged=$matches[1]', 'top');
    add_rewrite_rule('^' . preg_quote($archive_slug, '/') . '/([^/]+)/?$', 'index.php?' . TSURUSE_PICTURES_POST_TYPE . '=$matches[1]', 'top');
}

add_filter('post_type_link', 'tsuruse_force_pictures_post_type_link', 10, 4);
function tsuruse_force_pictures_post_type_link($post_link, $post, $leavename, $sample) {
    if (!$post instanceof WP_Post || TSURUSE_PICTURES_POST_TYPE !== $post->post_type) {
        return $post_link;
    }

    $post_name = $sample && $leavename ? '%postname%' : $post->post_name;
    $path      = trailingslashit(trim(tsuruse_event_archive_path(), '/')) . $post_name;

    return home_url(user_trailingslashit($path, 'single'));
}

add_filter('post_type_archive_link', 'tsuruse_force_pictures_archive_link', 10, 2);
function tsuruse_force_pictures_archive_link($link, $post_type) {
    if (TSURUSE_PICTURES_POST_TYPE !== $post_type) {
        return $link;
    }

    return tsuruse_event_archive_url();
}

add_filter('redirect_canonical', 'tsuruse_normalize_legacy_pictures_canonical', 10, 2);
function tsuruse_normalize_legacy_pictures_canonical($redirect_url, $requested_url) {
    if (empty($redirect_url) || empty($requested_url)) {
        return $redirect_url;
    }

    $legacy_base = untrailingslashit(home_url('/archives/pictures/'));
    $event_base  = untrailingslashit(tsuruse_event_archive_url());

    if (0 !== strpos((string) $redirect_url, $legacy_base)) {
        return $redirect_url;
    }

    return $event_base . substr((string) $redirect_url, strlen($legacy_base));
}

add_action('init', 'tsuruse_register_event_taxonomy');
function tsuruse_register_event_taxonomy() {
    foreach (tsuruse_event_group_taxonomies() as $taxonomy => $config) {
        register_taxonomy(
            $taxonomy,
            array(TSURUSE_PICTURES_POST_TYPE),
            array(
                'labels'            => array(
                    'name'                       => $config['label'],
                    'singular_name'              => $config['label'],
                    'search_items'               => $config['label'] . 'を検索',
                    'popular_items'              => 'よく使われている' . $config['label'],
                    'all_items'                  => $config['label'] . '一覧',
                    'edit_item'                  => $config['label'] . 'を編集',
                    'update_item'                => $config['label'] . 'を更新',
                    'add_new_item'               => $config['label'] . 'を追加',
                    'new_item_name'              => '新しい' . $config['label'] . '名',
                    'separate_items_with_commas' => $config['label'] . 'をカンマ区切りで入力',
                    'add_or_remove_items'        => $config['label'] . 'の追加または削除',
                    'choose_from_most_used'      => 'よく使う' . $config['label'] . 'から選択',
                    'menu_name'                  => $config['label'],
                ),
                'description'       => $config['description'],
                'public'            => true,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_admin_column' => false,
                'show_in_rest'      => true,
                'show_in_menu'      => false,
                'rewrite'           => array(
                    'slug'       => $config['rewrite_slug'],
                    'with_front' => false,
                ),
            )
        );
    }
}

add_action('init', 'tsuruse_ensure_event_transfer_term', 20);
function tsuruse_ensure_event_transfer_term() {
    if (!taxonomy_exists(TSURUSE_EVENT_GYOJI_TAG_TAXONOMY)) {
        return;
    }

    $term = get_term_by('slug', TSURUSE_EVENT_TRANSFER_TERM_SLUG, TSURUSE_EVENT_GYOJI_TAG_TAXONOMY);
    if ($term instanceof WP_Term) {
        return;
    }

    $created_term = wp_insert_term(
        TSURUSE_EVENT_TRANSFER_TERM_NAME,
        TSURUSE_EVENT_GYOJI_TAG_TAXONOMY,
        array(
            'slug'        => TSURUSE_EVENT_TRANSFER_TERM_SLUG,
            'description' => 'メインサイトのイベント情報一覧へ転記する写真を抽出する内部用タグです。',
        )
    );

    if (is_wp_error($created_term)) {
        $existing_term_id = (int) $created_term->get_error_data('term_exists');
        if ($existing_term_id > 0) {
            return;
        }
    }
}

add_action('init', 'tsuruse_register_picture_member_tag_taxonomy');
function tsuruse_register_picture_member_tag_taxonomy() {
    $config = tsuruse_picture_member_tag_taxonomy_config();

    register_taxonomy(
        TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY,
        array(TSURUSE_PICTURES_POST_TYPE),
        array(
            'labels'            => array(
                'name'                       => $config['label'],
                'singular_name'              => $config['label'],
                'search_items'               => $config['label'] . 'を検索',
                'popular_items'              => 'よく使われている' . $config['label'],
                'all_items'                  => $config['label'] . '一覧',
                'edit_item'                  => $config['label'] . 'を編集',
                'update_item'                => $config['label'] . 'を更新',
                'add_new_item'               => $config['label'] . 'を追加',
                'new_item_name'              => '新しい' . $config['label'] . '名',
                'separate_items_with_commas' => $config['label'] . 'をカンマ区切りで入力',
                'add_or_remove_items'        => $config['label'] . 'の追加または削除',
                'choose_from_most_used'      => 'よく使う' . $config['label'] . 'から選択',
                'menu_name'                  => $config['label'],
            ),
            'description'       => $config['description'],
            'public'            => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'show_in_menu'      => false,
            'rewrite'           => array(
                'slug'       => $config['rewrite_slug'],
                'with_front' => false,
            ),
        )
    );
}

add_action('init', 'tsuruse_seed_picture_member_tags', 25);
function tsuruse_seed_picture_member_tags() {
    if (!taxonomy_exists(TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY)) {
        return;
    }

    foreach (tsuruse_picture_member_tag_order() as $term_name) {
        if (term_exists($term_name, TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY)) {
            continue;
        }

        wp_insert_term(
            $term_name,
            TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY,
            array(
                'slug' => rawurlencode($term_name),
            )
        );
    }
}

add_action('init', 'tsuruse_register_event_post_meta');
function tsuruse_register_event_post_meta() {
    foreach (array_keys(tsuruse_event_meta_fields()) as $meta_key) {
        register_post_meta(
            TSURUSE_EVENT_POST_TYPE,
            $meta_key,
            array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => function () {
                    return current_user_can('edit_posts');
                },
            )
        );
    }
}

function tsuruse_get_event_taxonomy_terms($taxonomy, $post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $terms   = get_the_terms($post_id, $taxonomy);

    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    return tsuruse_filter_public_event_terms($terms, $taxonomy);
}

function tsuruse_get_event_terms($post_id = null) {
    return tsuruse_get_event_taxonomy_terms(TSURUSE_EVENT_TAXONOMY, $post_id);
}

function tsuruse_get_event_term_names($post_id = null) {
    $terms = tsuruse_get_event_terms($post_id);

    if (empty($terms)) {
        return array();
    }

    return wp_list_pluck($terms, 'name');
}

function tsuruse_get_event_group_terms($post_id = null) {
    $grouped_terms = array();

    foreach (tsuruse_event_group_taxonomies() as $taxonomy => $config) {
        $grouped_terms[$taxonomy] = tsuruse_get_event_taxonomy_terms($taxonomy, $post_id);
    }

    return $grouped_terms;
}

function tsuruse_get_event_group_term_names($post_id = null) {
    $grouped_names = array();

    foreach (tsuruse_get_event_group_terms($post_id) as $taxonomy => $terms) {
        $grouped_names[$taxonomy] = empty($terms) ? array() : wp_list_pluck($terms, 'name');
    }

    return $grouped_names;
}

function tsuruse_get_event_badge_names($post_id = null) {
    $badge_names = tsuruse_get_event_term_names($post_id);

    foreach (tsuruse_get_event_group_term_names($post_id) as $term_names) {
        if (!empty($term_names)) {
            $badge_names = array_merge($badge_names, $term_names);
        }
    }

    return array_values(array_unique($badge_names));
}

add_action('add_meta_boxes', 'tsuruse_register_event_meta_box');
function tsuruse_register_event_meta_box() {
    add_meta_box(
        'tsuruse-event-details',
        'イベント情報',
        'tsuruse_render_event_meta_box',
        TSURUSE_EVENT_POST_TYPE,
        'normal',
        'high'
    );
}

function tsuruse_render_event_meta_box($post) {
    wp_nonce_field('tsuruse_save_event_meta', 'tsuruse_event_meta_nonce');
    $fields = tsuruse_event_meta_fields();
    ?>
    <style>
        .tsuruse-event-meta-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 12px;
        }
        .tsuruse-event-meta-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .tsuruse-event-meta-field input {
            width: 100%;
        }
        .tsuruse-event-meta-field .description {
            display: block;
            margin-top: 6px;
        }
    </style>
    <p>イベント詳細ページに表示する補足情報を設定します。詳しい説明文は本文に入力してください。</p>
    <div class="tsuruse-event-meta-grid">
        <?php foreach ($fields as $meta_key => $field) : ?>
            <?php $value = get_post_meta($post->ID, $meta_key, true); ?>
            <div class="tsuruse-event-meta-field">
                <label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($field['label']); ?></label>
                <input
                    type="<?php echo esc_attr($field['type']); ?>"
                    id="<?php echo esc_attr($meta_key); ?>"
                    name="<?php echo esc_attr($meta_key); ?>"
                    value="<?php echo esc_attr($value); ?>"
                />
                <span class="description"><?php echo esc_html($field['description']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

add_action('save_post_' . TSURUSE_EVENT_POST_TYPE, 'tsuruse_save_event_meta');
function tsuruse_save_event_meta($post_id) {
    if (!isset($_POST['tsuruse_event_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tsuruse_event_meta_nonce'])), 'tsuruse_save_event_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (tsuruse_event_meta_fields() as $meta_key => $field) {
        $raw_value = isset($_POST[$meta_key]) ? wp_unslash($_POST[$meta_key]) : '';

        if ('url' === $field['type']) {
            $value = esc_url_raw($raw_value);
        } else {
            $value = sanitize_text_field($raw_value);
        }

        if ('' === $value) {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $value);
        }
    }
}

function tsuruse_get_event_details($post_id = null) {
    $post_id = $post_id ?: get_the_ID();

    return array(
        'deadline'        => get_post_meta($post_id, TSURUSE_EVENT_DEADLINE_META, true),
        'application_url' => get_post_meta($post_id, TSURUSE_EVENT_APPLICATION_URL_META, true),
    );
}

function tsuruse_event_format_date($date) {
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);
    if (false === $timestamp) {
        return '';
    }

    $weekday = array('日', '月', '火', '水', '木', '金', '土');

    return wp_date('Y.n.j', $timestamp) . '（' . $weekday[(int) wp_date('w', $timestamp)] . '）';
}

function tsuruse_is_recent_post($post = null, $days = 7) {
    $post = get_post($post);

    if (!$post instanceof WP_Post) {
        return false;
    }

    $days = max(1, (int) $days);
    $timezone = wp_timezone();
    $published_at = get_post_datetime($post, 'date', $timezone);

    if (!$published_at instanceof DateTimeInterface) {
        return false;
    }

    $today = new DateTimeImmutable('now', $timezone);
    $current_day = $today->setTime(0, 0, 0);
    $published_day = DateTimeImmutable::createFromInterface($published_at)->setTime(0, 0, 0);
    $day_diff = (int) $published_day->diff($current_day)->format('%r%a');

    return $day_diff >= 0 && $day_diff < $days;
}

function tsuruse_is_recent_post_within_months($post = null, $months = 1) {
    $post = get_post($post);

    if (!$post instanceof WP_Post) {
        return false;
    }

    $months = max(1, (int) $months);
    $timezone = wp_timezone();
    $published_at = get_post_datetime($post, 'date', $timezone);

    if (!$published_at instanceof DateTimeInterface) {
        return false;
    }

    $today = new DateTimeImmutable('now', $timezone);
    $current_day = $today->setTime(0, 0, 0);
    $threshold = $current_day->sub(new DateInterval('P' . $months . 'M'));
    $published_day = DateTimeImmutable::createFromInterface($published_at)->setTime(0, 0, 0);

    return $published_day >= $threshold && $published_day <= $current_day;
}

function tsuruse_get_whatsnew_posts($limit = 5) {
    $limit = max(1, (int) $limit);
    $posts = array();

    $sticky_ids = array_values(array_filter(array_map('absint', (array) get_option('sticky_posts'))));

    if (!empty($sticky_ids)) {
        $sticky_query = new WP_Query(
            array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => $limit,
                'post__in'            => $sticky_ids,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            )
        );

        if (!empty($sticky_query->posts)) {
            $posts = $sticky_query->posts;
        }
    }

    if (count($posts) < $limit) {
        $recent_query = new WP_Query(
            array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => $limit - count($posts),
                'post__not_in'        => wp_list_pluck($posts, 'ID'),
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            )
        );

        if (!empty($recent_query->posts)) {
            $posts = array_merge($posts, $recent_query->posts);
        }
    }

    return array_slice($posts, 0, $limit);
}

function tsuruse_render_whatsnew_shortcode($atts = array()) {
    $atts  = shortcode_atts(
        array(
            'posts_per_page' => 5,
        ),
        (array) $atts,
        'showwhatsnew'
    );
    $posts = tsuruse_get_whatsnew_posts((int) $atts['posts_per_page']);

    if (empty($posts)) {
        return '';
    }

    ob_start();
    ?>
    <div class="tsuruse-whatsnew-section">
        <div class="tsuruse-whatsnew-toolbar">
            <a class="tsuruse-whatsnew-more" href="<?php echo esc_url(tsuruse_post_archive_url()); ?>">一覧を表示</a>
        </div>

        <div class="tsuruse-whatsnew-list">
            <?php foreach ($posts as $news_post) : ?>
                <article <?php post_class('tsuruse-post-archive-list__item', $news_post->ID); ?>>
                    <a class="tsuruse-post-archive-list__link" href="<?php echo esc_url(get_permalink($news_post)); ?>">
                        <time class="tsuruse-post-archive-list__date" datetime="<?php echo esc_attr(get_the_date('c', $news_post)); ?>">
                            <?php echo esc_html(get_the_date('Y年n月j日', $news_post)); ?>
                        </time>
                        <span class="tsuruse-post-archive-list__title">
                            <span class="tsuruse-post-archive-list__title-text"><?php echo esc_html(get_the_title($news_post)); ?></span>
                            <?php if (tsuruse_is_recent_post_within_months($news_post, 1)) : ?>
                                <span class="vk_post_title_new tsuruse-post-archive-list__new" aria-label="新着">NEW!</span>
                            <?php endif; ?>
                        </span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('showwhatsnew', 'tsuruse_render_whatsnew_shortcode');

function tsuruse_event_get_deadline_label($event = null) {
    $event = is_array($event) ? $event : tsuruse_get_event_details();

    if (empty($event['deadline'])) {
        return '';
    }

    return tsuruse_event_format_date($event['deadline']);
}

add_action('pre_get_posts', 'tsuruse_event_archive_query');
function tsuruse_event_archive_query($query) {
    if (!tsuruse_is_event_list_query($query)) {
        return;
    }

    $query->set('posts_per_page', 12);
    $query->set('ignore_sticky_posts', true);
}

add_action('pre_get_posts', 'tsuruse_post_archive_query');
function tsuruse_post_archive_query($query) {
    if (!tsuruse_is_post_archive_query($query)) {
        return;
    }

    $query->set('post_type', 'post');
    $query->set('post_status', 'publish');
    $query->set('ignore_sticky_posts', true);
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');

    $query->is_home = false;
    $query->is_singular = false;
    $query->is_page = false;
    $query->is_404 = false;
    $query->is_archive = true;
}

add_action('pre_get_posts', 'tsuruse_home_post_list_query');
function tsuruse_home_post_list_query($query) {
    if (is_admin() || !$query instanceof WP_Query || !$query->is_main_query() || !$query->is_home()) {
        return;
    }

    $query->set('posts_per_page', 5);
    $query->set('no_found_rows', true);
    $query->set('ignore_sticky_posts', true);
}

add_action('template_redirect', 'tsuruse_redirect_paged_home');
function tsuruse_redirect_paged_home() {
    if (!is_home()) {
        return;
    }

    $paged = max((int) get_query_var('paged'), (int) get_query_var('page'));
    if ($paged < 2) {
        return;
    }

    wp_safe_redirect(home_url('/'), 302);
    exit;
}

add_action('lightning_loop_before', 'tsuruse_home_post_list_header', 5);
function tsuruse_home_post_list_header() {
    if (!is_home()) {
        return;
    }
    ?>
    <div class="tsuruse-home-post-list-header">
        <h3 class="tsuruse-home-post-list-heading">イベント情報</h3>
        <a class="tsuruse-home-post-list-more" href="<?php echo esc_url(tsuruse_post_archive_url()); ?>">
            一覧を見る
        </a>
    </div>
    <?php
}

function tsuruse_is_event_list_query($query) {
    $event_archive_taxonomies = tsuruse_picture_sidebar_taxonomies();

    return !is_admin()
        && $query instanceof WP_Query
        && $query->is_main_query()
        && (
            $query->is_post_type_archive(TSURUSE_PICTURES_POST_TYPE)
            || $query->is_tax($event_archive_taxonomies)
        );
}

add_filter('manage_' . TSURUSE_PICTURES_POST_TYPE . '_posts_columns', 'tsuruse_group_taxonomy_admin_columns');
function tsuruse_group_taxonomy_admin_columns($columns) {
    $new_columns = array();

    foreach ($columns as $key => $label) {
        if ('title' === $key) {
            $new_columns[$key] = $label;

            foreach (tsuruse_event_group_taxonomies() as $taxonomy => $config) {
                $new_columns['taxonomy_' . $taxonomy] = $config['label'];
            }

            continue;
        }

        $new_columns[$key] = $label;
    }

    return $new_columns;
}

add_action('manage_' . TSURUSE_PICTURES_POST_TYPE . '_posts_custom_column', 'tsuruse_group_taxonomy_admin_column_content', 10, 2);
function tsuruse_group_taxonomy_admin_column_content($column, $post_id) {
    if (0 === strpos($column, 'taxonomy_')) {
        $taxonomy = substr($column, 9);
        $terms    = get_the_terms($post_id, $taxonomy);

        if (empty($terms) || is_wp_error($terms)) {
            echo '&mdash;';
        } else {
            echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
        }

        return;
    }
}

add_action('init', 'tsuruse_event_maybe_flush_rewrite_rules', 30);
function tsuruse_event_maybe_flush_rewrite_rules() {
    $version = '20260419_event_cpt_v12';

    if (get_option('tsuruse_event_rewrite_version') === $version) {
        return;
    }

    tsuruse_register_post_archive_rewrite();
    tsuruse_register_pictures_post_type();
    tsuruse_register_event_taxonomy();
    tsuruse_register_picture_member_tag_taxonomy();
    flush_rewrite_rules(false);
    update_option('tsuruse_event_rewrite_version', $version);
}

add_action('admin_menu', 'tsuruse_register_event_taxonomy_submenus', 20);
function tsuruse_register_event_taxonomy_submenus() {
    foreach (tsuruse_event_group_taxonomies() as $taxonomy => $config) {
        add_submenu_page(
            'edit.php?post_type=' . TSURUSE_PICTURES_POST_TYPE,
            $config['label'],
            $config['label'],
            'manage_categories',
            'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=' . TSURUSE_PICTURES_POST_TYPE
        );
    }

    $config = tsuruse_picture_member_tag_taxonomy_config();
    add_submenu_page(
        'edit.php?post_type=' . TSURUSE_PICTURES_POST_TYPE,
        $config['label'],
        $config['label'],
        'manage_categories',
        'edit-tags.php?taxonomy=' . TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY . '&post_type=' . TSURUSE_PICTURES_POST_TYPE
    );
}

add_filter('lightning_page_header_title_html', 'tsuruse_event_taxonomy_page_header_title');
function tsuruse_event_taxonomy_page_header_title($html) {
    if (tsuruse_is_post_archive_query()) {
        return '<h1 class="page-header-title">イベント情報</h1>';
    }

    if (is_singular(TSURUSE_PICTURES_POST_TYPE)) {
        $badge_names = tsuruse_get_event_badge_names();
        $header_name = empty($badge_names) ? 'イベント情報' : (string) $badge_names[0];

        return '<div class="page-header-title">' . esc_html($header_name) . '</div>';
    }

    if (!is_tax(tsuruse_picture_sidebar_taxonomies())) {
        return $html;
    }

    $term = get_queried_object();
    if (!$term instanceof WP_Term) {
        return $html;
    }

    return '<h1 class="page-header-title">' . esc_html($term->name) . '</h1>';
}

add_filter('lightning_get_the_class_name_sub-section', 'tsuruse_picture_sidebar_class');
function tsuruse_picture_sidebar_class($class_name) {
    if (!tsuruse_is_picture_sidebar_context() && !tsuruse_is_post_sidebar_context()) {
        return $class_name;
    }

    return trim($class_name . ' sub-section--tsuruse-event-nav');
}

add_filter('lightning_is_layout_onecolumn', 'tsuruse_force_two_column_event_layout', 20);
function tsuruse_force_two_column_event_layout($onecolumn) {
    if (tsuruse_is_picture_sidebar_context() || tsuruse_is_post_sidebar_context()) {
        return false;
    }

    return $onecolumn;
}

add_filter('lightning_is_subsection', 'tsuruse_force_sidebar_subsection', 20);
function tsuruse_force_sidebar_subsection($display) {
    if (tsuruse_is_picture_sidebar_context() || tsuruse_is_post_sidebar_context()) {
        return true;
    }

    return $display;
}

add_filter('lightning_taxnomiesHtml', 'tsuruse_hide_single_post_category_meta');
function tsuruse_hide_single_post_category_meta($html) {
    if (is_singular(TSURUSE_PICTURES_POST_TYPE)) {
        return '';
    }

    if (!is_singular('post')) {
        return $html;
    }

    return (string) preg_replace(
        '/<div class="entry-meta-data-list entry-meta-data-list--category">.*?<\/div>/s',
        '',
        (string) $html
    );
}

add_action('wp_footer', 'tsuruse_reset_sidebar_groups_on_load', 30);
function tsuruse_reset_sidebar_groups_on_load() {
    if (!tsuruse_is_picture_sidebar_context() && !tsuruse_is_post_sidebar_context()) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tsuruse-event-nav-widget .tsuruse-event-sidebar-group').forEach(function (group) {
                group.open = group.classList.contains('has-current');
            });
        });
    </script>
    <?php
}

add_action('init', 'tsuruse_register_members_role', 20);
function tsuruse_register_members_role() {
    return;
}

add_action('init', 'tsuruse_register_account_application_capability', 20);
function tsuruse_register_account_application_capability() {
    return;
}

add_action('init', 'tsuruse_register_account_application_post_type', 21);
function tsuruse_register_account_application_post_type() {
    register_post_type(
        TSURUSE_ACCOUNT_APPLICATION_POST_TYPE,
        array(
            'labels' => array(
                'name'               => 'アカウント申請',
                'singular_name'      => 'アカウント申請',
                'menu_name'          => 'アカウント申請',
                'all_items'          => '申請一覧',
                'edit_item'          => '申請内容',
                'view_item'          => '申請内容',
                'search_items'       => '申請を検索',
                'not_found'          => '申請はまだありません。',
                'not_found_in_trash' => 'ゴミ箱に申請はありません。',
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'users.php',
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'menu_icon'           => 'dashicons-id',
            'supports'            => array('title'),
            'map_meta_cap'        => true,
            'capabilities'        => array(
                'edit_post'              => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'read_post'              => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'delete_post'            => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'edit_posts'             => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'edit_others_posts'      => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'publish_posts'          => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'read_private_posts'     => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'delete_posts'           => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'delete_private_posts'   => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'delete_published_posts' => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'delete_others_posts'    => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'edit_private_posts'     => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'edit_published_posts'   => TSURUSE_ACCOUNT_APPLICATION_CAPABILITY,
                'create_posts'           => 'do_not_allow',
            ),
        )
    );
}

add_action('init', 'tsuruse_register_photo_provision_post_type', 22);
function tsuruse_register_photo_provision_post_type() {
    register_post_type(
        TSURUSE_PHOTO_PROVISION_POST_TYPE,
        array(
            'labels' => array(
                'name'               => '写真提供申請',
                'singular_name'      => '写真提供申請',
                'menu_name'          => '写真提供申請',
                'all_items'          => '申請一覧',
                'edit_item'          => '申請内容',
                'view_item'          => '申請内容',
                'search_items'       => '申請を検索',
                'not_found'          => '申請はまだありません。',
                'not_found_in_trash' => 'ゴミ箱に申請はありません。',
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=' . TSURUSE_PICTURES_POST_TYPE,
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'menu_icon'           => 'dashicons-share-alt2',
            'supports'            => array('title'),
            'map_meta_cap'        => true,
            'capabilities'        => array(
                'edit_post'              => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'read_post'              => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'delete_post'            => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'edit_posts'             => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'edit_others_posts'      => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'publish_posts'          => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'read_private_posts'     => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'delete_posts'           => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'delete_private_posts'   => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'delete_published_posts' => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'delete_others_posts'    => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'edit_private_posts'     => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'edit_published_posts'   => TSURUSE_PHOTO_PROVISION_CAPABILITY,
                'create_posts'           => 'do_not_allow',
            ),
        )
    );
}

add_action('wpcf7_mail_sent', 'tsuruse_capture_account_application_submission');
function tsuruse_capture_account_application_submission($contact_form) {
    if (!$contact_form instanceof WPCF7_ContactForm || !tsuruse_is_account_application_form($contact_form)) {
        return;
    }

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }

    $posted_data = (array) $submission->get_posted_data();
    $payload     = tsuruse_build_account_application_payload($posted_data, $submission, $contact_form);

    if (is_wp_error($payload)) {
        return;
    }

    tsuruse_save_account_application($payload);
}

add_action('wpcf7_mail_sent', 'tsuruse_capture_photo_provision_submission');
function tsuruse_capture_photo_provision_submission($contact_form) {
    if (!$contact_form instanceof WPCF7_ContactForm || !tsuruse_is_photo_provision_form($contact_form)) {
        return;
    }

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }

    $posted_data = (array) $submission->get_posted_data();
    $payload     = tsuruse_build_photo_provision_payload($posted_data, $submission, $contact_form);

    if (is_wp_error($payload)) {
        return;
    }

    tsuruse_save_photo_provision_application($payload);
}

function tsuruse_is_account_application_form($contact_form) {
    $form_id = (int) $contact_form->id();
    $title   = trim((string) $contact_form->title());

    return TSURUSE_ACCOUNT_APPLICATION_FORM_ID === $form_id || '新規申込' === $title;
}

function tsuruse_is_photo_provision_form($contact_form) {
    $title = trim((string) $contact_form->title());

    return in_array($title, array('写真提供', '写真提供申請'), true);
}

function tsuruse_submission_field_value($value) {
    if (is_array($value)) {
        foreach ($value as $item) {
            $normalized = tsuruse_submission_field_value($item);
            if ('' !== $normalized) {
                return $normalized;
            }
        }

        return '';
    }

    if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
        return trim((string) $value);
    }

    return '';
}

function tsuruse_sanitize_application_name_part($value) {
    return sanitize_text_field(tsuruse_submission_field_value($value));
}

function tsuruse_sanitize_application_romaji($value) {
    $value = wp_strip_all_tags(tsuruse_submission_field_value($value));
    $value = preg_replace('/[^A-Za-z0-9\'\-\s]/u', '', $value);
    $value = preg_replace('/\s+/', ' ', (string) $value);

    return trim((string) $value);
}

function tsuruse_build_application_name($sei = '', $mei = '', $fallback = '') {
    $sei      = trim((string) $sei);
    $mei      = trim((string) $mei);
    $fallback = trim((string) $fallback);

    if ('' !== $sei || '' !== $mei) {
        return trim($sei . ' ' . $mei);
    }

    return $fallback;
}

function tsuruse_build_account_application_payload(array $posted_data, WPCF7_Submission $submission, WPCF7_ContactForm $contact_form) {
    $name_sei             = tsuruse_sanitize_application_name_part($posted_data['name_sei'] ?? '');
    $name_mei             = tsuruse_sanitize_application_name_part($posted_data['name_mei'] ?? '');
    $name_sei_romaji      = tsuruse_sanitize_application_romaji($posted_data['name_sei_romaji'] ?? '');
    $name_mei_romaji      = tsuruse_sanitize_application_romaji($posted_data['name_mei_romaji'] ?? '');
    $legacy_name          = sanitize_text_field(tsuruse_submission_field_value($posted_data['name01'] ?? ''));
    $name                 = tsuruse_build_application_name($name_sei, $name_mei, $legacy_name);
    $email                = sanitize_email(tsuruse_submission_field_value($posted_data['email'] ?? ''));
    $generation           = sanitize_text_field(tsuruse_submission_field_value($posted_data['number'] ?? ''));
    $selected_affiliation = sanitize_text_field(tsuruse_submission_field_value($posted_data['doin'] ?? ''));
    $other_affiliation    = sanitize_text_field(tsuruse_submission_field_value($posted_data['other'] ?? ''));
    $message              = sanitize_textarea_field(tsuruse_submission_field_value($posted_data['your-message'] ?? ''));
    $uses_split_name      = isset($posted_data['name_sei']) || isset($posted_data['name_mei']) || isset($posted_data['name_sei_romaji']) || isset($posted_data['name_mei_romaji']);

    if ($uses_split_name && ('' === $name_sei || '' === $name_mei || '' === $name_sei_romaji || '' === $name_mei_romaji)) {
        return new WP_Error('invalid_application', '氏名またはローマ字の必須項目が不足しています。');
    }

    if ('' === $name || '' === $email || '' === $selected_affiliation || !is_email($email)) {
        return new WP_Error('invalid_application', '申請フォームの必須項目が不足しています。');
    }

    $affiliation = tsuruse_resolve_application_affiliation_label($selected_affiliation, $other_affiliation);

    return array(
        'title'                 => sprintf('%s / %s', $name, $affiliation),
        'name'                  => $name,
        'name_sei'              => $name_sei,
        'name_mei'              => $name_mei,
        'name_sei_romaji'       => $name_sei_romaji,
        'name_mei_romaji'       => $name_mei_romaji,
        'email'                 => $email,
        'generation'            => $generation,
        'selected_affiliation'  => $selected_affiliation,
        'affiliation'           => $affiliation,
        'other_affiliation'     => $other_affiliation,
        'message'               => $message,
        'form_id'               => (int) $contact_form->id(),
        'submitted_url'         => esc_url_raw((string) $submission->get_meta('url')),
        'remote_ip'             => sanitize_text_field((string) $submission->get_meta('remote_ip')),
        'user_agent'            => sanitize_text_field((string) $submission->get_meta('user_agent')),
    );
}

function tsuruse_resolve_application_affiliation_label($selected_affiliation, $other_affiliation = '') {
    if ('その他支部' === $selected_affiliation && '' !== trim((string) $other_affiliation)) {
        return trim((string) $other_affiliation);
    }

    return trim((string) $selected_affiliation);
}

function tsuruse_build_photo_provision_payload(array $posted_data, WPCF7_Submission $submission, WPCF7_ContactForm $contact_form) {
    $provider_name = sanitize_text_field(tsuruse_submission_field_value($posted_data['your-name'] ?? ''));
    $subject       = sanitize_text_field(tsuruse_submission_field_value($posted_data['your-subject'] ?? ''));
    $email         = sanitize_email(tsuruse_submission_field_value($posted_data['your-email'] ?? ''));
    $file_url      = esc_url_raw(tsuruse_submission_field_value($posted_data['file-url'] ?? ''));
    $message       = sanitize_textarea_field(tsuruse_submission_field_value($posted_data['your-message'] ?? ''));
    $event_id      = absint(tsuruse_submission_field_value($posted_data['related-event'] ?? ''));
    $gallery_id    = absint(tsuruse_submission_field_value($posted_data['related-gallery'] ?? ''));

    if ('' === $provider_name || '' === $subject || '' === $email || '' === $file_url || !is_email($email)) {
        return new WP_Error('invalid_photo_provision', '写真提供申請フォームの必須項目が不足しています。');
    }

    return array(
        'title'         => sprintf('%s / %s', $subject, $provider_name),
        'provider_name' => $provider_name,
        'subject'       => $subject,
        'email'         => $email,
        'file_url'      => $file_url,
        'message'       => $message,
        'event_id'      => $event_id,
        'gallery_id'    => $gallery_id,
        'form_id'       => (int) $contact_form->id(),
        'submitted_url' => esc_url_raw((string) $submission->get_meta('url')),
        'remote_ip'     => sanitize_text_field((string) $submission->get_meta('remote_ip')),
        'user_agent'    => sanitize_text_field((string) $submission->get_meta('user_agent')),
    );
}

function tsuruse_default_application_affiliation() {
    return '埼玉鶴瀬道院';
}

function tsuruse_repair_broken_application_affiliations($force = false) {
    $option_name = '_tsuruse_application_affiliation_repair_v1';
    if (!$force && '1' === (string) get_option($option_name, '')) {
        return;
    }

    $application_ids = get_posts(
        array(
            'post_type'      => TSURUSE_ACCOUNT_APPLICATION_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_tsuruse_application_selected_affiliation',
                    'value' => 'Array',
                ),
                array(
                    'key'   => '_tsuruse_application_affiliation',
                    'value' => 'Array',
                ),
            ),
        )
    );

    foreach ($application_ids as $application_id) {
        $application_id       = (int) $application_id;
        $current_selected     = trim((string) get_post_meta($application_id, '_tsuruse_application_selected_affiliation', true));
        $current_affiliation  = trim((string) get_post_meta($application_id, '_tsuruse_application_affiliation', true));
        $other_affiliation    = trim((string) get_post_meta($application_id, '_tsuruse_application_other_affiliation', true));
        $name                 = trim((string) get_post_meta($application_id, '_tsuruse_application_name', true));
        $selected_affiliation = $current_selected;
        $affiliation          = $current_affiliation;

        if ('Array' === $selected_affiliation || '' === $selected_affiliation) {
            $selected_affiliation = '' !== $other_affiliation ? 'その他支部' : tsuruse_default_application_affiliation();
        }

        if ('Array' === $affiliation || '' === $affiliation) {
            $affiliation = tsuruse_resolve_application_affiliation_label($selected_affiliation, $other_affiliation);
        }

        update_post_meta($application_id, '_tsuruse_application_selected_affiliation', $selected_affiliation);
        update_post_meta($application_id, '_tsuruse_application_affiliation', $affiliation);

        if ('' !== $name && '' !== $affiliation) {
            wp_update_post(
                array(
                    'ID'         => $application_id,
                    'post_title' => sprintf('%s / %s', $name, $affiliation),
                )
            );
        }

        $user_id = (int) get_post_meta($application_id, '_tsuruse_application_user_id', true);
        if ($user_id > 0) {
            update_user_meta($user_id, 'affiliation', $affiliation);
            update_user_meta($user_id, '_tsuruse_selected_affiliation', $selected_affiliation);
            tsuruse_sync_member_affiliation_roles($user_id, $selected_affiliation);
        }
    }

    update_option($option_name, '1', false);
}

function tsuruse_save_account_application(array $payload) {
    $existing_id = tsuruse_find_open_account_application_by_email((string) $payload['email']);
    $postarr     = array(
        'post_type'   => TSURUSE_ACCOUNT_APPLICATION_POST_TYPE,
        'post_status' => 'publish',
        'post_title'  => $payload['title'],
    );

    if ($existing_id > 0) {
        $postarr['ID'] = $existing_id;
    }

    $application_id = wp_insert_post(wp_slash($postarr), true, false);
    if (is_wp_error($application_id)) {
        return $application_id;
    }

    $application_id = (int) $application_id;
    $existing_user  = get_user_by('email', (string) $payload['email']);
    $attempts       = (int) get_post_meta($application_id, '_tsuruse_application_attempts', true);

    update_post_meta($application_id, '_tsuruse_application_status', 'pending');
    update_post_meta($application_id, '_tsuruse_application_name', $payload['name']);
    update_post_meta($application_id, '_tsuruse_application_name_sei', $payload['name_sei'] ?? '');
    update_post_meta($application_id, '_tsuruse_application_name_mei', $payload['name_mei'] ?? '');
    update_post_meta($application_id, '_tsuruse_application_name_sei_romaji', $payload['name_sei_romaji'] ?? '');
    update_post_meta($application_id, '_tsuruse_application_name_mei_romaji', $payload['name_mei_romaji'] ?? '');
    update_post_meta($application_id, '_tsuruse_application_email', $payload['email']);
    update_post_meta($application_id, '_tsuruse_application_generation', $payload['generation']);
    update_post_meta($application_id, '_tsuruse_application_selected_affiliation', $payload['selected_affiliation']);
    update_post_meta($application_id, '_tsuruse_application_affiliation', $payload['affiliation']);
    update_post_meta($application_id, '_tsuruse_application_other_affiliation', $payload['other_affiliation']);
    update_post_meta($application_id, '_tsuruse_application_message', $payload['message']);
    update_post_meta($application_id, '_tsuruse_application_form_id', (int) $payload['form_id']);
    update_post_meta($application_id, '_tsuruse_application_submitted_url', $payload['submitted_url']);
    update_post_meta($application_id, '_tsuruse_application_remote_ip', $payload['remote_ip']);
    update_post_meta($application_id, '_tsuruse_application_user_agent', $payload['user_agent']);
    update_post_meta($application_id, '_tsuruse_application_submitted_at', current_time('mysql'));
    update_post_meta($application_id, '_tsuruse_application_attempts', $attempts + 1);

    if ($existing_user instanceof WP_User) {
        update_post_meta($application_id, '_tsuruse_application_existing_user_id', (int) $existing_user->ID);
    } else {
        delete_post_meta($application_id, '_tsuruse_application_existing_user_id');
    }

    return $application_id;
}

function tsuruse_save_photo_provision_application(array $payload) {
    $application_id = wp_insert_post(
        wp_slash(
            array(
                'post_type'   => TSURUSE_PHOTO_PROVISION_POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => $payload['title'],
            )
        ),
        true,
        false
    );

    if (is_wp_error($application_id)) {
        return $application_id;
    }

    $application_id = (int) $application_id;

    update_post_meta($application_id, '_tsuruse_photo_status', 'received');
    update_post_meta($application_id, '_tsuruse_photo_provider_name', $payload['provider_name']);
    update_post_meta($application_id, '_tsuruse_photo_subject', $payload['subject']);
    update_post_meta($application_id, '_tsuruse_photo_email', $payload['email']);
    update_post_meta($application_id, '_tsuruse_photo_url', $payload['file_url']);
    update_post_meta($application_id, '_tsuruse_photo_message', $payload['message']);
    update_post_meta($application_id, '_tsuruse_photo_event_id', (int) $payload['event_id']);
    update_post_meta($application_id, '_tsuruse_photo_gallery_id', (int) $payload['gallery_id']);
    update_post_meta($application_id, '_tsuruse_photo_form_id', (int) $payload['form_id']);
    update_post_meta($application_id, '_tsuruse_photo_submitted_url', $payload['submitted_url']);
    update_post_meta($application_id, '_tsuruse_photo_remote_ip', $payload['remote_ip']);
    update_post_meta($application_id, '_tsuruse_photo_user_agent', $payload['user_agent']);
    update_post_meta($application_id, '_tsuruse_photo_submitted_at', current_time('mysql'));

    return $application_id;
}

function tsuruse_find_open_account_application_by_email($email) {
    $posts = get_posts(
        array(
            'post_type'      => TSURUSE_ACCOUNT_APPLICATION_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_tsuruse_application_email',
                    'value' => $email,
                ),
                array(
                    'key'     => '_tsuruse_application_status',
                    'value'   => array('approved', 'mail_failed', 'rejected', 'reject_mail_failed'),
                    'compare' => 'NOT IN',
                ),
            ),
        )
    );

    return empty($posts[0]) ? 0 : (int) $posts[0];
}

function tsuruse_user_has_gallery_access($user_id) {
    $user = get_userdata((int) $user_id);
    if (!$user instanceof WP_User) {
        return false;
    }

    if (tsuruse_is_member_account_disabled((int) $user_id)) {
        return false;
    }

    $managed_roles = tsuruse_get_managed_member_role_slugs();

    if (!empty(array_intersect($managed_roles, (array) $user->roles))) {
        return true;
    }

    if ('1' === (string) get_user_meta($user_id, '_tsuruse_account_approved', true)) {
        return true;
    }

    $affiliation   = (string) get_user_meta($user_id, 'affiliation', true);
    if ('' !== $affiliation && array_intersect($managed_roles, (array) $user->roles)) {
        return true;
    }

    if (function_exists('uam_get_user_groups')) {
        $groups = uam_get_user_groups((int) $user_id);
        if (!empty($groups)) {
            return true;
        }
    }

    return false;
}

function tsuruse_is_managed_member_user($user_id) {
    $user = get_userdata((int) $user_id);
    if (!$user instanceof WP_User) {
        return false;
    }

    if (!empty(array_intersect(tsuruse_get_managed_member_role_slugs(), (array) $user->roles))) {
        return true;
    }

    if ('1' === (string) get_user_meta($user_id, '_tsuruse_account_approved', true)) {
        return true;
    }

    if ('' !== trim((string) get_user_meta($user_id, '_tsuruse_selected_affiliation', true))) {
        return true;
    }

    return '' !== trim((string) get_user_meta($user_id, 'affiliation', true));
}

function tsuruse_is_member_account_disabled($user_id) {
    return '1' === (string) get_user_meta((int) $user_id, '_tsuruse_account_disabled', true);
}

function tsuruse_member_account_lifecycle_reference_ts($user_id) {
    $last_login_ts = (int) get_user_meta((int) $user_id, '_tsuruse_last_login_ts', true);
    if ($last_login_ts > 0) {
        return $last_login_ts;
    }

    $approved_at = (string) get_user_meta((int) $user_id, '_tsuruse_account_approved_at', true);
    if ('' !== $approved_at) {
        $approved_ts = strtotime($approved_at);
        if ($approved_ts > 0) {
            return $approved_ts;
        }
    }

    $user = get_userdata((int) $user_id);
    if ($user instanceof WP_User && !empty($user->user_registered)) {
        $registered_ts = strtotime((string) $user->user_registered);
        if ($registered_ts > 0) {
            return $registered_ts;
        }
    }

    return 0;
}

function tsuruse_account_lifecycle_threshold_ts($months) {
    return strtotime('-' . absint($months) . ' months', current_time('timestamp'));
}

add_action('init', 'tsuruse_schedule_account_lifecycle_check');
function tsuruse_schedule_account_lifecycle_check() {
    if (!wp_next_scheduled('tsuruse_account_lifecycle_daily')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'tsuruse_account_lifecycle_daily');
    }
}

add_action('tsuruse_account_lifecycle_daily', 'tsuruse_run_account_lifecycle_check');
function tsuruse_run_account_lifecycle_check() {
    $user_ids = get_users(
        array(
            'fields'      => 'ids',
            'number'      => -1,
            'count_total' => false,
            'meta_query'  => array(
                array(
                    'key'   => '_tsuruse_account_approved',
                    'value' => '1',
                ),
            ),
        )
    );

    $disabled_threshold_ts = tsuruse_account_lifecycle_threshold_ts(TSURUSE_ACCOUNT_INACTIVITY_MONTHS);
    $delete_threshold_ts   = tsuruse_account_lifecycle_threshold_ts(TSURUSE_ACCOUNT_DELETE_CANDIDATE_MONTHS);

    foreach ($user_ids as $user_id) {
        $user_id = (int) $user_id;
        if ($user_id < 1 || !tsuruse_is_managed_member_user($user_id) || user_can($user_id, 'administrator')) {
            continue;
        }

        $reference_ts = tsuruse_member_account_lifecycle_reference_ts($user_id);
        if ($reference_ts < 1) {
            continue;
        }

        if (!tsuruse_is_member_account_disabled($user_id) && $reference_ts <= $disabled_threshold_ts) {
            tsuruse_disable_member_account($user_id);
            continue;
        }

        if (tsuruse_is_member_account_disabled($user_id)) {
            $disabled_at = (string) get_user_meta($user_id, '_tsuruse_account_disabled_at', true);
            $disabled_ts = '' !== $disabled_at ? strtotime($disabled_at) : 0;

            if ($disabled_ts > 0 && $disabled_ts <= $delete_threshold_ts) {
                tsuruse_mark_member_account_delete_candidate($user_id);
            }
        }
    }
}

function tsuruse_disable_member_account($user_id) {
    $timestamp = current_time('timestamp');
    update_user_meta((int) $user_id, '_tsuruse_account_disabled', '1');
    update_user_meta((int) $user_id, '_tsuruse_account_disabled_at', date_i18n('Y-m-d H:i:s', $timestamp));
    delete_user_meta((int) $user_id, '_tsuruse_account_delete_candidate');
    delete_user_meta((int) $user_id, '_tsuruse_account_delete_candidate_at');
}

function tsuruse_enable_member_account($user_id) {
    $timestamp = current_time('timestamp');
    delete_user_meta((int) $user_id, '_tsuruse_account_disabled');
    delete_user_meta((int) $user_id, '_tsuruse_account_disabled_at');
    delete_user_meta((int) $user_id, '_tsuruse_account_delete_candidate');
    delete_user_meta((int) $user_id, '_tsuruse_account_delete_candidate_at');
    update_user_meta((int) $user_id, '_tsuruse_last_login_at', date_i18n('Y-m-d H:i:s', $timestamp));
    update_user_meta((int) $user_id, '_tsuruse_last_login_ts', $timestamp);
}

function tsuruse_mark_member_account_delete_candidate($user_id) {
    if ('1' === (string) get_user_meta((int) $user_id, '_tsuruse_account_delete_candidate', true)) {
        return;
    }

    update_user_meta((int) $user_id, '_tsuruse_account_delete_candidate', '1');
    update_user_meta((int) $user_id, '_tsuruse_account_delete_candidate_at', current_time('mysql'));
}

function tsuruse_member_affiliation_role_map() {
    return array(
        '埼玉鶴瀬道院'     => 'author',
        '志木スポーツ少年団' => 'author',
        '和光国際高校'     => 'contributor',
        'その他支部'       => 'subscriber',
    );
}

function tsuruse_get_managed_member_role_slugs() {
    return array_values(array_unique(tsuruse_member_affiliation_role_map()));
}

function tsuruse_resolve_member_role_for_affiliation($selected_affiliation = '', $affiliation = '') {
    $selected_affiliation = trim((string) $selected_affiliation);
    $affiliation          = trim((string) $affiliation);
    $map                  = tsuruse_member_affiliation_role_map();

    if (isset($map[$selected_affiliation])) {
        return $map[$selected_affiliation];
    }

    if (isset($map[$affiliation])) {
        return $map[$affiliation];
    }

    if ('' !== $selected_affiliation || '' !== $affiliation) {
        return $map['その他支部'];
    }

    return '';
}

function tsuruse_sync_member_affiliation_roles($user_id, $selected_affiliation = '') {
    $user = get_userdata((int) $user_id);
    if (!$user instanceof WP_User || user_can($user_id, 'administrator')) {
        return;
    }

    if ('' === $selected_affiliation) {
        $selected_affiliation = (string) get_user_meta($user_id, '_tsuruse_selected_affiliation', true);
    }

    if ('' === $selected_affiliation) {
        $selected_affiliation = (string) get_user_meta($user_id, 'affiliation', true);
    }

    $managed_roles = tsuruse_get_managed_member_role_slugs();

    foreach ($managed_roles as $role_slug) {
        $user->remove_role($role_slug);
    }

    $user->remove_role(TSURUSE_LEGACY_MEMBER_ROLE);

    $member_role = tsuruse_resolve_member_role_for_affiliation(
        $selected_affiliation,
        (string) get_user_meta($user_id, 'affiliation', true)
    );

    if ('' !== $member_role) {
        $user->add_role($member_role);
    }
}

add_action('init', 'tsuruse_maybe_repair_application_affiliations', 40);
function tsuruse_maybe_repair_application_affiliations() {
    tsuruse_repair_broken_application_affiliations();
}

add_action('init', 'tsuruse_maybe_migrate_member_roles', 30);
function tsuruse_maybe_migrate_member_roles() {
    if ('1' === (string) get_option('_tsuruse_member_role_migration_v2', '')) {
        return;
    }

    $user_ids = get_users(
        array(
            'fields'      => 'ids',
            'number'      => -1,
            'role'        => TSURUSE_LEGACY_MEMBER_ROLE,
            'count_total' => false,
        )
    );

    $meta_users = get_users(
        array(
            'fields'      => 'ids',
            'number'      => -1,
            'count_total' => false,
            'meta_query'  => array(
                'relation' => 'OR',
                array(
                    'key'   => '_tsuruse_account_approved',
                    'value' => '1',
                ),
                array(
                    'key'     => '_tsuruse_selected_affiliation',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => 'affiliation',
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );

    $user_ids = array_unique(
        array_map(
            'intval',
            array_merge((array) $user_ids, (array) $meta_users)
        )
    );

    foreach ($user_ids as $user_id) {
        if ($user_id > 0) {
            tsuruse_sync_member_affiliation_roles($user_id);
        }
    }

    update_option('_tsuruse_member_role_migration_v2', '1', false);
}

add_action('add_meta_boxes', 'tsuruse_register_account_application_metabox');
function tsuruse_register_account_application_metabox() {
    add_meta_box(
        'tsuruse-account-application',
        '申請内容',
        'tsuruse_render_account_application_metabox',
        TSURUSE_ACCOUNT_APPLICATION_POST_TYPE,
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'tsuruse_register_photo_provision_metabox');
function tsuruse_register_photo_provision_metabox() {
    add_meta_box(
        'tsuruse-photo-provision',
        '写真提供申請内容',
        'tsuruse_render_photo_provision_metabox',
        TSURUSE_PHOTO_PROVISION_POST_TYPE,
        'normal',
        'high'
    );
}

function tsuruse_render_account_application_metabox($post) {
    $status           = (string) get_post_meta($post->ID, '_tsuruse_application_status', true);
    $name             = (string) get_post_meta($post->ID, '_tsuruse_application_name', true);
    $name_sei         = (string) get_post_meta($post->ID, '_tsuruse_application_name_sei', true);
    $name_mei         = (string) get_post_meta($post->ID, '_tsuruse_application_name_mei', true);
    $name_sei_romaji  = (string) get_post_meta($post->ID, '_tsuruse_application_name_sei_romaji', true);
    $name_mei_romaji  = (string) get_post_meta($post->ID, '_tsuruse_application_name_mei_romaji', true);
    $email            = (string) get_post_meta($post->ID, '_tsuruse_application_email', true);
    $generation       = (string) get_post_meta($post->ID, '_tsuruse_application_generation', true);
    $affiliation      = (string) get_post_meta($post->ID, '_tsuruse_application_affiliation', true);
    $selected         = (string) get_post_meta($post->ID, '_tsuruse_application_selected_affiliation', true);
    $message          = (string) get_post_meta($post->ID, '_tsuruse_application_message', true);
    $submitted_at     = (string) get_post_meta($post->ID, '_tsuruse_application_submitted_at', true);
    $submitted_url    = (string) get_post_meta($post->ID, '_tsuruse_application_submitted_url', true);
    $existing_user_id = (int) get_post_meta($post->ID, '_tsuruse_application_existing_user_id', true);
    $user_id          = (int) get_post_meta($post->ID, '_tsuruse_application_user_id', true);
    $approved_at      = (string) get_post_meta($post->ID, '_tsuruse_application_approved_at', true);
    $username         = (string) get_post_meta($post->ID, '_tsuruse_application_username', true);
    $last_error       = (string) get_post_meta($post->ID, '_tsuruse_application_last_error', true);
    $rejected_at      = (string) get_post_meta($post->ID, '_tsuruse_application_rejected_at', true);
    $rejected_by      = (int) get_post_meta($post->ID, '_tsuruse_application_rejected_by', true);
    $rejection_reason = (string) get_post_meta($post->ID, '_tsuruse_application_rejection_reason', true);
    $approve_url      = wp_nonce_url(
        admin_url('admin-post.php?action=tsuruse_approve_account_application&application_id=' . (int) $post->ID),
        'tsuruse_approve_account_application_' . (int) $post->ID
    );
    ?>
    <table class="widefat striped" style="margin-bottom:16px;">
        <tbody>
            <tr><th style="width:180px;">状態</th><td><?php echo esc_html(tsuruse_account_application_status_label($status)); ?></td></tr>
            <tr><th>氏名</th><td><?php echo esc_html($name); ?></td></tr>
            <tr><th>姓 / 名</th><td><?php echo esc_html(trim($name_sei . ' / ' . $name_mei, ' /')); ?></td></tr>
            <tr><th>ローマ字</th><td><?php echo esc_html(trim($name_sei_romaji . ' / ' . $name_mei_romaji, ' /')); ?></td></tr>
            <tr><th>メールアドレス</th><td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td></tr>
            <tr><th>期生</th><td><?php echo '' !== $generation ? esc_html($generation . '期') : '&mdash;'; ?></td></tr>
            <tr><th>道院(支部)名</th><td><?php echo esc_html($affiliation); ?></td></tr>
            <tr><th>選択値</th><td><?php echo esc_html($selected); ?></td></tr>
            <tr><th>申請日時</th><td><?php echo esc_html($submitted_at); ?></td></tr>
            <tr><th>申請ページ</th><td><?php echo '' !== $submitted_url ? '<a href="' . esc_url($submitted_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($submitted_url) . '</a>' : '&mdash;'; ?></td></tr>
            <tr><th>メッセージ</th><td><?php echo '' !== $message ? nl2br(esc_html($message)) : '&mdash;'; ?></td></tr>
            <tr><th>作成済みユーザー</th><td>
                <?php if ($user_id > 0) : ?>
                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user_id)); ?>">ユーザー #<?php echo esc_html((string) $user_id); ?></a>
                    <?php if ('' !== $username) : ?>
                        <span style="margin-left:8px;">(<?php echo esc_html($username); ?>)</span>
                    <?php endif; ?>
                <?php else : ?>
                    &mdash;
                <?php endif; ?>
            </td></tr>
            <tr><th>承認日時</th><td><?php echo '' !== $approved_at ? esc_html($approved_at) : '&mdash;'; ?></td></tr>
            <tr><th>差戻し日時</th><td><?php echo '' !== $rejected_at ? esc_html($rejected_at) : '&mdash;'; ?></td></tr>
            <tr><th>差戻し担当者</th><td><?php echo $rejected_by > 0 ? esc_html(get_the_author_meta('display_name', $rejected_by)) : '&mdash;'; ?></td></tr>
            <tr><th>差戻し理由</th><td><?php echo '' !== $rejection_reason ? nl2br(esc_html($rejection_reason)) : '&mdash;'; ?></td></tr>
            <tr><th>重複ユーザー警告</th><td>
                <?php if ($existing_user_id > 0 && $user_id < 1) : ?>
                    既存ユーザー <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $existing_user_id)); ?>">#<?php echo esc_html((string) $existing_user_id); ?></a> が見つかっています。
                <?php else : ?>
                    &mdash;
                <?php endif; ?>
            </td></tr>
            <tr><th>最終エラー</th><td><?php echo '' !== $last_error ? esc_html($last_error) : '&mdash;'; ?></td></tr>
        </tbody>
    </table>
    <?php if ('approved' !== $status && $user_id < 1) : ?>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($approve_url); ?>">承認してアカウント作成</a>
        </p>
        <div id="tsuruse-account-reject" style="border-top:1px solid #dcdcde;margin-top:16px;padding-top:16px;">
            <h3>差戻し</h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('tsuruse_reject_account_application_' . (int) $post->ID); ?>
                <input type="hidden" name="action" value="tsuruse_reject_account_application">
                <input type="hidden" name="application_id" value="<?php echo esc_attr((string) $post->ID); ?>">
                <p>
                    <label for="tsuruse-remand-reason">差戻し理由</label><br>
                    <textarea id="tsuruse-remand-reason" name="remand" rows="5" class="large-text" required><?php echo esc_textarea($rejection_reason); ?></textarea>
                </p>
                <p><button type="submit" class="button">理由を送信して差戻し</button></p>
            </form>
        </div>
    <?php elseif ($user_id > 0) : ?>
        <p>この申請は承認済みです。ログイン情報は承認時にメール送信されています。</p>
    <?php endif;
}

function tsuruse_render_photo_provision_metabox($post) {
    $status        = (string) get_post_meta($post->ID, '_tsuruse_photo_status', true);
    $provider_name = (string) get_post_meta($post->ID, '_tsuruse_photo_provider_name', true);
    $subject       = (string) get_post_meta($post->ID, '_tsuruse_photo_subject', true);
    $email         = (string) get_post_meta($post->ID, '_tsuruse_photo_email', true);
    $file_url      = (string) get_post_meta($post->ID, '_tsuruse_photo_url', true);
    $message       = (string) get_post_meta($post->ID, '_tsuruse_photo_message', true);
    $submitted_at  = (string) get_post_meta($post->ID, '_tsuruse_photo_submitted_at', true);
    $checked_at    = (string) get_post_meta($post->ID, '_tsuruse_photo_checked_at', true);
    $checked_by    = (int) get_post_meta($post->ID, '_tsuruse_photo_checked_by', true);
    $published_at  = (string) get_post_meta($post->ID, '_tsuruse_photo_published_at', true);
    $event_id      = (int) get_post_meta($post->ID, '_tsuruse_photo_event_id', true);
    $gallery_id    = (int) get_post_meta($post->ID, '_tsuruse_photo_gallery_id', true);
    $submitted_url = (string) get_post_meta($post->ID, '_tsuruse_photo_submitted_url', true);
    $check_url     = wp_nonce_url(
        admin_url('admin-post.php?action=tsuruse_mark_photo_provision_checked&application_id=' . (int) $post->ID),
        'tsuruse_mark_photo_provision_checked_' . (int) $post->ID
    );
    $publish_url   = wp_nonce_url(
        admin_url('admin-post.php?action=tsuruse_mark_photo_provision_published&application_id=' . (int) $post->ID),
        'tsuruse_mark_photo_provision_published_' . (int) $post->ID
    );
    ?>
    <table class="widefat striped" style="margin-bottom:16px;">
        <tbody>
            <tr><th style="width:180px;">申請状態</th><td><?php echo esc_html(tsuruse_photo_provision_status_label($status)); ?></td></tr>
            <tr><th>提供者名</th><td><?php echo esc_html($provider_name); ?></td></tr>
            <tr><th>提供名</th><td><?php echo esc_html($subject); ?></td></tr>
            <tr><th>提供者メールアドレス</th><td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td></tr>
            <tr><th>提供URL</th><td><?php echo '' !== $file_url ? '<a href="' . esc_url($file_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($file_url) . '</a>' : '&mdash;'; ?></td></tr>
            <tr><th>メッセージ本文</th><td><?php echo '' !== $message ? nl2br(esc_html($message)) : '&mdash;'; ?></td></tr>
            <tr><th>関連イベント</th><td><?php echo $event_id > 0 ? '<a href="' . esc_url(get_edit_post_link($event_id)) . '">#' . esc_html((string) $event_id) . '</a>' : '&mdash;'; ?></td></tr>
            <tr><th>関連ギャラリー</th><td><?php echo $gallery_id > 0 ? esc_html((string) $gallery_id) : '&mdash;'; ?></td></tr>
            <tr><th>申請日時</th><td><?php echo esc_html($submitted_at); ?></td></tr>
            <tr><th>申請ページ</th><td><?php echo '' !== $submitted_url ? '<a href="' . esc_url($submitted_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($submitted_url) . '</a>' : '&mdash;'; ?></td></tr>
            <tr><th>確認者</th><td><?php echo $checked_by > 0 ? esc_html(get_the_author_meta('display_name', $checked_by)) : '&mdash;'; ?></td></tr>
            <tr><th>確認日時</th><td><?php echo '' !== $checked_at ? esc_html($checked_at) : '&mdash;'; ?></td></tr>
            <tr><th>公開日時</th><td><?php echo '' !== $published_at ? esc_html($published_at) : '&mdash;'; ?></td></tr>
        </tbody>
    </table>
    <p>
        <?php if ('published' !== $status) : ?>
            <a class="button" href="<?php echo esc_url($check_url); ?>">確認済みにする</a>
            <a class="button button-primary" href="<?php echo esc_url($publish_url); ?>">公開済みにする</a>
        <?php else : ?>
            この申請は公開済みです。
        <?php endif; ?>
    </p>
    <?php
}

add_filter('manage_' . TSURUSE_ACCOUNT_APPLICATION_POST_TYPE . '_posts_columns', 'tsuruse_account_application_columns');
function tsuruse_account_application_columns($columns) {
    return array(
        'cb'                     => isset($columns['cb']) ? $columns['cb'] : '<input type="checkbox" />',
        'title'                  => '申請者',
        'tsuruse_application_email'       => 'メールアドレス',
        'tsuruse_application_affiliation' => '道院(支部)',
        'tsuruse_application_status'      => '状態',
        'tsuruse_application_user'        => '作成ユーザー',
        'date'                   => '申請日時',
    );
}

add_action('manage_' . TSURUSE_ACCOUNT_APPLICATION_POST_TYPE . '_posts_custom_column', 'tsuruse_render_account_application_column', 10, 2);
function tsuruse_render_account_application_column($column, $post_id) {
    switch ($column) {
        case 'tsuruse_application_email':
            echo esc_html((string) get_post_meta($post_id, '_tsuruse_application_email', true));
            break;
        case 'tsuruse_application_affiliation':
            echo esc_html((string) get_post_meta($post_id, '_tsuruse_application_affiliation', true));
            break;
        case 'tsuruse_application_status':
            echo esc_html(
                tsuruse_account_application_status_label(
                    (string) get_post_meta($post_id, '_tsuruse_application_status', true)
                )
            );
            break;
        case 'tsuruse_application_user':
            $user_id = (int) get_post_meta($post_id, '_tsuruse_application_user_id', true);
            if ($user_id > 0) {
                $username = (string) get_post_meta($post_id, '_tsuruse_application_username', true);
                echo '<a href="' . esc_url(admin_url('user-edit.php?user_id=' . $user_id)) . '">#' . esc_html((string) $user_id) . '</a>';
                if ('' !== $username) {
                    echo ' ' . esc_html($username);
                }
            } else {
                echo '&mdash;';
            }
            break;
    }
}

add_filter('manage_' . TSURUSE_PHOTO_PROVISION_POST_TYPE . '_posts_columns', 'tsuruse_photo_provision_columns');
function tsuruse_photo_provision_columns($columns) {
    return array(
        'cb'                    => isset($columns['cb']) ? $columns['cb'] : '<input type="checkbox" />',
        'title'                 => '提供名',
        'tsuruse_photo_name'    => '提供者',
        'tsuruse_photo_email'   => 'メールアドレス',
        'tsuruse_photo_status'  => '状態',
        'tsuruse_photo_checked' => '確認日時',
        'date'                  => '申請日時',
    );
}

add_action('manage_' . TSURUSE_PHOTO_PROVISION_POST_TYPE . '_posts_custom_column', 'tsuruse_render_photo_provision_column', 10, 2);
function tsuruse_render_photo_provision_column($column, $post_id) {
    switch ($column) {
        case 'tsuruse_photo_name':
            echo esc_html((string) get_post_meta($post_id, '_tsuruse_photo_provider_name', true));
            break;
        case 'tsuruse_photo_email':
            echo esc_html((string) get_post_meta($post_id, '_tsuruse_photo_email', true));
            break;
        case 'tsuruse_photo_status':
            echo esc_html(
                tsuruse_photo_provision_status_label(
                    (string) get_post_meta($post_id, '_tsuruse_photo_status', true)
                )
            );
            break;
        case 'tsuruse_photo_checked':
            $checked_at = (string) get_post_meta($post_id, '_tsuruse_photo_checked_at', true);
            echo '' !== $checked_at ? esc_html($checked_at) : '&mdash;';
            break;
    }
}

function tsuruse_account_application_status_label($status) {
    $labels = array(
        'approved'    => '承認済み',
        'mail_failed' => '承認済み（メール要確認）',
        'error'       => 'エラー',
        'rejected'    => '差戻し',
        'reject_mail_failed' => '差戻し（メール要確認）',
        'pending'     => '承認待ち',
    );

    return isset($labels[$status]) ? $labels[$status] : '承認待ち';
}

function tsuruse_photo_provision_status_label($status) {
    $labels = array(
        'published' => '公開済み',
        'checked'   => '確認済み',
        'received'  => '受付',
    );

    return isset($labels[$status]) ? $labels[$status] : '受付';
}

add_filter('post_row_actions', 'tsuruse_account_application_row_actions', 10, 2);
function tsuruse_account_application_row_actions($actions, $post) {
    if (TSURUSE_ACCOUNT_APPLICATION_POST_TYPE !== $post->post_type || !current_user_can(TSURUSE_ACCOUNT_APPLICATION_CAPABILITY)) {
        return $actions;
    }

    $status  = (string) get_post_meta($post->ID, '_tsuruse_application_status', true);
    $user_id = (int) get_post_meta($post->ID, '_tsuruse_application_user_id', true);

    if ('approved' !== $status && 'mail_failed' !== $status && $user_id < 1) {
        $actions['tsuruse_approve_application'] = sprintf(
            '<a href="%s">承認してアカウント作成</a>',
            esc_url(
                wp_nonce_url(
                    admin_url('admin-post.php?action=tsuruse_approve_account_application&application_id=' . (int) $post->ID),
                    'tsuruse_approve_account_application_' . (int) $post->ID
                )
            )
        );
    }

    if (!in_array($status, array('approved', 'mail_failed', 'rejected', 'reject_mail_failed'), true) && $user_id < 1) {
        $actions['tsuruse_reject_application'] = sprintf(
            '<a href="%s">差戻し</a>',
            esc_url(admin_url('post.php?post=' . (int) $post->ID . '&action=edit#tsuruse-account-reject'))
        );
    }

    return $actions;
}

add_filter('post_row_actions', 'tsuruse_photo_provision_row_actions', 10, 2);
function tsuruse_photo_provision_row_actions($actions, $post) {
    if (TSURUSE_PHOTO_PROVISION_POST_TYPE !== $post->post_type || !current_user_can(TSURUSE_PHOTO_PROVISION_CAPABILITY)) {
        return $actions;
    }

    $status = (string) get_post_meta($post->ID, '_tsuruse_photo_status', true);

    if ('published' !== $status) {
        $actions['tsuruse_photo_checked'] = sprintf(
            '<a href="%s">確認済みにする</a>',
            esc_url(
                wp_nonce_url(
                    admin_url('admin-post.php?action=tsuruse_mark_photo_provision_checked&application_id=' . (int) $post->ID),
                    'tsuruse_mark_photo_provision_checked_' . (int) $post->ID
                )
            )
        );
        $actions['tsuruse_photo_published'] = sprintf(
            '<a href="%s">公開済みにする</a>',
            esc_url(
                wp_nonce_url(
                    admin_url('admin-post.php?action=tsuruse_mark_photo_provision_published&application_id=' . (int) $post->ID),
                    'tsuruse_mark_photo_provision_published_' . (int) $post->ID
                )
            )
        );
    }

    return $actions;
}

add_action('admin_post_tsuruse_approve_account_application', 'tsuruse_handle_account_application_approval');
function tsuruse_handle_account_application_approval() {
    if (!current_user_can(TSURUSE_ACCOUNT_APPLICATION_CAPABILITY)) {
        wp_die('権限がありません。');
    }

    $application_id = isset($_GET['application_id']) ? absint($_GET['application_id']) : 0;
    check_admin_referer('tsuruse_approve_account_application_' . $application_id);

    $redirect = admin_url('post.php?post=' . $application_id . '&action=edit');
    $result   = tsuruse_approve_account_application($application_id);

    if (is_wp_error($result)) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'tsuruse_application_notice' => 'error',
                    'tsuruse_application_message'=> rawurlencode($result->get_error_message()),
                ),
                $redirect
            )
        );
        exit;
    }

    $notice = !empty($result['mail_sent']) ? 'approved' : 'mail_failed';
    wp_safe_redirect(
        add_query_arg(
            array(
                'tsuruse_application_notice' => $notice,
                'tsuruse_application_user'   => (int) $result['user_id'],
            ),
            $redirect
        )
    );
    exit;
}

add_action('admin_post_tsuruse_reject_account_application', 'tsuruse_handle_account_application_rejection');
function tsuruse_handle_account_application_rejection() {
    if (!current_user_can(TSURUSE_ACCOUNT_APPLICATION_CAPABILITY)) {
        wp_die('権限がありません。');
    }

    $application_id = isset($_POST['application_id']) ? absint($_POST['application_id']) : 0;
    check_admin_referer('tsuruse_reject_account_application_' . $application_id);

    $redirect = admin_url('post.php?post=' . $application_id . '&action=edit');
    $reason   = isset($_POST['remand']) ? sanitize_textarea_field(wp_unslash((string) $_POST['remand'])) : '';
    $result   = tsuruse_reject_account_application($application_id, $reason);

    if (is_wp_error($result)) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'tsuruse_application_notice'  => 'error',
                    'tsuruse_application_message' => rawurlencode($result->get_error_message()),
                ),
                $redirect
            )
        );
        exit;
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'tsuruse_application_notice' => !empty($result['mail_sent']) ? 'rejected' : 'reject_mail_failed',
            ),
            $redirect
        )
    );
    exit;
}

add_action('admin_post_tsuruse_mark_photo_provision_checked', 'tsuruse_handle_photo_provision_checked');
function tsuruse_handle_photo_provision_checked() {
    tsuruse_update_photo_provision_status_from_request('checked');
}

add_action('admin_post_tsuruse_mark_photo_provision_published', 'tsuruse_handle_photo_provision_published');
function tsuruse_handle_photo_provision_published() {
    tsuruse_update_photo_provision_status_from_request('published');
}

function tsuruse_update_photo_provision_status_from_request($status) {
    if (!current_user_can(TSURUSE_PHOTO_PROVISION_CAPABILITY)) {
        wp_die('権限がありません。');
    }

    $application_id = isset($_GET['application_id']) ? absint($_GET['application_id']) : 0;
    $action         = 'published' === $status ? 'tsuruse_mark_photo_provision_published' : 'tsuruse_mark_photo_provision_checked';

    check_admin_referer($action . '_' . $application_id);

    $result   = tsuruse_update_photo_provision_status($application_id, $status);
    $redirect = admin_url('post.php?post=' . $application_id . '&action=edit');

    wp_safe_redirect(
        add_query_arg(
            array(
                'tsuruse_photo_notice' => is_wp_error($result) ? 'error' : $status,
            ),
            $redirect
        )
    );
    exit;
}

function tsuruse_approve_account_application($application_id) {
    $application = get_post((int) $application_id);
    if (!$application instanceof WP_Post || TSURUSE_ACCOUNT_APPLICATION_POST_TYPE !== $application->post_type) {
        return new WP_Error('invalid_application', '申請データが見つかりません。');
    }

    $status  = (string) get_post_meta($application_id, '_tsuruse_application_status', true);
    $user_id = (int) get_post_meta($application_id, '_tsuruse_application_user_id', true);
    if (('approved' === $status || 'mail_failed' === $status) && $user_id > 0) {
        return new WP_Error('already_approved', 'この申請はすでに承認済みです。');
    }

    $name                 = (string) get_post_meta($application_id, '_tsuruse_application_name', true);
    $name_sei             = (string) get_post_meta($application_id, '_tsuruse_application_name_sei', true);
    $name_mei             = (string) get_post_meta($application_id, '_tsuruse_application_name_mei', true);
    $name_sei_romaji      = (string) get_post_meta($application_id, '_tsuruse_application_name_sei_romaji', true);
    $name_mei_romaji      = (string) get_post_meta($application_id, '_tsuruse_application_name_mei_romaji', true);
    $email                = (string) get_post_meta($application_id, '_tsuruse_application_email', true);
    $generation           = (string) get_post_meta($application_id, '_tsuruse_application_generation', true);
    $selected_affiliation = (string) get_post_meta($application_id, '_tsuruse_application_selected_affiliation', true);
    $affiliation          = (string) get_post_meta($application_id, '_tsuruse_application_affiliation', true);
    $message              = (string) get_post_meta($application_id, '_tsuruse_application_message', true);

    if ('' === $name || '' === $email || !is_email($email)) {
        update_post_meta($application_id, '_tsuruse_application_status', 'error');
        update_post_meta($application_id, '_tsuruse_application_last_error', 'メールアドレスまたは氏名が不足しています。');

        return new WP_Error('invalid_application_data', '申請データに不足があります。');
    }

    if (get_user_by('email', $email)) {
        update_post_meta($application_id, '_tsuruse_application_status', 'error');
        update_post_meta($application_id, '_tsuruse_application_last_error', 'このメールアドレスの既存ユーザーが見つかりました。');

        return new WP_Error('existing_user', 'このメールアドレスの既存ユーザーがいるため、自動作成を停止しました。');
    }

    $member_role = tsuruse_resolve_member_role_for_affiliation($selected_affiliation, $affiliation);
    if ('' === $member_role) {
        update_post_meta($application_id, '_tsuruse_application_status', 'error');
        update_post_meta($application_id, '_tsuruse_application_last_error', '権限の割り当て先を判定できませんでした。');

        return new WP_Error('invalid_member_role', '権限の割り当て先を判定できませんでした。');
    }

    $username = tsuruse_generate_member_username($name_sei_romaji, $email);
    $password = wp_generate_password(12, false, false);
    $user_id  = wp_insert_user(
        array(
            'user_login'   => $username,
            'user_pass'    => $password,
            'user_email'   => $email,
            'display_name' => $name,
            'nickname'     => $name,
            'role'         => $member_role,
        )
    );

    if (is_wp_error($user_id)) {
        update_post_meta($application_id, '_tsuruse_application_status', 'error');
        update_post_meta($application_id, '_tsuruse_application_last_error', $user_id->get_error_message());

        return $user_id;
    }

    $user_id = (int) $user_id;

    update_user_meta($user_id, 'show_admin_bar_front', 'false');
    update_user_meta($user_id, 'last_name', $name_sei);
    update_user_meta($user_id, 'first_name', $name_mei);
    update_user_meta($user_id, 'affiliation', $affiliation);
    update_user_meta($user_id, '_tsuruse_selected_affiliation', $selected_affiliation);
    update_user_meta($user_id, '_tsuruse_name_sei_romaji', $name_sei_romaji);
    update_user_meta($user_id, '_tsuruse_name_mei_romaji', $name_mei_romaji);
    update_user_meta($user_id, 'generation', $generation);
    update_user_meta($user_id, '_tsuruse_account_approved', '1');
    update_user_meta($user_id, '_tsuruse_account_approved_at', current_time('mysql'));
    update_user_meta($user_id, '_tsuruse_application_post_id', $application_id);
    delete_user_meta($user_id, '_tsuruse_account_disabled');
    delete_user_meta($user_id, '_tsuruse_account_disabled_at');
    delete_user_meta($user_id, '_tsuruse_account_delete_candidate');
    delete_user_meta($user_id, '_tsuruse_account_delete_candidate_at');

    tsuruse_sync_member_affiliation_roles($user_id, $selected_affiliation);

    $mail_sent = tsuruse_send_member_account_email(
        array(
            'name'        => $name,
            'email'       => $email,
            'username'    => $username,
            'password'    => $password,
            'affiliation' => $affiliation,
            'generation'  => $generation,
            'message'     => $message,
        )
    );

    update_post_meta($application_id, '_tsuruse_application_status', $mail_sent ? 'approved' : 'mail_failed');
    update_post_meta($application_id, '_tsuruse_application_user_id', $user_id);
    update_post_meta($application_id, '_tsuruse_application_username', $username);
    update_post_meta($application_id, '_tsuruse_application_approved_at', current_time('mysql'));
    update_post_meta($application_id, '_tsuruse_application_approved_by', get_current_user_id());
    update_post_meta($application_id, '_tsuruse_application_email_sent', $mail_sent ? '1' : '0');
    delete_post_meta($application_id, '_tsuruse_application_last_error');

    if ($mail_sent) {
        update_post_meta($application_id, '_tsuruse_application_email_sent_at', current_time('mysql'));
    }

    return array(
        'user_id'   => $user_id,
        'mail_sent' => $mail_sent,
    );
}

function tsuruse_reject_account_application($application_id, $reason) {
    $application = get_post((int) $application_id);
    if (!$application instanceof WP_Post || TSURUSE_ACCOUNT_APPLICATION_POST_TYPE !== $application->post_type) {
        return new WP_Error('invalid_application', '申請データが見つかりません。');
    }

    $reason = trim((string) $reason);
    if ('' === $reason) {
        return new WP_Error('empty_reason', '差戻し理由を入力してください。');
    }

    $email = (string) get_post_meta($application_id, '_tsuruse_application_email', true);
    if ('' === $email || !is_email($email)) {
        return new WP_Error('invalid_application_email', '申請者のメールアドレスが確認できません。');
    }

    $mail_sent = tsuruse_send_account_application_rejection_email(
        array(
            'name'        => (string) get_post_meta($application_id, '_tsuruse_application_name', true),
            'email'       => $email,
            'affiliation' => (string) get_post_meta($application_id, '_tsuruse_application_affiliation', true),
            'generation'  => (string) get_post_meta($application_id, '_tsuruse_application_generation', true),
            'reason'      => $reason,
        )
    );

    update_post_meta($application_id, '_tsuruse_application_status', $mail_sent ? 'rejected' : 'reject_mail_failed');
    update_post_meta($application_id, '_tsuruse_application_rejection_reason', $reason);
    update_post_meta($application_id, '_tsuruse_application_rejected_at', current_time('mysql'));
    update_post_meta($application_id, '_tsuruse_application_rejected_by', get_current_user_id());
    update_post_meta($application_id, '_tsuruse_application_rejection_email_sent', $mail_sent ? '1' : '0');
    delete_post_meta($application_id, '_tsuruse_application_last_error');

    if ($mail_sent) {
        update_post_meta($application_id, '_tsuruse_application_rejection_email_sent_at', current_time('mysql'));
    }

    return array(
        'mail_sent' => $mail_sent,
    );
}

function tsuruse_update_photo_provision_status($application_id, $status) {
    $application = get_post((int) $application_id);
    if (!$application instanceof WP_Post || TSURUSE_PHOTO_PROVISION_POST_TYPE !== $application->post_type) {
        return new WP_Error('invalid_photo_provision', '写真提供申請データが見つかりません。');
    }

    if (!in_array($status, array('checked', 'published'), true)) {
        return new WP_Error('invalid_photo_status', '申請状態が不正です。');
    }

    update_post_meta($application_id, '_tsuruse_photo_status', $status);

    if ('checked' === $status || 'published' === $status) {
        update_post_meta($application_id, '_tsuruse_photo_checked_by', get_current_user_id());
        update_post_meta($application_id, '_tsuruse_photo_checked_at', current_time('mysql'));
    }

    if ('published' === $status) {
        update_post_meta($application_id, '_tsuruse_photo_published_at', current_time('mysql'));
    }

    return true;
}

function tsuruse_generate_member_username($surname_romaji = '', $email = '') {
    $base = sanitize_user(
        strtolower(
            str_replace(
                array(' ', '　'),
                '',
                trim((string) $surname_romaji)
            )
        ),
        true
    );

    if ('' === $base && '' !== trim((string) $email)) {
        $local = (string) strstr((string) $email, '@', true);
        $base  = sanitize_user($local, true);
    }

    if ('' === $base) {
        $base = 'member';
    }

    $candidate = $base;
    $suffix    = 1;

    while (username_exists($candidate)) {
        $candidate = $base . $suffix;
        $suffix++;
    }

    return $candidate;
}

function tsuruse_send_member_account_email(array $args) {
    $login_url = wp_login_url(tsuruse_event_archive_url());
    $subject   = '【イベント写真館】アカウントを発行しました';
    $body      = array(
        trim((string) $args['name']) . ' 様',
        '',
        'イベント写真館のアカウントをご用意しました。',
        '以下の情報でログインできます。',
        '',
        'ユーザー名: ' . (string) $args['username'],
        'パスワード: ' . (string) $args['password'],
        'ログインURL: ' . $login_url,
        '',
        '道院(支部)名: ' . (string) $args['affiliation'],
    );

    if ('' !== (string) $args['generation']) {
        $body[] = '期生: ' . (string) $args['generation'] . '期';
    }

    $body[] = '';
    $body[] = '初回ログイン後に必要に応じてパスワードを変更してください。';
    $body[] = 'どうぞよろしくお願いいたします。';

    return wp_mail((string) $args['email'], $subject, implode("\n", $body));
}

function tsuruse_send_account_application_rejection_email(array $args) {
    $name        = trim((string) $args['name']);
    $email       = (string) $args['email'];
    $affiliation = trim((string) $args['affiliation']);
    $generation  = trim((string) $args['generation']);
    $reason      = trim((string) $args['reason']);
    $subject     = 'イベント写真館 登録申請を差戻しました。';
    $body        = array(
        ('' !== $name ? $name : '申請者') . ' 様',
        '',
        'イベント写真館の登録申請を差戻しました。',
        '以下の内容をご確認のうえ、必要に応じて再度お申し込みください。',
        '',
        '所属: ' . ('' !== $affiliation ? $affiliation : '-'),
        '期生: ' . ('' !== $generation ? $generation . '期' : '-'),
        '氏名: ' . ('' !== $name ? $name : '-'),
        '',
        '差戻し理由:',
        $reason,
        '',
        '--',
        'イベント写真館',
    );
    $headers     = array(
        'From: イベント写真館 <saitama-tsuruse@dream-times.net>',
        'Reply-To: ' . get_option('admin_email'),
    );

    return wp_mail($email, $subject, implode("\n", $body), $headers);
}

add_filter('manage_users_columns', 'tsuruse_member_lifecycle_user_columns');
function tsuruse_member_lifecycle_user_columns($columns) {
    $columns['tsuruse_member_status']     = '写真館状態';
    $columns['tsuruse_member_last_login'] = '最終ログイン';

    return $columns;
}

add_filter('manage_users_custom_column', 'tsuruse_render_member_lifecycle_user_column', 10, 3);
function tsuruse_render_member_lifecycle_user_column($value, $column_name, $user_id) {
    if (!in_array($column_name, array('tsuruse_member_status', 'tsuruse_member_last_login'), true)) {
        return $value;
    }

    if (!tsuruse_is_managed_member_user((int) $user_id)) {
        return '&mdash;';
    }

    if ('tsuruse_member_last_login' === $column_name) {
        $last_login = (string) get_user_meta((int) $user_id, '_tsuruse_last_login_at', true);
        return '' !== $last_login ? esc_html($last_login) : '&mdash;';
    }

    if ('1' === (string) get_user_meta((int) $user_id, '_tsuruse_account_delete_candidate', true)) {
        return '削除対象';
    }

    if (tsuruse_is_member_account_disabled((int) $user_id)) {
        return '無効';
    }

    if ('1' === (string) get_user_meta((int) $user_id, '_tsuruse_account_approved', true)) {
        return '承認済み・利用可能';
    }

    return '&mdash;';
}

add_action('show_user_profile', 'tsuruse_render_member_lifecycle_profile_fields');
add_action('edit_user_profile', 'tsuruse_render_member_lifecycle_profile_fields');
function tsuruse_render_member_lifecycle_profile_fields($user) {
    if (!$user instanceof WP_User || !tsuruse_is_managed_member_user((int) $user->ID)) {
        return;
    }

    $disabled        = tsuruse_is_member_account_disabled((int) $user->ID);
    $delete_candidate = '1' === (string) get_user_meta((int) $user->ID, '_tsuruse_account_delete_candidate', true);
    $last_login      = (string) get_user_meta((int) $user->ID, '_tsuruse_last_login_at', true);
    $disabled_at     = (string) get_user_meta((int) $user->ID, '_tsuruse_account_disabled_at', true);
    $candidate_at    = (string) get_user_meta((int) $user->ID, '_tsuruse_account_delete_candidate_at', true);
    ?>
    <h2>イベント写真館アカウント</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th>状態</th>
            <td><?php echo esc_html($delete_candidate ? '削除対象' : ($disabled ? '無効' : '承認済み・利用可能')); ?></td>
        </tr>
        <tr>
            <th>最終ログイン</th>
            <td><?php echo '' !== $last_login ? esc_html($last_login) : '&mdash;'; ?></td>
        </tr>
        <tr>
            <th>無効化日時</th>
            <td><?php echo '' !== $disabled_at ? esc_html($disabled_at) : '&mdash;'; ?></td>
        </tr>
        <tr>
            <th>削除対象判定日時</th>
            <td><?php echo '' !== $candidate_at ? esc_html($candidate_at) : '&mdash;'; ?></td>
        </tr>
        <?php if (current_user_can('edit_users') && $disabled) : ?>
            <tr>
                <th><label for="tsuruse_reactivate_account">再有効化</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="tsuruse_reactivate_account" id="tsuruse_reactivate_account" value="1">
                        このアカウントを再有効化する
                    </label>
                </td>
            </tr>
        <?php endif; ?>
    </table>
    <?php
}

add_action('personal_options_update', 'tsuruse_save_member_lifecycle_profile_fields');
add_action('edit_user_profile_update', 'tsuruse_save_member_lifecycle_profile_fields');
function tsuruse_save_member_lifecycle_profile_fields($user_id) {
    if (!current_user_can('edit_user', (int) $user_id) || !current_user_can('edit_users')) {
        return;
    }

    if (empty($_POST['tsuruse_reactivate_account'])) {
        return;
    }

    tsuruse_enable_member_account((int) $user_id);
}

add_action('admin_notices', 'tsuruse_account_application_notices');
function tsuruse_account_application_notices() {
    if (!current_user_can(TSURUSE_ACCOUNT_APPLICATION_CAPABILITY) || empty($_GET['tsuruse_application_notice'])) {
        return;
    }

    $notice_type = sanitize_key((string) $_GET['tsuruse_application_notice']);
    $message     = '';
    $class       = 'notice notice-success is-dismissible';

    if ('approved' === $notice_type) {
        $user_id  = isset($_GET['tsuruse_application_user']) ? absint($_GET['tsuruse_application_user']) : 0;
        $message  = $user_id > 0 ? '申請を承認し、ユーザーを作成してメール送信しました。' : '申請を承認しました。';
    } elseif ('mail_failed' === $notice_type) {
        $class   = 'notice notice-warning is-dismissible';
        $message = 'ユーザーは作成しましたが、メール送信に失敗しました。SMTP 設定をご確認ください。';
    } elseif ('rejected' === $notice_type) {
        $message = '申請を差戻し、理由をメール送信しました。';
    } elseif ('reject_mail_failed' === $notice_type) {
        $class   = 'notice notice-warning is-dismissible';
        $message = '申請を差戻しましたが、メール送信に失敗しました。SMTP 設定をご確認ください。';
    } elseif ('error' === $notice_type) {
        $class   = 'notice notice-error is-dismissible';
        $message = isset($_GET['tsuruse_application_message']) ? rawurldecode(wp_unslash((string) $_GET['tsuruse_application_message'])) : '申請処理でエラーが発生しました。';
    }

    if ('' === $message) {
        return;
    }

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

add_action('admin_notices', 'tsuruse_member_lifecycle_admin_notice');
function tsuruse_member_lifecycle_admin_notice() {
    if (!current_user_can('edit_users')) {
        return;
    }

    $candidate_ids = get_users(
        array(
            'fields'      => 'ids',
            'number'      => 1,
            'count_total' => true,
            'meta_query'  => array(
                array(
                    'key'   => '_tsuruse_account_delete_candidate',
                    'value' => '1',
                ),
            ),
        )
    );

    $count = is_array($candidate_ids) ? count($candidate_ids) : 0;
    if ($count < 1) {
        return;
    }

    printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        esc_html('イベント写真館の登録アカウントに削除対象候補があります。ユーザー一覧で「写真館状態」を確認してください。')
    );
}

add_action('admin_notices', 'tsuruse_photo_provision_notices');
function tsuruse_photo_provision_notices() {
    if (!current_user_can(TSURUSE_PHOTO_PROVISION_CAPABILITY) || empty($_GET['tsuruse_photo_notice'])) {
        return;
    }

    $notice_type = sanitize_key((string) $_GET['tsuruse_photo_notice']);
    $message     = '';
    $class       = 'notice notice-success is-dismissible';

    if ('checked' === $notice_type) {
        $message = '写真提供申請を確認済みにしました。';
    } elseif ('published' === $notice_type) {
        $message = '写真提供申請を公開済みにしました。';
    } elseif ('error' === $notice_type) {
        $class   = 'notice notice-error is-dismissible';
        $message = '写真提供申請の状態更新でエラーが発生しました。';
    }

    if ('' === $message) {
        return;
    }

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}
