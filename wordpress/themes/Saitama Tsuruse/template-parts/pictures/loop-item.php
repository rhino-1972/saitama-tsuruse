<?php
/**
 * Pictures archive loop item.
 *
 * @package Saitama_Tsuruse
 */

use VektorInc\VK_Component\VK_Component_Posts;

$options = array(
    'layout'                     => 'card-horizontal',
    'display_image'              => true,
    'display_image_overlay_term' => true,
    'display_excerpt'            => false,
    'display_date'               => true,
    'display_new'                => true,
    'display_taxonomies'         => false,
    'display_btn'                => true,
    'image_default_url'          => false,
    'overlay'                    => false,
    'btn_text'                   => '続きを読む',
    'btn_align'                  => 'text-right',
    'new_text'                   => '新着!!',
    'new_date'                   => 7,
    'class_outer'                => 'vk_post-col-xs-12 vk_post-col-sm-12 vk_post-col-lg-12 tsuruse-post-archive-item tsuruse-picture-archive-item',
    'class_title'                => '',
    'body_prepend'               => '',
    'body_append'                => '',
);

VK_Component_Posts::the_view($post, $options);
