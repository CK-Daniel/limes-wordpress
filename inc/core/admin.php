<?php
/**
 * Admin-specific Functions
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add ACF Options Page
 */
function limes_add_options_page() {
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page(array(
            'page_title' => 'הגדרות כלליות',
            'menu_title' => 'הגדרות כלליות',
            'menu_slug'  => 'theme-general-settings',
            'capability' => 'edit_posts',
            'redirect'   => false,
            'position'   => 2,
        ));
    }
}
add_action('init', 'limes_add_options_page');
