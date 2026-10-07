<?php
/**
 * Event archive loop item.
 *
 * @package Saitama Tsuruse
 */

$event            = tsuruse_get_event_details(get_the_ID());
$deadline         = tsuruse_event_get_deadline_label($event);
$badge_names      = tsuruse_get_event_badge_names(get_the_ID());
$summary          = has_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags(strip_shortcodes(get_the_content())), 44, '...');
$application_url  = !empty($event['application_url']) ? esc_url($event['application_url']) : '';
$card_class_names = 'tsuruse-event-card vk_post vk_post-col-xs-12 vk_post-col-sm-12 vk_post-col-lg-12';

if (!has_post_thumbnail()) {
    $card_class_names .= ' is-no-image';
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class($card_class_names); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <a class="tsuruse-event-card__image" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
            <?php the_post_thumbnail('large'); ?>
        </a>
    <?php endif; ?>

    <div class="tsuruse-event-card__body">
        <div class="tsuruse-event-card__meta">
            <?php foreach ($badge_names as $term_name) : ?>
                <span class="tsuruse-event-term"><?php echo esc_html($term_name); ?></span>
            <?php endforeach; ?>
        </div>

        <h2 class="tsuruse-event-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>

        <?php if (!empty($deadline)) : ?>
            <dl class="tsuruse-event-card__details">
                <div>
                    <dt>申込締切</dt>
                    <dd><?php echo esc_html($deadline); ?></dd>
                </div>
            </dl>
        <?php endif; ?>

        <?php if (!empty($summary)) : ?>
            <p class="tsuruse-event-card__summary"><?php echo esc_html($summary); ?></p>
        <?php endif; ?>

        <div class="tsuruse-event-card__actions">
            <a class="tsuruse-event-card__button" href="<?php the_permalink(); ?>">イベント詳細を見る</a>
            <?php if (!empty($application_url)) : ?>
                <a class="tsuruse-event-card__button is-secondary" href="<?php echo $application_url; ?>" target="_blank" rel="noopener noreferrer">申込ページへ</a>
            <?php endif; ?>
        </div>
    </div>
</article>
