<?php
/**
 * Plugin Name: SKST Event Dates
 * Description: The Events Calendar events with explicitly selected additional dates.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class SKST_Event_Dates {
    private static $syncing = false;

    public static function boot() {
        if (function_exists('eo_insert_event') || !function_exists('tribe_events') || get_stylesheet() !== 'doin') {
            return;
        }
        add_action('add_meta_boxes_tribe_events', array(__CLASS__, 'add_box'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'));
        add_action('save_post', array(__CLASS__, 'save'), 100, 2);
        add_action('admin_notices', array(__CLASS__, 'notice'));
        add_action('wp_trash_post', array(__CLASS__, 'trash_children'));
        add_action('untrashed_post', array(__CLASS__, 'sync'));
        add_action('before_delete_post', array(__CLASS__, 'delete_children'));
        add_action('tribe_events_single_event_after_the_content', array(__CLASS__, 'details'));
    }

    public static function add_box($post) {
        add_meta_box('skst-event-dates', '修練・イベントの追加日程', array(__CLASS__, 'box'), 'tribe_events', 'normal');
    }

    public static function admin_assets($hook) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'tribe_events' && in_array($hook, array('post.php', 'post-new.php'), true)) {
            wp_enqueue_script('skst-event-dates', plugin_dir_url(__FILE__) . 'skst-event-dates/admin.js', array(), filemtime(__DIR__ . '/skst-event-dates/admin.js'), true);
        }
    }

    public static function box($post) {
        $parent_id = (int) get_post_meta($post->ID, '_skst_schedule_parent', true);
        if ($parent_id) {
            echo '<p><a href="' . esc_url(get_edit_post_link($parent_id)) . '">元のイベントで日程を編集</a></p>';
            return;
        }
        wp_nonce_field('skst_event_dates_' . $post->ID, 'skst_event_dates_nonce');
        $dates = get_post_meta($post->ID, '_skst_additional_dates', true);
        $dates = is_array($dates) ? $dates : array();
        ?>
        <table class="widefat" id="skst-event-dates-table">
            <thead><tr><th>開始日時</th><th>終了日時</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($dates as $index => $date) : self::row($index, $date); endforeach; ?>
            </tbody>
        </table>
        <p><button type="button" class="button" id="skst-add-event-date"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> 日程を追加</button></p>
        <template id="skst-event-date-template"><?php self::row('__INDEX__', array('start' => '', 'end' => '')); ?></template>
        <?php
    }

    private static function row($index, $date) {
        ?>
        <tr>
            <td><input type="datetime-local" aria-label="開始日時" name="skst_event_dates[<?php echo esc_attr($index); ?>][start]" value="<?php echo esc_attr(str_replace(' ', 'T', substr($date['start'], 0, 16))); ?>" required></td>
            <td><input type="datetime-local" aria-label="終了日時" name="skst_event_dates[<?php echo esc_attr($index); ?>][end]" value="<?php echo esc_attr(str_replace(' ', 'T', substr($date['end'], 0, 16))); ?>" required></td>
            <td><button type="button" class="button skst-remove-event-date" aria-label="日程を削除" title="日程を削除"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button></td>
        </tr>
        <?php
    }

    public static function validate($rows, $timezone) {
        if (!is_array($rows) || count($rows) > 100) {
            return new WP_Error('skst_dates', '追加日程は100件以内で設定してください。');
        }
        $dates = array();
        foreach ($rows as $row) {
            $parsed = array();
            foreach (array('start', 'end') as $key) {
                $value = isset($row[$key]) && is_string($row[$key]) ? $row[$key] : '';
                $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $timezone);
                if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
                    return new WP_Error('skst_dates', '追加日程の開始日時・終了日時を正しく入力してください。');
                }
                $parsed[$key] = $date;
            }
            if ($parsed['end'] <= $parsed['start']) {
                return new WP_Error('skst_dates', '追加日程の終了日時は開始日時より後にしてください。');
            }
            $date = array('start' => $parsed['start']->format('Y-m-d H:i:s'), 'end' => $parsed['end']->format('Y-m-d H:i:s'));
            $key = $date['start'];
            if (isset($dates[$key])) {
                return new WP_Error('skst_dates', '開始日時が同じ追加日程が重複しています。');
            }
            $dates[$key] = $date;
        }
        ksort($dates);
        return array_values($dates);
    }

    public static function save($post_id, $post) {
        if (self::$syncing || $post->post_type !== 'tribe_events' || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || get_post_meta($post_id, '_skst_schedule_parent', true) || !current_user_can('edit_post', $post_id)) {
            return;
        }
        if (!isset($_POST['skst_event_dates_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['skst_event_dates_nonce'])), 'skst_event_dates_' . $post_id)) {
            return;
        }
        $timezone = get_post_meta($post_id, '_EventTimezone', true);
        try {
            $timezone = $timezone ? new DateTimeZone($timezone) : wp_timezone();
        } catch (Exception $error) {
            $timezone = wp_timezone();
        }
        $dates = self::validate(isset($_POST['skst_event_dates']) ? wp_unslash($_POST['skst_event_dates']) : array(), $timezone);
        if (is_wp_error($dates)) {
            self::error($dates->get_error_message());
            return;
        }
        $base_start = get_post_meta($post_id, '_EventStartDate', true);
        foreach ($dates as $date) {
            if ($date['start'] === $base_start) {
                self::error('標準の日程と同じ開始日時は、追加日程に設定できません。');
                return;
            }
        }
        update_post_meta($post_id, '_skst_additional_dates', $dates);
        self::sync($post_id);
    }

    private static function error($message) {
        set_transient('skst_event_dates_error_' . get_current_user_id(), $message, MINUTE_IN_SECONDS);
    }

    public static function notice() {
        $key = 'skst_event_dates_error_' . get_current_user_id();
        $message = get_transient($key);
        if ($message) {
            delete_transient($key);
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        }
    }

    public static function children($parent_id) {
        return get_posts(array('post_type' => 'tribe_events', 'post_status' => array('publish', 'future', 'draft', 'pending', 'private', 'trash'), 'numberposts' => -1, 'meta_key' => '_skst_schedule_parent', 'meta_value' => (int) $parent_id));
    }

    public static function sync($parent_id) {
        $parent = get_post($parent_id);
        if (self::$syncing || !$parent || $parent->post_type !== 'tribe_events' || get_post_meta($parent_id, '_skst_schedule_parent', true)) {
            return;
        }
        $dates = get_post_meta($parent_id, '_skst_additional_dates', true);
        if (!is_array($dates)) {
            return;
        }
        self::$syncing = true;
        try {
            $existing = array();
            foreach (self::children($parent_id) as $child) {
                $existing[get_post_meta($child->ID, '_skst_schedule_key', true)] = $child->ID;
            }
            $terms = wp_get_object_terms($parent_id, 'tribe_events_cat', array('fields' => 'ids'));
            foreach ($dates as $date) {
                $key = $date['start'];
                $child_id = isset($existing[$key]) ? $existing[$key] : 0;
                $args = array(
                    'post_title' => $parent->post_title,
                    'post_content' => $parent->post_content,
                    'post_excerpt' => $parent->post_excerpt,
                    'post_status' => $parent->post_status,
                    'post_author' => $parent->post_author,
                    'start_date' => $date['start'],
                    'end_date' => $date['end'],
                    'timezone' => get_post_meta($parent_id, '_EventTimezone', true) ?: wp_timezone_string(),
                    'all_day' => tribe_event_is_all_day($parent_id),
                    'venue' => get_post_meta($parent_id, '_EventVenueID', true),
                    'organizers' => get_post_meta($parent_id, '_EventOrganizerID', false),
                    'cost' => get_post_meta($parent_id, '_EventCost', true),
                    'url' => get_post_meta($parent_id, '_EventURL', true),
                    'show_map' => get_post_meta($parent_id, '_EventShowMap', true),
                    'show_map_link' => get_post_meta($parent_id, '_EventShowMapLink', true),
                    'category' => is_wp_error($terms) ? array() : $terms,
                );
                if ($child_id) {
                    $result = tribe_events()->where('post_status', array('publish', 'future', 'draft', 'pending', 'private', 'trash'))->where('id', $child_id)->set_args($args)->save();
                    if (empty($result[$child_id]) || is_wp_error($result[$child_id])) {
                        self::error('追加日程の更新に失敗しました。元のイベントを再度更新してください。');
                        return;
                    }
                } else {
                    $child = tribe_events()->set_args($args)->create();
                    if (!$child instanceof WP_Post) {
                        self::error('追加日程の登録に失敗しました。元のイベントを再度更新してください。');
                        return;
                    }
                    $child_id = $child->ID;
                }
                update_post_meta($child_id, '_skst_schedule_parent', (int) $parent_id);
                update_post_meta($child_id, '_skst_schedule_key', $key);
                $thumbnail = get_post_thumbnail_id($parent_id);
                if ($thumbnail) {
                    set_post_thumbnail($child_id, $thumbnail);
                } else {
                    delete_post_thumbnail($child_id);
                }
                unset($existing[$key]);
            }
            foreach ($existing as $child_id) {
                wp_trash_post($child_id);
            }
        } finally {
            self::$syncing = false;
        }
    }

    public static function trash_children($parent_id) {
        if (get_post_type($parent_id) === 'tribe_events' && !get_post_meta($parent_id, '_skst_schedule_parent', true)) {
            foreach (self::children($parent_id) as $child) {
                wp_trash_post($child->ID);
            }
        }
    }

    public static function delete_children($parent_id) {
        if (get_post_type($parent_id) === 'tribe_events' && !get_post_meta($parent_id, '_skst_schedule_parent', true)) {
            foreach (self::children($parent_id) as $child) {
                wp_delete_post($child->ID, true);
            }
        }
    }

    public static function details() {
        $post_id = get_the_ID();
        $parent_id = (int) get_post_meta($post_id, '_skst_schedule_parent', true) ?: $post_id;
        if (get_post_status($parent_id) !== 'publish') {
            return;
        }
        $children = array_filter(self::children($parent_id), function ($event) { return $event->post_status === 'publish'; });
        if (!$children) {
            return;
        }
        $events = array_merge(array(get_post($parent_id)), $children);
        usort($events, function ($left, $right) { return strcmp(get_post_meta($left->ID, '_EventStartDate', true), get_post_meta($right->ID, '_EventStartDate', true)); });
        echo '<section class="skst-event-dates"><h2>修練・イベント日程</h2><ul>';
        foreach ($events as $event) {
            $format = get_option('date_format') . (tribe_event_is_all_day($event->ID) ? '' : ' ' . get_option('time_format'));
            echo '<li><a href="' . esc_url(get_permalink($event)) . '">' . esc_html(tribe_get_start_date($event, true, $format) . ' ～ ' . tribe_get_end_date($event, true, $format)) . '</a></li>';
        }
        echo '</ul></section>';
    }
}

add_action('init', array('SKST_Event_Dates', 'boot'), 30);
