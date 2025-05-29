<?php
/**
 * Limes Theme Functions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
$upload_dir = wp_upload_dir();
$upload_dir = $upload_dir['basedir'] . '/';
define('LOG_DIR', $upload_dir);

if (!defined('_S_VERSION')) {
    define('_S_VERSION', '1.0.0');
}

/**
 * Load Core Theme Files
 */
require_once get_template_directory() . '/inc/core/theme-setup.php';
require_once get_template_directory() . '/inc/core/enqueue-scripts.php';
require_once get_template_directory() . '/inc/core/image-sizes.php';
require_once get_template_directory() . '/inc/core/menus.php';
require_once get_template_directory() . '/inc/core/post-types.php';
require_once get_template_directory() . '/inc/core/taxonomies.php';
require_once get_template_directory() . '/inc/core/utilities.php';
require_once get_template_directory() . '/inc/core/admin.php';

/**
 * Load Template Functions
 */
require_once get_template_directory() . '/inc/templates/product-templates.php';
require_once get_template_directory() . '/inc/templates/post-templates.php';
require_once get_template_directory() . '/inc/templates/blog-templates.php';

/**
 * Load Feature Files
 */
require_once get_template_directory() . '/inc/features/breadcrumbs.php';

/**
 * Load WooCommerce Integration
 */
if (class_exists('WooCommerce')) {
    // Load new modular WooCommerce system
    require_once get_template_directory() . '/inc/woocommerce/woocommerce-integration.php';
    
    // Load legacy files for backward compatibility (will be phased out)
    require_once get_template_directory() . '/inc/woocommerce.php';
    require_once get_template_directory() . '/inc/woo-product-page.php';
    require_once get_template_directory() . '/inc/woo-cart-calculations.php';
    require_once get_template_directory() . '/inc/woo-simple-product-customization.php';
    require_once get_template_directory() . '/inc/woo-final-price-display.php';
    // Note: woo-cart-order-display.php is now replaced by modular system
}

/**
 * Load Legacy Files (to be refactored)
 */
require_once get_template_directory() . '/functions-loaders.php';

/**
 * Backward Compatibility
 * 
 * These functions are kept for backward compatibility
 * and will be moved to appropriate modules in future updates
 */

// Keep the old function names as aliases for backward compatibility
if (!function_exists('add_theme_scripts')) {
    function add_theme_scripts() {
        // This function is now handled by limes_enqueue_scripts() in inc/core/enqueue-scripts.php
        // Keeping this empty function to prevent errors if called directly
    }
}

if (!function_exists('load_admin_style')) {
    function load_admin_style() {
        // This function is now handled by limes_admin_styles() in inc/core/enqueue-scripts.php
        // Keeping this empty function to prevent errors if called directly
    }
}
