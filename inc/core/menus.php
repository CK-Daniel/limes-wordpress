<?php
/**
 * Menu Registration and Functions
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom menus
 */
function limes_register_menus() {
    register_nav_menus(
        array(
            'top-menu' => __('תפריט עליון'),
            'mobile-menu' => __('תפריט מובייל'),
        )
    );
}
add_action('init', 'limes_register_menus');

/**
 * Menu shortcode
 */
function limes_menu_shortcode($atts, $content = null) {
    extract(shortcode_atts(array('name' => null), $atts));
    return wp_nav_menu(array('menu' => $name, 'echo' => false));
}
add_shortcode('menu', 'limes_menu_shortcode');
