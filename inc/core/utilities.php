<?php
/**
 * Utility Functions
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Make short excerpt from content
 */
function make_short($string, $num_of_words) {
    $no_tags = wp_strip_all_tags($string);
    return wp_trim_words($no_tags, $num_of_words);
}

/**
 * Get current template name
 */
function get_cur_template() {
    global $template;
    return basename($template);
}

/**
 * Update product price range for WooCommerce
 */
function limes_update_product_price_range($product) {
    if (!is_a($product, 'WC_Product')) {
        return;
    }
    
    global $wpdb;
    
    // Query to get the min and max _price values among all published products.
    $result = $wpdb->get_row("
        SELECT
            MIN( CAST(pm.meta_value AS DECIMAL(10,2)) ) AS min_price,
            MAX( CAST(pm.meta_value AS DECIMAL(10,2)) ) AS max_price
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_price'
    ");
    
    // Make sure we have valid results before updating the option.
    if ($result) {
        update_option('min_max_product_price', array(
            'min' => $result->min_price,
            'max' => $result->max_price,
        ));
    }
}
add_action('woocommerce_after_product_object_save', 'limes_update_product_price_range', 10, 1);

/**
 * Disable Contact Form 7 auto-formatting
 */
add_filter('wpcf7_autop_or_not', '__return_false');
