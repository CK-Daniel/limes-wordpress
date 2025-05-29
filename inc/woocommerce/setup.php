<?php
/**
 * WooCommerce Setup and Configuration
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce setup function.
 */
function limes_woocommerce_setup() {
    add_theme_support(
        'woocommerce',
        array(
            'thumbnail_image_width' => 150,
            'single_image_width'    => 300,
            'product_grid'          => array(
                'default_rows'    => 3,
                'min_rows'        => 1,
                'default_columns' => 4,
                'min_columns'     => 1,
                'max_columns'     => 6,
            ),
        )
    );
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'limes_woocommerce_setup');

/**
 * WooCommerce specific scripts & stylesheets.
 */
function limes_woocommerce_scripts() {
    wp_enqueue_style('limes-woocommerce-style', get_template_directory_uri() . '/woocommerce.css', array(), _S_VERSION);

    $font_path   = WC()->plugin_url() . '/assets/fonts/';
    $inline_font = '@font-face {
            font-family: "star";
            src: url("' . $font_path . 'star.eot");
            src: url("' . $font_path . 'star.eot?#iefix") format("embedded-opentype"),
                url("' . $font_path . 'star.woff") format("woff"),
                url("' . $font_path . 'star.ttf") format("truetype"),
                url("' . $font_path . 'star.svg#star") format("svg");
            font-weight: normal;
            font-style: normal;
        }';

    wp_add_inline_style('limes-woocommerce-style', $inline_font);
}
add_action('wp_enqueue_scripts', 'limes_woocommerce_scripts');

/**
 * Disable the default WooCommerce stylesheet.
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Add 'woocommerce-active' class to the body tag.
 */
function limes_woocommerce_active_body_class($classes) {
    $classes[] = 'woocommerce-active';
    return $classes;
}
add_filter('body_class', 'limes_woocommerce_active_body_class');

/**
 * Related Products Args.
 */
function limes_woocommerce_related_products_args($args) {
    $defaults = array(
        'posts_per_page' => 3,
        'columns'        => 3,
    );

    $args = wp_parse_args($defaults, $args);
    return $args;
}
add_filter('woocommerce_output_related_products_args', 'limes_woocommerce_related_products_args');

/**
 * Remove default WooCommerce wrapper.
 */
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

/**
 * Before Content wrapper.
 */
if (!function_exists('limes_woocommerce_wrapper_before')) {
    function limes_woocommerce_wrapper_before() {
        ?>
        <main id="primary" class="site-main">
        <?php
    }
}
add_action('woocommerce_before_main_content', 'limes_woocommerce_wrapper_before');

/**
 * After Content wrapper.
 */
if (!function_exists('limes_woocommerce_wrapper_after')) {
    function limes_woocommerce_wrapper_after() {
        ?>
        </main><!-- #main -->
        <?php
    }
}
add_action('woocommerce_after_main_content', 'limes_woocommerce_wrapper_after');

/**
 * Cart Fragments.
 */
if (!function_exists('limes_woocommerce_cart_link_fragment')) {
    function limes_woocommerce_cart_link_fragment($fragments) {
        ob_start();
        limes_woocommerce_cart_link();
        $fragments['a.cart-contents'] = ob_get_clean();
        return $fragments;
    }
}
add_filter('woocommerce_add_to_cart_fragments', 'limes_woocommerce_cart_link_fragment');

/**
 * Cart Link.
 */
if (!function_exists('limes_woocommerce_cart_link')) {
    function limes_woocommerce_cart_link() {
        ?>
        <a class="cart-contents" href="<?php echo esc_url(wc_get_cart_url()); ?>" title="<?php esc_attr_e('View your shopping cart', 'limes'); ?>">
            <?php
            $item_count_text = sprintf(
                /* translators: number of items in the mini cart. */
                _n('%d item', '%d items', WC()->cart->get_cart_contents_count(), 'limes'),
                WC()->cart->get_cart_contents_count()
            );
            ?>
            <span class="amount"><?php echo wp_kses_data(WC()->cart->get_cart_subtotal()); ?></span> 
            <span class="count"><?php echo esc_html($item_count_text); ?></span>
        </a>
        <?php
    }
}

/**
 * Display Header Cart.
 */
if (!function_exists('limes_woocommerce_header_cart')) {
    function limes_woocommerce_header_cart() {
        if (is_cart()) {
            $class = 'current-menu-item';
        } else {
            $class = '';
        }
        ?>
        <ul id="site-header-cart" class="site-header-cart">
            <li class="<?php echo esc_attr($class); ?>">
                <?php limes_woocommerce_cart_link(); ?>
            </li>
            <li>
                <?php
                $instance = array(
                    'title' => '',
                );
                the_widget('WC_Widget_Cart', $instance);
                ?>
            </li>
        </ul>
        <?php
    }
}
