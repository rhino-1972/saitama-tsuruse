<?php
/**
 * Home post list item.
 *
 * @package Saitama_Tsuruse
 */

use VektorInc\VK_Component\VK_Component_Posts;

if (is_home()) :
    $is_new = tsuruse_is_recent_post(get_the_ID(), 7);
    ?>
    <article <?php post_class('tsuruse-home-post-list__item'); ?>>
        <a class="tsuruse-home-post-list__link" href="<?php the_permalink(); ?>">
            <time class="tsuruse-home-post-list__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                <?php echo esc_html(get_the_date('Y.m.d')); ?>
            </time>
            <span class="tsuruse-home-post-list__title">
                <span class="tsuruse-home-post-list__title-text"><?php the_title(); ?></span>
                <?php if ($is_new) : ?>
                    <span class="tsuruse-home-post-list__new" aria-label="新着">NEW!</span>
                <?php endif; ?>
            </span>
        </a>
    </article>
    <?php
    return;
endif;

$is_new = tsuruse_is_post_archive_query()
    ? tsuruse_is_recent_post_within_months(get_the_ID(), 1)
    : tsuruse_is_recent_post(get_the_ID(), 7);
?>
<article <?php post_class('tsuruse-post-archive-list__item'); ?>>
    <a class="tsuruse-post-archive-list__link" href="<?php the_permalink(); ?>">
        <time class="tsuruse-post-archive-list__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
            <?php echo esc_html(get_the_date('Y年n月j日')); ?>
        </time>
        <span class="tsuruse-post-archive-list__title">
            <span class="tsuruse-post-archive-list__title-text"><?php the_title(); ?></span>
            <?php if ($is_new) : ?>
                <span class="vk_post_title_new tsuruse-post-archive-list__new" aria-label="新着">新着!!</span>
            <?php endif; ?>
        </span>
    </a>
</article>
