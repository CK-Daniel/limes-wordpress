<?php
/**
 * Cart Data Handler
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cart data handler class
 */
class Limes_Cart_Data_Handler {

    /**
     * Initialize cart data handling
     */
    public static function init() {
        add_filter('woocommerce_add_cart_item_data', array(__CLASS__, 'save_custom_dimensions'), 15, 3);
        add_filter('woocommerce_add_cart_item_data', array(__CLASS__, 'save_installation_mechanism'), 20, 3);
        add_filter('woocommerce_add_cart_item_data', array(__CLASS__, 'store_minimum_price_data'), 30, 3);
    }

    /**
     * Save custom dimensions fields to cart
     *
     * @param array $cart_item_data Cart item data
     * @param int $product_id Product ID
     * @param int $variation_id Variation ID
     * @return array Modified cart item data
     */
    public static function save_custom_dimensions($cart_item_data, $product_id, $variation_id = 0) {
        $has_width = isset($_POST['prod_width']) && $_POST['prod_width'] !== '';
        $has_height = isset($_POST['prod_height']) && $_POST['prod_height'] !== '';
        $has_coverage = isset($_POST['prod_coverage']) && $_POST['prod_coverage'] !== '';
        $has_rolls_needed = isset($_POST['prod_rolls_needed']);

        // Save dimensions
        if ($has_width) {
            $cart_item_data['prod_width'] = sanitize_text_field($_POST['prod_width']);
        }
        if ($has_height) {
            $cart_item_data['prod_height'] = sanitize_text_field($_POST['prod_height']);
        }
        if ($has_coverage) {
            $cart_item_data['prod_coverage'] = sanitize_text_field($_POST['prod_coverage']);
        }
        if ($has_rolls_needed) {
            $cart_item_data['rolls_needed'] = absint($_POST['prod_rolls_needed']);
        }

        // Store base price for calculations
        $product_for_price = wc_get_product($variation_id ? $variation_id : $product_id);
        if (($has_width || $has_height || $has_coverage) && $product_for_price) {
            $cart_item_data['base_price'] = $product_for_price->get_price('edit');
        }

        return $cart_item_data;
    }

    /**
     * Save installation mechanism fields to cart
     *
     * @param array $cart_item_data Cart item data
     * @param int $product_id Product ID
     * @param int $variation_id Variation ID
     * @return array Modified cart item data
     */
    public static function save_installation_mechanism($cart_item_data, $product_id, $variation_id = 0) {
        if (isset($_POST['prod_radio-gr1'])) {
            $cart_item_data['prod_radio-gr1'] = sanitize_text_field($_POST['prod_radio-gr1']);
        }
        if (isset($_POST['prod_radio-gr2'])) {
            $cart_item_data['prod_radio-gr2'] = sanitize_text_field($_POST['prod_radio-gr2']);
        }

        // Ensure base_price is set if not already set
        if (empty($cart_item_data['base_price'])) {
            $product_for_price = wc_get_product($variation_id ? $variation_id : $product_id);
            if ($product_for_price) {
                $cart_item_data['base_price'] = $product_for_price->get_price('edit');
            }
        }

        return $cart_item_data;
    }

    /**
     * Store minimum price data for later enforcement
     *
     * @param array $cart_item_data Cart item data
     * @param int $product_id Product ID
     * @param int $variation_id Variation ID
     * @return array Modified cart item data
     */
    public static function store_minimum_price_data($cart_item_data, $product_id, $variation_id = 0) {
        $min_price = get_field('pro_order_min_price', $product_id);
        if (!empty($min_price)) {
            $cart_item_data['min_price_threshold'] = (float) $min_price;
        }
        return $cart_item_data;
    }

    /**
     * Get cart item dimensions
     *
     * @param array $cart_item Cart item data
     * @return array Dimensions data
     */
    public static function get_cart_item_dimensions($cart_item) {
        return array(
            'width' => isset($cart_item['prod_width']) ? (float) $cart_item['prod_width'] : 0,
            'height' => isset($cart_item['prod_height']) ? (float) $cart_item['prod_height'] : 0,
            'coverage' => isset($cart_item['prod_coverage']) ? (float) $cart_item['prod_coverage'] : 0,
            'rolls_needed' => isset($cart_item['rolls_needed']) ? (int) $cart_item['rolls_needed'] : 0
        );
    }

    /**
     * Get cart item installation data
     *
     * @param array $cart_item Cart item data
     * @return array Installation data
     */
    public static function get_cart_item_installation($cart_item) {
        return array(
            'mechanism_side' => isset($cart_item['prod_radio-gr2']) ? $cart_item['prod_radio-gr2'] : '',
            'installation_type' => isset($cart_item['prod_radio-gr1']) ? $cart_item['prod_radio-gr1'] : ''
        );
    }

    /**
     * Get cart item calculation data for price calculator
     *
     * @param array $cart_item Cart item data
     * @return array Calculation data
     */
    public static function get_calculation_data($cart_item) {
        $dimensions = self::get_cart_item_dimensions($cart_item);
        $product_id_for_acf = $cart_item['variation_id'] ? $cart_item['product_id'] : $cart_item['product_id'];
        
        return array(
            'product_id' => $cart_item['product_id'],
            'variation_id' => $cart_item['variation_id'],
            'base_price' => isset($cart_item['base_price']) ? (float) $cart_item['base_price'] : 0,
            'product_type' => get_field('product_type_dimensions', $product_id_for_acf),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'coverage' => $dimensions['coverage'],
            'addons' => isset($cart_item['addons']) ? $cart_item['addons'] : array(),
            'quantity' => $cart_item['quantity']
        );
    }
}

// Initialize the cart data handler
Limes_Cart_Data_Handler::init();
