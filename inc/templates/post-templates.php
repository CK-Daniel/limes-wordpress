<?php
/**
 * Post Template Functions
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display post box template
 */
function template_post_box($id, $tag = "p") {
    $item = get_post($id);
    $image = get_the_post_thumbnail_url($item, 'thumb-post');
    
    if (!$image) {
        $image = get_field("general", 'options')['image-placeholder']['sizes']['thumb-post'];
    }
    ?>
    <a class="box" href="<?= get_permalink($item) ?>">
        <div class="inner">
            <div class="image">
                <img src="<?= $image ?>" alt="<?= $item->post_title ?>" title="<?= $item->post_title ?>">
            </div>
            <div class="text">
                <<?= $tag ?> class="title"><?= $item->post_title ?></<?= $tag ?>>
                <p class="short"><?= make_short($item->post_content, 20) ?></p>

                <div class="button-underline-hover dark">
                    <span>המשך קריאה</span>
                </div>
            </div>
        </div>
    </a>
    <?php 
}

/**
 * Alias for backward compatibility
 */
function template_post($id, $tag = "p") {
    template_post_box($id, $tag);
}
