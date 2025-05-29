<?php
/**
 * Custom Taxonomies
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Product Topics Category taxonomy
 */
if (!function_exists('limes_product_topics_taxonomy')) {
    function limes_product_topics_taxonomy() {
        $labels = array(
            'name'                       => _x('נושאים', 'Taxonomy General Name', 'limes'),
            'singular_name'              => _x('קטגוריה', 'Taxonomy Singular Name', 'limes'),
            'menu_name'                  => __('נושאים', 'limes'),
            'all_items'                  => __('כל הנושאים', 'limes'),
            'parent_item'                => __('Parent Item', 'limes'),
            'parent_item_colon'          => __('Parent Item:', 'limes'),
            'new_item_name'              => __('New Item Name', 'limes'),
            'add_new_item'               => __('Add New Item', 'limes'),
            'edit_item'                  => __('Edit Item', 'limes'),
            'update_item'                => __('Update Item', 'limes'),
            'view_item'                  => __('View Item', 'limes'),
            'separate_items_with_commas' => __('Separate items with commas', 'limes'),
            'add_or_remove_items'        => __('Add or remove items', 'limes'),
            'choose_from_most_used'      => __('Choose from the most used', 'limes'),
            'popular_items'              => __('Popular Items', 'limes'),
            'search_items'               => __('Search Items', 'limes'),
            'not_found'                  => __('Not Found', 'limes'),
            'no_terms'                   => __('No items', 'limes'),
            'items_list'                 => __('Items list', 'limes'),
            'items_list_navigation'      => __('Items list navigation', 'limes'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
        );
        
        register_taxonomy('product_topcics_cat', array('product'), $args);
    }
    add_action('init', 'limes_product_topics_taxonomy', 0);
}
