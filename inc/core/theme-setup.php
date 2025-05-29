<?php
/**
 * Theme Setup and Support Features
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
if (!function_exists('limes_setup')) :
    function limes_setup() {
        /*
         * Make theme available for translation.
         * Translations can be filed in the /languages/ directory.
         */
        load_theme_textdomain('limes', get_template_directory() . '/languages');

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
         * Let WordPress manage the document title.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for Post Thumbnails on posts and pages.
         */
        add_theme_support('post-thumbnails');

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            )
        );

        // Set up the WordPress core custom background feature.
        add_theme_support(
            'custom-background',
            apply_filters(
                'limes_custom_background_args',
                array(
                    'default-color' => 'ffffff',
                    'default-image' => '',
                )
            )
        );

        // Add theme support for selective refresh for widgets.
        add_theme_support('customize-selective-refresh-widgets');

        /**
         * Add support for core custom logo.
         */
        add_theme_support(
            'custom-logo',
            array(
                'height'      => 250,
                'width'       => 250,
                'flex-width'  => true,
                'flex-height' => true,
            )
        );
    }
endif;
add_action('after_setup_theme', 'limes_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 */
function limes_content_width() {
    $GLOBALS['content_width'] = apply_filters('limes_content_width', 640);
}
add_action('after_setup_theme', 'limes_content_width', 0);

/**
 * Disable Gutenberg editor
 */
function limes_disable_gutenberg() {
    // Disable gutenberg for posts
    add_filter('use_block_editor_for_post', '__return_false', 10);
    // Disable gutenberg for post types
    add_filter('use_block_editor_for_post_type', '__return_false', 10);
}
add_action('init', 'limes_disable_gutenberg');

/**
 * Remove WordPress block library CSS
 */
function limes_remove_wp_block_library_css() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style'); // Remove WooCommerce block CSS
}
add_action('wp_enqueue_scripts', 'limes_remove_wp_block_library_css', 100);

/**
 * Hide editor for specific page templates
 */
function limes_hide_editor() {
    $templates = [
        'tmp-corian-colors.php', 
        'tmp-club.php', 
        'tmp-home.php', 
        'tmp-about.php', 
        'tmp-services.php', 
        'tmp-contact.php', 
        'tmp-testimonials.php', 
        'tmp-gallery.php'
    ];
    
    $template_file = basename(get_page_template());
    
    if (in_array($template_file, $templates)) {
        remove_post_type_support('page', 'editor');
    }
}
add_action('admin_head', 'limes_hide_editor');
