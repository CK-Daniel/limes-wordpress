<?php
/**
 * Product Template Functions
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display product box template
 */
function template_product_box($id, $tag = "p") {
    $td = get_template_directory_uri();
    $product = wc_get_product($id);
    $image = get_the_post_thumbnail_url($product->get_id(), 'thumb-product');
    
    if (!$image) {
        $image = get_field("general", 'options')['image-placeholder']['sizes']['thumb-product'];
    }
    ?>
    <div class="box box-product">
        <div class="inner">
            <div class="like">
                <?php echo do_shortcode("[wp_ulike id='$id']"); ?>
            </div>
            <?php if ($product->is_on_sale()) : ?>
            <div class="sale">
                <span>במבצע</span>
            </div>
            <?php endif; ?>
            <a class="image" href="<?= get_permalink($product->get_id()) ?>">
                <img src="<?= $image ?>" alt="<?= $product->get_name() ?>" title="<?= $product->get_name() ?>">
            </a>
            <div class="info">
                <<?= $tag ?> class="title"><?= $product->get_name() ?></<?= $tag ?>>
                <div class="price">
                    <span class="from_span">החל מ- </span><?= $product->get_price_html(); ?>
                </div>
            </div>
            <?php if ($product->is_type('variable')) : ?>
            <div class="pro_link">
                <a class="button" href="<?= get_permalink($product->get_id()) ?>">
                    <span>צפה במוצר</span>
                </a>
            </div>
            <?php endif; ?>
            <?php if ($product->is_type('simple')) : ?>
            <div class="wrap_btns">
                <a class="button" href="<?= get_permalink($product->get_id()) ?>">
                    <span>צפה במוצר</span>
                </a>
                <a href="#" class="buy-now-button" data-pid="<?php echo $product->get_id(); ?>">לביצוע הזמנה</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
