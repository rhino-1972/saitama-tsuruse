<?php
/**
 * Sidebar for post.
 *
 * @package Saitama Tsuruse
 */

if (function_exists('Lightning_customize_widget_area_alert')) {
    Lightning_customize_widget_area_alert('post');
}

$current_object          = get_queried_object();
$current_category_id     = $current_object instanceof WP_Term && 'category' === $current_object->taxonomy ? (int) $current_object->term_id : 0;
$current_post_id         = is_singular('post') ? (int) get_the_ID() : 0;
$current_post_categories = $current_post_id > 0 ? get_the_category($current_post_id) : array();
$current_category_ids    = !empty($current_post_categories) && !is_wp_error($current_post_categories)
    ? array_map('intval', wp_list_pluck($current_post_categories, 'term_id'))
    : array();
$sections                = tsuruse_get_post_sidebar_sections();

if (!empty($sections)) :
    ?>
    <aside class="widget widget_link_list tsuruse-event-nav-widget tsuruse-post-nav-widget">
        <?php foreach ($sections as $section) : ?>
            <?php
            $term = $section['term'];
            $posts = $section['posts'];
            $section_has_current = $current_category_id === (int) $term->term_id
                || in_array((int) $term->term_id, $current_category_ids, true);
            ?>
            <section class="tsuruse-event-sidebar-section">
                <details class="tsuruse-event-sidebar-group<?php echo $section_has_current ? ' has-current' : ''; ?>" <?php echo $section_has_current ? 'open' : ''; ?>>
                    <summary class="tsuruse-event-sidebar-group__summary">
                        <h3 class="tsuruse-event-sidebar-section__title"><?php echo esc_html($term->name); ?></h3>
                    </summary>

                    <ul class="tsuruse-event-sidebar-nav">
                        <?php foreach ($posts as $post_item) : ?>
                            <?php
                            $is_current = $current_post_id === (int) $post_item->ID;
                            $post_link  = get_permalink($post_item);

                            if (!$post_link) {
                                continue;
                            }
                            ?>
                            <li class="tsuruse-event-sidebar-nav__item<?php echo $is_current ? ' is-current' : ''; ?>">
                                <a
                                    class="tsuruse-event-sidebar-nav__link"
                                    href="<?php echo esc_url($post_link); ?>"
                                    <?php echo $is_current ? 'aria-current="page"' : ''; ?>
                                >
                                    <span class="tsuruse-event-sidebar-nav__name"><?php echo esc_html(get_the_title($post_item)); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            </section>
        <?php endforeach; ?>
    </aside>
    <?php
    return;
endif;

$parent_template = trailingslashit(get_template_directory()) . '_g3/template-parts/sidebar-contents-post.php';

if (file_exists($parent_template)) {
    require $parent_template;
}
