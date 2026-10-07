<?php
/** Run with Local PHP and its mysqli.default_socket configured. */
$_SERVER['HTTP_HOST'] = 'wordpress.local';
$_SERVER['REQUEST_URI'] = '/saitamatsuruse-douin/';
require '/Users/toshiyuki/Local Sites/shorinjikempo/app/public/wp-load.php';
wp_set_current_user(1);
if (get_current_blog_id() !== 3 || !function_exists('eo_insert_event')) {
    throw new RuntimeException('Event Organiser on the doin site is required.');
}
$created = array();
function skst_eo_assert($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
try {
    $term = get_term_by('name', DOIN_ANNUAL_CATEGORY_NAME, 'event-category');
    skst_eo_assert($term instanceof WP_Term, 'Missing annual category');
    $zone = eo_get_blog_timezone();
    for ($index = 1; $index <= 11; $index++) {
        $id = eo_insert_event(array('post_title' => 'SKST EO TEST ' . $index, 'post_status' => 'publish', 'tax_input' => array('event-category' => array($term->term_id))), array('start' => new DateTime("2040-01-{$index} 18:30:00", $zone), 'end' => new DateTime("2040-01-{$index} 20:00:00", $zone), 'schedule' => 'once'));
        skst_eo_assert(!is_wp_error($id), 'Insert failed');
        $created[] = $id;
    }
    $annual = doin_get_annual_events(10);
    skst_eo_assert(count($annual) === 10, 'Expected ten annual occurrences');
    $dates = array_map(function ($event) { return eo_get_the_start('Y-m-d', $event->ID, $event->occurrence_id); }, $annual);
    $sorted = $dates;
    rsort($sorted);
    skst_eo_assert($dates === $sorted && $dates[0] === '2040-01-11', 'Annual order must be descending');
    $id = $created[0];
    $result = eo_update_event($id, array('schedule' => 'custom', 'include' => array(new DateTime('2040-02-01 18:30:00', $zone), new DateTime('2040-02-08 18:30:00', $zone))));
    skst_eo_assert(!is_wp_error($result) && count(eo_get_the_occurrences_of($id)) === 3, 'Multiple dates not saved');
    eo_update_event($id, array('include' => array(new DateTime('2040-02-08 18:30:00', $zone))));
    skst_eo_assert(count(eo_get_the_occurrences_of($id)) === 2, 'Date removal failed');
    $occurrences = eo_get_the_occurrences_of($id);
    skst_eo_assert(end($occurrences)['start']->format('H:i') === '18:30', 'Start time not retained');
    echo "PASS: annual category, descending order, limit 10, multiple dates, removal, time preservation\n";
} finally {
    foreach ($created as $id) { wp_delete_post($id, true); }
}
