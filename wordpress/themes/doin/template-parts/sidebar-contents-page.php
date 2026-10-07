<?php
/**
 * Keep Lightning's page navigation except for contact and its completion pages.
 */
if (!defined('ABSPATH')) {
    exit;
}

$doin_contact_page = get_page_by_path('contact');
if ($doin_contact_page && is_page()
    && (get_queried_object_id() === (int) $doin_contact_page->ID
        || in_array((int) $doin_contact_page->ID, get_post_ancestors(get_queried_object_id()), true))) {
    return;
}

$doin_generation = lightning_is_g3() ? '_g3' : '_g2';
$doin_parent_sidebar = get_template_directory() . '/' . $doin_generation . '/template-parts/sidebar-contents-page.php';
if (is_file($doin_parent_sidebar)) {
    load_template($doin_parent_sidebar, false);
}
