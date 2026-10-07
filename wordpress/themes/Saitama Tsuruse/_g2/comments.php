<?php
/**
 * Collapsible comments template for Lightning G2.
 *
 * @package Saitama Tsuruse
 */

if (post_password_required()) {
    return;
}

if (!post_type_supports(get_post_type(), 'comments')) {
    return;
}

global $post;

if (!comments_open($post->ID) && !have_comments()) {
    return;
}

$form_args = array(
    'title_reply'        => '',
    'title_reply_before' => '',
    'title_reply_after'  => '',
    'class_form'         => 'comment-form tsuruse-comment-form',
);
?>
<div id="comments" class="comments-area tsuruse-comments-area">
    <details class="tsuruse-comments-accordion">
        <summary class="tsuruse-comments-accordion__summary">
            <span class="tsuruse-comments-accordion__title">コメントを残す</span>
        </summary>

        <div class="tsuruse-comments-accordion__body">
            <?php if (have_comments()) : ?>
                <section class="tsuruse-comments-thread">
                    <h3 class="tsuruse-comments-thread__title">コメント</h3>

                    <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
                        <nav id="comment-nav-above" class="navigation comment-navigation" aria-label="コメントナビゲーション">
                            <div class="nav-links">
                                <div class="nav-previous"><?php previous_comments_link(__('Older Comments', 'lightning')); ?></div>
                                <div class="nav-next"><?php next_comments_link(__('Newer Comments', 'lightning')); ?></div>
                            </div>
                        </nav>
                    <?php endif; ?>

                    <ol class="comment-list">
                        <?php
                        wp_list_comments(
                            array(
                                'style'      => 'ol',
                                'short_ping' => true,
                            )
                        );
                        ?>
                    </ol>

                    <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
                        <nav id="comment-nav-below" class="navigation comment-navigation" aria-label="コメントナビゲーション">
                            <div class="nav-links">
                                <div class="nav-previous"><?php previous_comments_link(__('Older Comments', 'lightning')); ?></div>
                                <div class="nav-next"><?php next_comments_link(__('Newer Comments', 'lightning')); ?></div>
                            </div>
                        </nav>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!comments_open() && '0' !== get_comments_number()) : ?>
                <p class="no-comments"><?php _e('Comments are closed.', 'lightning'); ?></p>
            <?php else : ?>
                <?php comment_form($form_args); ?>
            <?php endif; ?>
        </div>
    </details>
</div>
