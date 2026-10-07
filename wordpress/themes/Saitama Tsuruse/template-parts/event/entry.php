<?php
/**
 * Event singular entry.
 *
 * @package Saitama Tsuruse
 */

$event           = tsuruse_get_event_details(get_the_ID());
$deadline        = tsuruse_event_get_deadline_label($event);
$grouped_tags    = tsuruse_get_event_group_term_names(get_the_ID());
$term_names      = tsuruse_get_event_term_names(get_the_ID());
$badge_names     = tsuruse_get_event_badge_names(get_the_ID());
$has_group_tags  = false;
$application_url = !empty($event['application_url']) ? esc_url($event['application_url']) : '';

foreach ($grouped_tags as $names) {
    if (!empty($names)) {
        $has_group_tags = true;
        break;
    }
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('entry entry-full tsuruse-event-entry'); ?>>
    <header class="entry-header tsuruse-event-entry__header">
        <h1 class="entry-title"><?php the_title(); ?></h1>

        <?php if (!empty($badge_names)) : ?>
            <div class="tsuruse-event-entry__terms">
                <?php foreach ($badge_names as $term_name) : ?>
                    <span class="tsuruse-event-term"><?php echo esc_html($term_name); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (has_excerpt()) : ?>
            <p class="tsuruse-event-entry__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
        <?php endif; ?>
    </header>

    <?php if (has_post_thumbnail()) : ?>
        <div class="tsuruse-event-entry__image">
            <?php the_post_thumbnail('full'); ?>
        </div>
    <?php endif; ?>

    <?php if ($has_group_tags || !empty($term_names) || !empty($deadline) || !empty($application_url)) : ?>
        <div class="tsuruse-event-entry__info">
            <?php foreach (tsuruse_event_group_taxonomies() as $taxonomy => $config) : ?>
                <?php if (!empty($grouped_tags[$taxonomy])) : ?>
                    <section class="tsuruse-event-panel">
                        <h2><?php echo esc_html($config['label']); ?></h2>
                        <p><?php echo esc_html(implode(' / ', $grouped_tags[$taxonomy])); ?></p>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($term_names)) : ?>
                <section class="tsuruse-event-panel">
                    <h2>分類</h2>
                    <p><?php echo esc_html(implode(' / ', $term_names)); ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($deadline)) : ?>
                <section class="tsuruse-event-panel">
                    <h2>申込締切</h2>
                    <p><?php echo esc_html($deadline); ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($application_url)) : ?>
                <section class="tsuruse-event-panel">
                    <h2>申込</h2>
                    <p><a href="<?php echo $application_url; ?>" target="_blank" rel="noopener noreferrer">申込ページを開く</a></p>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php do_action('lightning_entry_body_before'); ?>

    <div class="entry-body">
        <?php do_action('lightning_entry_body_prepend'); ?>
        <?php the_content(); ?>
        <?php do_action('lightning_entry_body_apppend'); ?>
    </div>

    <?php do_action('lightning_entry_body_after'); ?>

    <?php
    wp_link_pages(
        array(
            'before'      => '<nav class="page-link"><dl><dt>Pages :</dt><dd>',
            'after'       => '</dd></dl></nav>',
            'link_before' => '<span class="page-numbers">',
            'link_after'  => '</span>',
        )
    );
    ?>
</article>
