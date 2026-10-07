<?php
// Run after loading the Local WordPress installation; fixtures are removed in finally.
if (!defined('ABSPATH') || !class_exists('SKST_Event_Dates')) {
    throw new RuntimeException('Load the doin WordPress site before running this check.');
}

function skst_check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

wp_set_current_user(1);
$parent_id = 0;
$child_ids = array();
try {
    $timezone = new DateTimeZone('Asia/Tokyo');
    skst_check(is_wp_error(SKST_Event_Dates::validate(array(array('start' => '2040-02-30T18:00', 'end' => '2040-02-30T19:00')), $timezone)), 'Invalid date accepted.');
    skst_check(is_wp_error(SKST_Event_Dates::validate(array(array('start' => '2040-10-08T18:00', 'end' => '2040-10-08T17:00')), $timezone)), 'Reverse interval accepted.');
    $row = array('start' => '2040-10-08T18:00', 'end' => '2040-10-08T19:00');
    skst_check(is_wp_error(SKST_Event_Dates::validate(array($row, $row), $timezone)), 'Duplicate date accepted.');

    $category = get_term_by('name', '修練', 'tribe_events_cat');
    $parent = tribe_events()->set_args(array('post_title' => 'SKST integration fixture', 'post_content' => 'Integration fixture', 'post_status' => 'private', 'start_date' => '2040-10-01 18:00:00', 'end_date' => '2040-10-01 19:00:00', 'timezone' => 'Asia/Tokyo', 'category' => array($category->term_id)))->create();
    skst_check($parent instanceof WP_Post, 'Parent creation failed.');
    $parent_id = $parent->ID;
    $dates = SKST_Event_Dates::validate(array($row, array('start' => '2040-10-15T18:00', 'end' => '2040-10-15T20:00')), $timezone);
    update_post_meta($parent_id, '_skst_additional_dates', $dates);
    SKST_Event_Dates::sync($parent_id);
    $children = SKST_Event_Dates::children($parent_id);
    $child_ids = wp_list_pluck($children, 'ID');
    skst_check(count($children) === 2, 'Additional events were not created.');
    foreach ($children as $child) {
        skst_check($child->post_status === 'private', 'Private event became public.');
        skst_check(has_term('修練', 'tribe_events_cat', $child), 'Category not inherited.');
        skst_check(get_post_meta($child->ID, '_EventTimezone', true) === 'Asia/Tokyo', 'Timezone not inherited.');
    }
    SKST_Event_Dates::sync($parent_id);
    skst_check(count(SKST_Event_Dates::children($parent_id)) === 2, 'Repeated save created duplicates.');

    wp_update_post(array('ID' => $parent_id, 'post_title' => 'SKST updated fixture'));
    SKST_Event_Dates::sync($parent_id);
    foreach (SKST_Event_Dates::children($parent_id) as $child) {
        skst_check($child->post_title === 'SKST updated fixture', 'Title did not propagate.');
    }

    $_POST = array('skst_event_dates_nonce' => wp_create_nonce('skst_event_dates_' . $parent_id), 'skst_event_dates' => array($row));
    SKST_Event_Dates::save($parent_id, get_post($parent_id));
    $children = SKST_Event_Dates::children($parent_id);
    skst_check(count(array_filter($children, function ($child) { return $child->post_status === 'private'; })) === 1, 'Removed date remained active.');

    $_POST['skst_event_dates'][0]['end'] = '2040-10-08T17:00';
    $before = get_post_meta($parent_id, '_skst_additional_dates', true);
    SKST_Event_Dates::save($parent_id, get_post($parent_id));
    skst_check(get_post_meta($parent_id, '_skst_additional_dates', true) === $before, 'Invalid input replaced valid dates.');
    delete_transient('skst_event_dates_error_1');
    $_POST = array();

    $events = tribe_get_events(array('post_status' => 'private', 'eventDisplay' => 'custom', 'start_date' => '2040-10-08 00:00:00', 'end_date' => '2040-10-08 23:59:59', 'post__in' => $child_ids));
    skst_check(count($events) === 1, 'TEC did not retrieve generated event on its date.');

    wp_trash_post($parent_id);
    foreach (SKST_Event_Dates::children($parent_id) as $child) {
        skst_check($child->post_status === 'trash', 'Child was not trashed with parent.');
    }
    wp_untrash_post($parent_id);
    $children = SKST_Event_Dates::children($parent_id);
    skst_check(count(array_filter($children, function ($child) use ($parent_id) { return $child->post_status === get_post_status($parent_id); })) === 1, 'Active dates were not restored.');
    wp_delete_post($parent_id, true);
    foreach ($child_ids as $child_id) {
        skst_check(get_post($child_id) === null, 'Generated event was orphaned.');
    }
    $parent_id = 0;
    echo "Passed: validation, create, update, deduplication, date removal, TEC query, trash, restore, deletion.\n";
} finally {
    $_POST = array();
    if ($parent_id) {
        foreach (SKST_Event_Dates::children($parent_id) as $child) {
            $child_ids[] = $child->ID;
        }
        wp_delete_post($parent_id, true);
    }
    foreach (array_unique($child_ids) as $child_id) {
        if (get_post($child_id)) {
            wp_delete_post($child_id, true);
        }
    }
}
