<?php
/**
 * Custom Post Types
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Designer post type
 */
if (!function_exists('limes_designer_post_type')) :
    function limes_designer_post_type() {
        $labels = array(
            'name'                  => _x('מעצבים', 'Post Type General Name', 'limes'),
            'singular_name'         => _x('מעצב', 'Post Type Singular Name', 'limes'),
            'menu_name'             => __('מעצבים', 'limes'),
            'name_admin_bar'        => __('מעצבים', 'limes'),
            'archives'              => __('Item Archives', 'limes'),
            'attributes'            => __('Item Attributes', 'limes'),
            'parent_item_colon'     => __('Parent Item:', 'limes'),
            'all_items'             => __('All News', 'limes'),
            'add_new_item'          => __('Add New Item', 'limes'),
            'add_new'               => __('Add New Item', 'limes'),
            'new_item'              => __('New Item', 'limes'),
            'edit_item'             => __('Edit Item', 'limes'),
            'update_item'           => __('Update Item', 'limes'),
            'view_item'             => __('View Item', 'limes'),
            'view_items'            => __('View Feedbacks', 'limes'),
            'search_items'          => __('Search Item', 'limes'),
            'not_found'             => __('Not found', 'limes'),
            'not_found_in_trash'    => __('Not found in Trash', 'limes'),
            'featured_image'        => __('Featured Image', 'limes'),
            'set_featured_image'    => __('Set featured image', 'limes'),
            'remove_featured_image' => __('Remove featured image', 'limes'),
            'use_featured_image'    => __('Use as featured image', 'limes'),
            'insert_into_item'      => __('Insert into item', 'limes'),
            'uploaded_to_this_item' => __('Uploaded to this item', 'limes'),
            'items_list'            => __('News list', 'limes'),
            'items_list_navigation' => __('News list navigation', 'limes'),
            'filter_items_list'     => __('Filter items list', 'limes'),
        );
        
        $args = array(
            'label'                 => __('מעצבים', 'limes'),
            'description'           => __('מעצבים', 'limes'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'taxonomies'            => array(''),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
        );
        
        register_post_type('designer', $args);
    }
    add_action('init', 'limes_designer_post_type', 0);
endif;

/**
 * Get all designers by IDs
 */
function limes_get_all_designers($designers_ids) {
    $args = array(
        'post_type' => 'designer',
        'post__in'  => $designers_ids,
        'order_by'  => 'post__in',
    );
    $query_loop = new WP_Query($args);
    return $query_loop;
}
