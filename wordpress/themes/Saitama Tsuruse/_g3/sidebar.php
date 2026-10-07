<?php
if (function_exists('tsuruse_is_picture_sidebar_context') && tsuruse_is_picture_sidebar_context()) {
    require get_stylesheet_directory() . '/template-parts/sidebar/pictures.php';
    return;
}

if (function_exists('tsuruse_is_post_sidebar_context') && tsuruse_is_post_sidebar_context()) {
    require get_stylesheet_directory() . '/template-parts/sidebar/post.php';
    return;
}

require get_parent_theme_file_path('/_g3/sidebar.php');
