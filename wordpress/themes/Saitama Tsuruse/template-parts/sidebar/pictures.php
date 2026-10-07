<?php
/**
 * Sidebar for pictures post type.
 *
 * @package Saitama Tsuruse
 */

if (function_exists('Lightning_customize_widget_area_alert')) {
    Lightning_customize_widget_area_alert(TSURUSE_PICTURES_POST_TYPE);
}

$current_object   = get_queried_object();
$current_term_id  = $current_object instanceof WP_Term ? (int) $current_object->term_id : 0;
$current_taxonomy = $current_object instanceof WP_Term ? $current_object->taxonomy : '';
$current_post_ids = array();
$sections         = array();

foreach (tsuruse_event_group_taxonomies() as $taxonomy => $config) {
    $current_post_terms = is_singular(TSURUSE_PICTURES_POST_TYPE)
        ? tsuruse_get_event_taxonomy_terms($taxonomy, get_the_ID())
        : array();

    $current_post_ids[$taxonomy] = is_singular(TSURUSE_PICTURES_POST_TYPE)
        ? array_map('intval', wp_list_pluck($current_post_terms, 'term_id'))
        : array();

    $terms = tsuruse_get_sidebar_terms($taxonomy);

    if (!is_wp_error($terms) && !empty($terms)) {
        $sections[] = array(
            'taxonomy' => $taxonomy,
            'label'    => $config['label'],
            'terms'    => $terms,
        );
    }
}

$member_taxonomy = TSURUSE_PICTURE_MEMBER_TAG_TAXONOMY;
$member_config   = tsuruse_picture_member_tag_taxonomy_config();
$member_terms    = tsuruse_get_sidebar_terms($member_taxonomy);

$current_post_ids[$member_taxonomy] = is_singular(TSURUSE_PICTURES_POST_TYPE)
    ? array_map('intval', wp_list_pluck(tsuruse_get_event_taxonomy_terms($member_taxonomy, get_the_ID()), 'term_id'))
    : array();

if (!empty($member_terms)) {
    $sections[] = array(
        'taxonomy' => $member_taxonomy,
        'label'    => $member_config['label'],
        'terms'    => $member_terms,
    );
}
?>

<?php if (!empty($sections)) : ?>
    <aside class="widget widget_link_list tsuruse-event-nav-widget">
        <?php foreach ($sections as $section) : ?>
            <?php
            $current_ids_for_section = isset($current_post_ids[$section['taxonomy']]) ? $current_post_ids[$section['taxonomy']] : array();
            $section_has_current = ($current_taxonomy === $section['taxonomy']) || !empty($current_ids_for_section);
            ?>
            <section class="tsuruse-event-sidebar-section">
                <details class="tsuruse-event-sidebar-group<?php echo $section_has_current ? ' has-current' : ''; ?>" <?php echo $section_has_current ? 'open' : ''; ?>>
                    <summary class="tsuruse-event-sidebar-group__summary">
                        <h3 class="tsuruse-event-sidebar-section__title"><?php echo esc_html($section['label']); ?></h3>
                    </summary>

                    <ul class="tsuruse-event-sidebar-nav">
                        <?php foreach ($section['terms'] as $term) : ?>
                            <?php
                            $is_current = ($current_taxonomy === $section['taxonomy'] && $current_term_id === (int) $term->term_id)
                                || in_array((int) $term->term_id, $current_ids_for_section, true);
                            $term_link = tsuruse_get_sidebar_term_link($section['taxonomy'], $term);

                            if (is_wp_error($term_link) || empty($term_link)) {
                                continue;
                            }
                            ?>
                            <li class="tsuruse-event-sidebar-nav__item<?php echo $is_current ? ' is-current' : ''; ?>">
                                <a
                                    class="tsuruse-event-sidebar-nav__link"
                                    href="<?php echo esc_url($term_link); ?>"
                                    <?php echo $is_current ? 'aria-current="page"' : ''; ?>
                                >
                                    <span class="tsuruse-event-sidebar-nav__name"><?php echo esc_html($term->name); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            </section>
        <?php endforeach; ?>
    </aside>
<?php endif; ?>
