<?php
/**
 * 埼玉鶴瀬道院サイト用の子テーマ機能。
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/inc/plugin-setup.php';

if (!defined('DOIN_SITE_TAG_NAME')) {
    define('DOIN_SITE_TAG_NAME', '埼玉鶴瀬道院');
}

if (!defined('DOIN_OTHER_SITE_TAG_NAME')) {
    define('DOIN_OTHER_SITE_TAG_NAME', '志木スポーツ少年団');
}

if (!defined('DOIN_NOTICE_CATEGORY_NAME')) {
    define('DOIN_NOTICE_CATEGORY_NAME', 'お知らせ');
}

if (!defined('DOIN_TRAINING_CATEGORY_NAME')) {
    define('DOIN_TRAINING_CATEGORY_NAME', '修練日程のお知らせ');
}

if (!defined('DOIN_ANNUAL_CATEGORY_NAME')) {
    define('DOIN_ANNUAL_CATEGORY_NAME', '年間行事');
}

if (!defined('DOIN_SEED_VERSION')) {
    define('DOIN_SEED_VERSION', '2026100501');
}

function doin_asset_url($path) {
    return get_stylesheet_directory_uri() . '/assets/' . ltrim($path, '/');
}

add_action('wp_enqueue_scripts', 'doin_enqueue_contact_completion');
function doin_enqueue_contact_completion() {
    if (!is_page('contact') || !function_exists('wpcf7')) {
        return;
    }

    $completion = get_page_by_path('contact/お問い合わせ完了');
    if (!$completion || $completion->post_status !== 'publish') {
        return;
    }

    wp_enqueue_script('doin-contact-completion', doin_asset_url('js/contact-completion.js'), array(), filemtime(__DIR__ . '/assets/js/contact-completion.js'), true);
    wp_add_inline_script('doin-contact-completion', 'window.doinContactCompletion = ' . wp_json_encode(array(
        'pageId' => get_queried_object_id(),
        'url' => get_permalink($completion),
    )) . ';', 'before');
}

add_action('after_setup_theme', 'doin_register_theme_menus');
function doin_register_theme_menus() {
    register_nav_menus(
        array(
            'doin-primary' => '埼玉鶴瀬道院 メニュー',
        )
    );
}

add_action('init', 'doin_ensure_terms', 20);
function doin_ensure_terms() {
    foreach (array(DOIN_NOTICE_CATEGORY_NAME, DOIN_TRAINING_CATEGORY_NAME, DOIN_ANNUAL_CATEGORY_NAME) as $category_name) {
        if (!term_exists($category_name, 'category')) {
            wp_insert_term($category_name, 'category');
        }
    }

    foreach (array(DOIN_SITE_TAG_NAME, DOIN_OTHER_SITE_TAG_NAME) as $tag_name) {
        if (!term_exists($tag_name, 'post_tag')) {
            wp_insert_term($tag_name, 'post_tag');
        }
    }
}

add_action('init', 'doin_maybe_seed_site_pages', 30);
function doin_maybe_seed_site_pages() {
    if (DOIN_SEED_VERSION === (string) get_option('_doin_seed_version', '')) {
        return;
    }

    $pages = doin_seed_page_definitions();
    $page_ids = array();

    foreach ($pages as $slug => $page) {
        $page_ids[$slug] = doin_ensure_page($slug, $page['title'], $page['content']);
    }

    if (!empty($page_ids['home'])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', (int) $page_ids['home']);
    }

    doin_ensure_primary_menu($page_ids, $pages);

    update_option('_doin_seed_version', DOIN_SEED_VERSION, false);
}

function doin_seed_page_definitions() {
    return array(
        'home' => array(
            'title'   => 'ホーム',
            'content' => '[doin_home]',
        ),
        'about' => array(
            'title'   => '道院のご案内',
            'content' => '<h3>道院長のごあいさつ</h3><p>埼玉鶴瀬道院の活動方針、道院長の略歴、修練場所を掲載します。</p><h3>修練場所</h3><p>修練場所説明を掲載します。</p>',
        ),
        'training' => array(
            'title'   => '修練内容',
            'content' => file_get_contents(__DIR__ . '/page-content/training.html'),
        ),
        'training-schedule' => array(
            'title'   => '修練日程',
            'content' => '[doin_training_schedule]',
        ),
        'event-gallery' => array(
            'title'   => 'イベント写真館',
            'content' => '<p>埼玉鶴瀬道院、志木スポーツ少年団にて開催されましたイベントの記録を順次掲載します。道院内のイベントであり、プライバシーにも関わるため、写真コンテンツはイベント写真館側のログインおよび閲覧権限に基づいて表示します。</p>[doin_gallery_link][doin_gallery_whatsnew]',
        ),
        'trial-entry' => array(
            'title'   => '見学・入門案内',
            'content' => doin_trial_entry_content(),
        ),
        'contact' => array(
            'title'   => 'お問い合わせ',
            'content' => '<h3>お問い合わせ</h3><p>お問い合わせ種類を選択し、必要事項をご記入ください。</p>',
        ),
    );
}

function doin_ensure_page($slug, $title, $content) {
    $existing = get_page_by_path($slug);

    if ($existing instanceof WP_Post) {
        return (int) $existing->ID;
    }

    $page_id = wp_insert_post(
        wp_slash(
            array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_name'    => $slug,
                'post_title'   => $title,
                'post_content' => $content,
            )
        ),
        true
    );

    return is_wp_error($page_id) ? 0 : (int) $page_id;
}

function doin_ensure_primary_menu(array $page_ids, array $pages) {
    $menu_name = '埼玉鶴瀬道院 メニュー';
    $menu = wp_get_nav_menu_object($menu_name);

    if (!$menu) {
        $menu_id = wp_create_nav_menu($menu_name);
        if (is_wp_error($menu_id)) {
            return;
        }
    } else {
        $menu_id = (int) $menu->term_id;
    }

    $menu_items = wp_get_nav_menu_items($menu_id);
    $existing_object_ids = array();

    if (is_array($menu_items)) {
        foreach ($menu_items as $item) {
            $existing_object_ids[] = (int) $item->object_id;
        }
    }

    foreach (array('home', 'about', 'training', 'training-schedule', 'event-gallery', 'trial-entry', 'contact') as $slug) {
        if (empty($page_ids[$slug]) || in_array((int) $page_ids[$slug], $existing_object_ids, true)) {
            continue;
        }

        wp_update_nav_menu_item(
            $menu_id,
            0,
            array(
                'menu-item-title'     => $pages[$slug]['title'],
                'menu-item-object-id' => (int) $page_ids[$slug],
                'menu-item-object'    => 'page',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
            )
        );
    }

    $locations = (array) get_theme_mod('nav_menu_locations', array());
    $locations['global-nav'] = $menu_id;
    $locations['doin-primary'] = $menu_id;
    $locations['vk-mobile-nav'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}

add_filter('wp_nav_menu_objects', 'doin_remove_home_menu_item', 10, 2);
function doin_remove_home_menu_item($items, $args) {
    $theme_locations = array('global-nav', 'doin-primary', 'vk-mobile-nav');
    if (empty($args->theme_location) || !in_array($args->theme_location, $theme_locations, true)) {
        return $items;
    }

    $front_page_id = (int) get_option('page_on_front');
    if (!$front_page_id) {
        return $items;
    }

    return array_values(
        array_filter(
            $items,
            function ($item) use ($front_page_id) {
                return !('post_type' === $item->type && 'page' === $item->object && (int) $item->object_id === $front_page_id);
            }
        )
    );
}

add_filter('body_class', 'doin_body_class');
function doin_body_class($classes) {
    $classes[] = 'skst-doin-site';

    return $classes;
}

add_filter('lightning_top_slide_count_max', 'doin_extend_top_slide_count');
function doin_extend_top_slide_count($count) {
    return max((int) $count, 5);
}

add_filter('lightning_is_layout_onecolumn', 'doin_use_two_column_on_front');
function doin_use_two_column_on_front($onecolumn) {
    if (is_front_page()) {
        return false;
    }

    return $onecolumn;
}

add_filter('lightning_is_subsection', 'doin_show_subsection_on_front');
function doin_show_subsection_on_front($show) {
    if (is_front_page()) {
        return true;
    }

    return $show;
}

add_filter('sidebars_widgets', 'doin_hide_footer_before_widgets_on_front');
function doin_hide_footer_before_widgets_on_front($sidebars_widgets) {
    if (isset($sidebars_widgets['footer-before-widget'])) {
        $sidebars_widgets['footer-before-widget'] = array();
    }

    return $sidebars_widgets;
}

add_filter('lightning_is_page_header', 'doin_disable_page_header', 10, 2);
function doin_disable_page_header($display, $position) {
    return 'page_header' === $position ? false : $display;
}

add_filter('lightning_is_site_header', 'doin_disable_default_site_header', 10, 2);
function doin_disable_default_site_header($display, $position) {
    return 'site_header' === $position ? false : $display;
}

add_action('lightning_site_header_after', 'doin_render_site_header', 9);
function doin_render_site_header() {
    ?>
    <header class="doin-site-header">
        <div class="container doin-site-header__top">
            <div class="doin-site-header__brand">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <img src="<?php echo esc_url(doin_asset_url('images/logo/logo.png')); ?>" alt="金剛禅総本山少林寺 埼玉鶴瀬道院">
                </a>
            </div>
            <div class="doin-site-header__contact">
                <span>お問い合わせは</span>
                <a href="tel:09026432887">TEL.090-2643-2887</a>
            </div>
        </div>
        <nav class="container doin-site-nav" aria-label="埼玉鶴瀬道院 メニュー">
            <div class="container">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'global-nav',
                        'container'      => false,
                        'menu_class'     => 'doin-site-nav__list',
                        'fallback_cb'    => false,
                        'depth'          => 1,
                    )
                );
                ?>
            </div>
        </nav>
    </header>
    <?php
}

add_action('lightning_sub_section_prepend', 'doin_render_sidebar_panel', 5);
function doin_render_sidebar_panel() {
    ?>
    <aside class="doin-sidebar-panel" aria-label="埼玉鶴瀬道院 サイドバー">
        <div class="doin-sidebar-profile">
            <img class="doin-sidebar-profile__photo" src="<?php echo esc_url(doin_asset_url('images/info/douin_img.jpg')); ?>" alt="埼玉鶴瀬道院 道院長写真">
            <p class="doin-sidebar-profile__site">埼玉鶴瀬道院</p>
            <p class="doin-sidebar-profile__leader">道院長　大野木　憲三</p>
            <p class="doin-sidebar-profile__tel">TEL 090-2643-2887</p>
        </div>
        <nav class="doin-sidebar-menu" aria-label="埼玉鶴瀬道院 関連メニュー">
            <a href="<?php echo esc_url(get_category_link((int) get_cat_ID(DOIN_NOTICE_CATEGORY_NAME))); ?>"><img src="<?php echo esc_url(doin_asset_url('images/side_menu/bn_notice.jpg')); ?>" alt="お知らせ"></a>
            <a href="<?php echo esc_url(home_url('/training-schedule/')); ?>"><img src="<?php echo esc_url(doin_asset_url('images/side_menu/bn_calender.jpg')); ?>" alt="修練カレンダー"></a>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>"><img src="<?php echo esc_url(doin_asset_url('images/side_menu/bn_contact.jpg')); ?>" alt="お問い合わせ"></a>
        </nav>
        <div class="doin-sidebar-links">
            <a href="https://www.shorinjikempo.or.jp/" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url(doin_asset_url('images/side_menu/bn_honbu.jpg')); ?>" alt="少林寺拳法公式サイト"></a>
            <a href="https://www.shorinjikempo-saitama.jp/" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url(doin_asset_url('images/side_menu/bn_saitama_g.jpg')); ?>" alt="埼玉県 少林寺拳法グループ"></a>
            <a href="https://www.shorinjikempo.or.jp/search/doin" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url(doin_asset_url('images/side_menu/bn_douinlist.jpg')); ?>" alt="埼玉県下 道院一覧"></a>
        </div>
    </aside>
    <?php
}

add_action('lightning_site_footer_before', 'doin_render_permission_footer', 15);
function doin_render_permission_footer() {
    ?>
    <div class="doin-permission-footer">
        <div class="container">
            <p>Copyright © SAITAMA SHORINJI KEMPO. All rights reserved.　許諾番号：17宗商第12号</p>
        </div>
    </div>
    <?php
}

add_filter('lightning_footerCopyRightCustom', 'doin_render_footer_copyright');
function doin_render_footer_copyright() {
    return '<p>Copyright © どり〜む・たいむす All Rights Reserved.</p>';
}

add_filter('lightning_footerPoweredCustom', '__return_empty_string');

add_action('pre_get_posts', 'doin_filter_post_queries');
function doin_filter_post_queries($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!$query->is_home() && !$query->is_category() && !$query->is_archive() && !$query->is_search()) {
        return;
    }

    $tax_query = (array) $query->get('tax_query');
    $tax_query[] = doin_site_visibility_tax_query();
    $query->set('tax_query', $tax_query);
}

function doin_site_visibility_tax_query() {
    $site_tag       = get_term_by('name', DOIN_SITE_TAG_NAME, 'post_tag');
    $other_site_tag = get_term_by('name', DOIN_OTHER_SITE_TAG_NAME, 'post_tag');

    if (!$site_tag instanceof WP_Term || !$other_site_tag instanceof WP_Term) {
        return array();
    }

    return array(
        'relation' => 'OR',
        array(
            'taxonomy' => 'post_tag',
            'field'    => 'term_id',
            'terms'    => array((int) $site_tag->term_id),
        ),
        array(
            'taxonomy' => 'post_tag',
            'operator' => 'NOT EXISTS',
        ),
        array(
            'taxonomy' => 'post_tag',
            'field'    => 'term_id',
            'terms'    => array((int) $other_site_tag->term_id),
            'operator' => 'NOT IN',
        ),
    );
}

function doin_get_category_posts($category_name, $limit = 5) {
    $category = get_term_by('name', $category_name, 'category');
    if (!$category instanceof WP_Term) {
        return array();
    }

    $tax_query = array(
        'relation' => 'AND',
        array(
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => array((int) $category->term_id),
        ),
        doin_site_visibility_tax_query(),
    );

    return get_posts(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => absint($limit),
            'ignore_sticky_posts' => true,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'tax_query'           => $tax_query,
        )
    );
}

function doin_get_annual_events($limit = 10) {
    if (!function_exists('eo_get_events')) {
        return array();
    }

    $category = get_term_by('name', DOIN_ANNUAL_CATEGORY_NAME, 'event-category');
    if (!$category instanceof WP_Term) {
        return array();
    }

    return eo_get_events(
        array(
            'post_status'    => 'publish',
            'numberposts'    => absint($limit),
            'showpastevents' => true,
            'orderby'        => 'eventstart',
            'order'          => 'DESC',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'event-category',
                    'field'    => 'term_id',
                    'terms'    => array((int) $category->term_id),
                ),
            ),
        )
    );
}

function doin_render_post_list($posts, $empty_text = '現在、掲載情報はありません。', $date_format = 'Y.m.d') {
    if (empty($posts)) {
        return '<p class="doin-empty">' . esc_html($empty_text) . '</p>';
    }

    ob_start();
    ?>
    <ul class="doin-post-list">
        <?php foreach ($posts as $post) : ?>
            <?php
            $is_event = function_exists('eo_get_the_start') && $post->post_type === 'event';
            $occurrence_id = isset($post->occurrence_id) ? (int) $post->occurrence_id : 0;
            $datetime = $is_event ? eo_get_the_start('c', $post->ID, $occurrence_id) : get_the_date('c', $post);
            $date = $is_event ? eo_get_the_start($date_format ?: get_option('date_format'), $post->ID, $occurrence_id) : get_the_date($date_format, $post);
            ?>
            <li>
                <time datetime="<?php echo esc_attr($datetime); ?>"><?php echo esc_html($date); ?></time>
                <a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    return ob_get_clean();
}

add_action('wp', 'doin_prepare_training_calendar');
function doin_prepare_training_calendar() {
    if (!is_page('training-schedule') || !function_exists('eo_get_event_fullcalendar')) {
        return;
    }

    $GLOBALS['doin_training_calendar_html'] = eo_get_event_fullcalendar(
        array(
            'defaultview' => 'month',
            'headerleft' => 'prev next today',
            'headercenter' => 'title',
            'headerright' => '',
            'responsive' => false,
            'titleformatmonth' => 'Y年n月',
        )
    );
}

add_filter('the_content', 'doin_event_occurrence_details');
function doin_event_occurrence_details($content) {
    if (!is_singular('event') || !in_the_loop() || !is_main_query() || !function_exists('eo_get_the_occurrences_of')) {
        return $content;
    }
    $occurrences = eo_get_the_occurrences_of(get_the_ID());
    if (!$occurrences) {
        return $content;
    }
    $format = get_option('date_format') . (eo_is_all_day() ? '' : ' ' . get_option('time_format'));
    $html = '<section class="doin-event-dates"><h3>開催日程</h3><ul>';
    foreach ($occurrences as $occurrence) {
        $html .= '<li><time datetime="' . esc_attr($occurrence['start']->format('c')) . '">' . esc_html(eo_format_datetime($occurrence['start'], $format)) . '</time>';
        if (!eo_is_all_day() || $occurrence['start']->format('Y-m-d') !== $occurrence['end']->format('Y-m-d')) {
            $html .= ' ～ ' . esc_html(eo_format_datetime($occurrence['end'], $format));
        }
        $html .= '</li>';
    }
    return $html . '</ul></section>' . $content;
}

add_shortcode('doin_training_calendar', 'doin_training_calendar_shortcode');
function doin_training_calendar_shortcode() {
    return isset($GLOBALS['doin_training_calendar_html'])
        ? $GLOBALS['doin_training_calendar_html']
        : '<p class="doin-empty">現在、修練カレンダーを表示できません。</p>';
}

add_shortcode('doin_home', 'doin_home_shortcode');
function doin_home_shortcode() {
    $notices = get_posts(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => 5,
            'ignore_sticky_posts' => true,
            'orderby'             => array('date' => 'DESC', 'title' => 'DESC'),
        )
    );

    ob_start();
    ?>
    <section class="doin-home-lead">
        <div>
            <p class="doin-home-lead__catch">少林寺拳法の修行を通じて、<br>
自分に自信を持ち、<br>
相手のことを思いやれる人づくりを目指しています。</p>
            <p>当道院は少林寺拳法の修行を通じて、自分に自信を持ち、相手のことを思いやれる人づくりを目指しています。 在籍は小学生から社会人までと、年齢、性別、職種を超えて様々な人たちが和気あいあいと修練に励んでいます。<br>
<br>
入門の検討にあたっての見学・体験修練につきましては、随時受付しております。詳しくは「見学・入門案内」をご覧ください。</p>
            <figure class="doin-home-lead__photo">
                <img src="<?php echo esc_url(doin_asset_url('images/info/top_snap00.jpg')); ?>" alt="埼玉鶴瀬道院 修練風景">
            </figure>
        </div>
    </section>
    <section class="doin-section doin-home-notices">
        <div class="doin-section__header">
            <h3>お知らせ</h3>
        </div>
        <?php echo doin_render_post_list($notices, '現在、掲載情報はありません。', ''); ?>
    </section>
    <?php
    return ob_get_clean();
}

add_shortcode('doin_notice_list', 'doin_notice_list_shortcode');
function doin_notice_list_shortcode($atts = array()) {
    $atts = shortcode_atts(array('limit' => 10), $atts, 'doin_notice_list');

    return doin_render_post_list(doin_get_category_posts(DOIN_NOTICE_CATEGORY_NAME, (int) $atts['limit']));
}

add_shortcode('doin_training_schedule', 'doin_training_schedule_shortcode');
function doin_training_schedule_shortcode() {
    ob_start();
    ?>
    <section class="doin-section doin-training-memo">
        <h3>修練日程メモ</h3>
        <?php echo doin_render_post_list(doin_get_category_posts(DOIN_TRAINING_CATEGORY_NAME, 10), '現在、修練日程メモはありません。'); ?>
    </section>
    <section class="doin-section">
        <h3>修練カレンダー</h3>
        <div class="doin-calendar-embed">
            <?php echo do_shortcode('[doin_training_calendar]'); ?>
        </div>
    </section>
    <section class="doin-section doin-annual-events">
        <p class="doin-gallery-request">※ 各行事後に順次<a href="https://shorinjikempo.dream-times.net/tsuruse-shiki/">写真館</a>に写真や動画をアップしております。<br>
皆様からの撮影した写真、動画もアップしますので<a href="https://shorinjikempo.dream-times.net/tsuruse-shiki/%e5%86%99%e7%9c%9f%e3%80%81%e5%8b%95%e7%94%bb%e3%82%92%e6%8f%90%e4%be%9b%e3%81%8a%e9%a1%98%e3%81%84%e3%81%97%e3%81%be%e3%81%99">ご提供</a>をお願いします。</p>
        <h3>年間行事</h3>
        <?php echo doin_render_post_list(doin_get_annual_events(10), '現在、年間行事のお知らせはありません。', ''); ?>
    </section>
    <?php
    return ob_get_clean();
}

function doin_gallery_base_url() {
    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $is_local = $host === 'localhost' || substr((string) $host, -6) === '.local';
    return trailingslashit(apply_filters('doin_gallery_base_url', $is_local
        ? 'http://wordpress.local/tsuruse-shiki/'
        : 'https://shorinjikempo.dream-times.net/tsuruse-shiki/'));
}

function doin_gallery_post_url($post, $base_url) {
    $parts = wp_parse_url(get_permalink($post));
    $source_path = trailingslashit(wp_parse_url(home_url('/'), PHP_URL_PATH));
    $path = isset($parts['path']) ? $parts['path'] : '';
    if (strpos($path, $source_path) !== 0) {
        return $base_url;
    }
    $url = $base_url . ltrim(substr($path, strlen($source_path)), '/');
    return $url . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

function doin_gallery_post_is_new($post, $now = null) {
    $published = get_post_datetime($post, 'date', 'local');
    $now = $now instanceof DateTimeImmutable ? $now : current_datetime();
    return $published && $now->getTimestamp() >= $published->getTimestamp()
        && $now->getTimestamp() < $published->setTime(0, 0)->modify('+14 days')->getTimestamp();
}

function doin_get_gallery_whatsnew() {
    if (!is_multisite()) {
        return array();
    }
    $sites = get_sites(array('network_id' => get_current_network_id(), 'path' => '/tsuruse-shiki/', 'number' => 1, 'archived' => 0, 'deleted' => 0, 'spam' => 0));
    if (!$sites) {
        return array();
    }
    $base_url = doin_gallery_base_url();
    switch_to_blog((int) $sites[0]->blog_id);
    try {
        $query = array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 5, 'orderby' => array('date' => 'DESC', 'ID' => 'DESC'), 'ignore_sticky_posts' => true, 'no_found_rows' => true);
        // Match the gallery's What'sNew selection, including its pinned announcements.
        $sticky = array_filter(array_map('absint', (array) get_option('sticky_posts')));
        $posts = $sticky ? (new WP_Query(array_merge($query, array('post__in' => $sticky))))->posts : array();
        if (count($posts) < 5) {
            $posts = array_merge($posts, (new WP_Query(array_merge($query, array('posts_per_page' => 5 - count($posts), 'post__not_in' => wp_list_pluck($posts, 'ID')))))->posts);
        }
        usort($posts, function ($a, $b) {
            return strcmp($b->post_date, $a->post_date) ?: $b->ID <=> $a->ID;
        });
        $items = array();
        foreach ($posts as $post) {
            $items[] = array('id' => $post->ID, 'title' => get_the_title($post), 'date' => get_the_date(get_option('date_format'), $post), 'datetime' => get_the_date('c', $post), 'url' => doin_gallery_post_url($post, $base_url), 'new' => doin_gallery_post_is_new($post));
        }
        return $items;
    } finally {
        restore_current_blog();
    }
}

add_shortcode('doin_gallery_link', function () {
    return '<p><a class="button" href="' . esc_url(doin_gallery_base_url()) . '">イベント写真館へ</a></p>';
});

add_shortcode('doin_gallery_whatsnew', function () {
    $items = doin_get_gallery_whatsnew();
    $html = '<section class="doin-section doin-gallery-whatsnew"><h3>What’sNew</h3>';
    if (!$items) {
        return $html . '<p class="doin-empty">現在、掲載情報はありません。</p></section>';
    }
    $html .= '<ul class="doin-post-list">';
    foreach ($items as $item) {
        $html .= '<li><time datetime="' . esc_attr($item['datetime']) . '">' . esc_html($item['date']) . '</time><a href="' . esc_url($item['url']) . '">' . esc_html($item['title']);
        if ($item['new']) {
            $html .= ' <span class="doin-gallery-new" aria-label="新着">New</span>';
        }
        $html .= '</a></li>';
    }
    return $html . '</ul></section>';
});

function doin_trial_entry_content() {
    return str_replace(
        array('{{DOIN_TRIAL_ASSETS}}', '{{DOIN_CONTACT_URL}}'),
        array(esc_url(get_stylesheet_directory_uri() . '/assets/images/trial'), esc_url(home_url('/contact/'))),
        file_get_contents(__DIR__ . '/page-content/trial-entry.html')
    );
}

add_action('after_setup_theme', function () {
    add_theme_support('editor-styles');
    add_editor_style('trial-entry.css');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('doin-trial-entry', get_stylesheet_directory_uri() . '/trial-entry.css', array(), filemtime(__DIR__ . '/trial-entry.css'));
});

add_action('init', function () {
    register_block_pattern_category('doin', array('label' => '埼玉鶴瀬道院'));
    foreach (parse_blocks(doin_trial_entry_content()) as $block) {
        if (($block['attrs']['className'] ?? '') === 'doin-qa-pair') {
            register_block_pattern('doin/question-answer', array(
                'title' => '質問と先生の回答',
                'description' => '質問者と先生のアイコン付きQ&A。文章を編集して追加できます。',
                'categories' => array('doin'),
                'content' => serialize_block($block),
            ));
            break;
        }
    }
});

add_shortcode('doin_training_content', 'doin_training_content_shortcode');
function doin_training_content_shortcode() {
    ob_start();
    ?>
    <section class="doin-section">
        <h3>修練日時</h3>
        <p>修練日時は修練日程ページおよび修練カレンダーでご確認ください。</p>
    </section>
    <section class="doin-section">
        <h3>修練風景</h3>
        <p>基本、昇級・昇段に向けた稽古、演武、親子での修練など、年齢や経験に応じて取り組みます。</p>
    </section>
    <?php
    return ob_get_clean();
}
