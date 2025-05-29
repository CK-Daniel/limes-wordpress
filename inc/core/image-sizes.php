<?php
/**
 * Custom Image Sizes
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom image sizes
 */
function limes_register_image_sizes() {
    // Product images
    add_image_size('thumb-product', 570, 380, false);
    add_image_size('thumb-product-gal', 136, 100, true);
    add_image_size('big-product-gal', 580, 400, false);

    // Post images
    add_image_size('big-post', 740, 9999, false);
    add_image_size('thumb-post', 433, 285, true);
    add_image_size('thumb-gal', 443, 297, true);

    // Designer images
    add_image_size('thumb-designer', 140, 140, true);
    
    // Institution logos
    add_image_size('insu_logo', 220, 90, false);
}
add_action('after_setup_theme', 'limes_register_image_sizes');

/**
 * Add SVG support to media uploads
 */
function limes_add_svg_support($file_types) {
    $new_filetypes = array();
    $new_filetypes['svg'] = 'image/svg';
    $file_types = array_merge($file_types, $new_filetypes);
    return $file_types;
}
add_action('upload_mimes', 'limes_add_svg_support');
