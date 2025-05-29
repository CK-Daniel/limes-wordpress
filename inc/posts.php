<?php
/**
 * Posts Template (Legacy File)
 *
 * This file is kept for backward compatibility.
 * Blog template functions have been moved to:
 * - inc/templates/blog-templates.php
 *
 * @package Limes
 * @deprecated Use limes_display_blog_section() from blog-templates.php instead
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Backward compatibility - include the original template logic
// This maintains the exact same functionality as before
if (!isset($cur_term)) {
    $args = array(
        'post_type'             => 'post',
        'posts_per_page'        => -1,
        'post_status'           => 'publish',
        'ignore_sticky_posts'   => 1,
    );
} else {
    $args = Array(
        'post_type' => 'post',
        'numberposts' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'category',
                'field' => 'term_id', 
                'terms' => $cur_term->term_id
            )
        )
    );
}

$items = get_posts($args);
if ($items):

wp_reset_postdata();
wp_reset_query();
?>

<section class="blog">
    <div class="section-inner">
        <div class="menu-blog">
            <div class="items">
                <?php
                    $terms = get_terms(array(
                        'taxonomy' => 'category',
                        'hide_empty' => false,
                        'exclude' => 1, 
                        'parent' => 0
                    ));
                    
                    foreach ($terms as $term):
                ?>
                    <a href="<?= get_term_link($term) ?>" class="item <?php if (isset($cur_term) && $cur_term->term_id == $term->term_id) echo "active" ?>">
                        <span><?= $term->name ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="boxes">
            <?php
                foreach ($items as $item) {
                    template_post($item->ID);
                }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="banner">
    <div class="section-inner">
        <div class="wrapper-banner">
            <div class="inner">
                <div class="part part-image">
                    <img src="<?= $td ?>/images/inner/pillow.png" alt="">
                </div>
                <div class="part part-text">
                    <div class="title-2-lines light">
                        <p class="line-1"><span>תכשיטי</span></p>
                        <p class="line-2">חינה מסורתיים</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
