<?php
/**
 * Cart Price Updater
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cart price updater class
 */
class Limes_Cart_Price_Updater {

    /**
     * Initialize cart price updating
     */
    public static function init() {
        add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'update_cart_prices'), 25, 1);
        add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'enforce_minimum_prices'), 30, 1);
    }

    /**
     * Update cart item prices based on dimensions and addons
     *
     * @param WC_Cart $cart Cart object
     */
    public static function update_cart_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!$cart instanceof WC_Cart) {
            $cart = WC()->cart;
            if (!$cart) return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Skip if no base price stored
            if (!isset($cart_item['base_price']) || $cart_item['base_price'] === '') {
                continue;
            }

            try {
                // Get calculation data
                $calculation_data = Limes_Cart_Data_Handler::get_calculation_data($cart_item);
                
                // Calculate price using the main calculator
                $result = Limes_Price_Calculator::calculate_price($calculation_data);
                
                // Update cart item with calculated price
                $cart_item['data']->set_price($result['total_price']);
                
                // Store rolls needed for roll products
                if ($calculation_data['product_type'] === 'roll' && !empty($result['calculations'])) {
                    foreach ($result['calculations'] as $calc) {
                        if ($calc['type'] === 'rolls_needed') {
                            WC()->cart->cart_contents[$cart_item_key]['rolls_needed'] = $calc['value'];
                            break;
                        }
                    }
                }

            } catch (Exception $e) {
                // Log error and use base price as fallback
                wc_get_logger()->error(
                    sprintf('Price calculation error for cart item %s: %s', $cart_item_key, $e->getMessage()),
                    array('source' => 'limes-cart-pricing')
                );
                
                $cart_item['data']->set_price($cart_item['base_price']);
            }
        }
    }

    /**
     * Enforce minimum prices for cart items
     *
     * @param WC_Cart $cart Cart object
     */
    public static function enforce_minimum_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!$cart instanceof WC_Cart) {
            $cart = WC()->cart;
            if (!$cart) return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Skip if no minimum price threshold
            if (!isset($cart_item['min_price_threshold'])) {
                continue;
            }

            $min_price = $cart_item['min_price_threshold'];
            $current_price = $cart_item['data']->get_price();
            $quantity = $cart_item['quantity'];

            // Calculate total price for this item
            $total_price = $current_price * $quantity;

            // If total price is below minimum, adjust the per-item price
            if ($total_price < $min_price) {
                $adjusted_price = $min_price / $quantity;
                $cart_item['data']->set_price($adjusted_price);

                // Mark that minimum price was applied
                WC()->cart->cart_contents[$cart_item_key]['min_price_applied'] = true;
            }
        }
    }

    /**
     * Get detailed price breakdown for a cart item
     *
     * @param array $cart_item Cart item data
     * @return array Price breakdown
     */
    public static function get_price_breakdown($cart_item) {
        try {
            $calculation_data = Limes_Cart_Data_Handler::get_calculation_data($cart_item);
            return Limes_Price_Calculator::calculate_price($calculation_data);
        } catch (Exception $e) {
            return array(
                'base_price' => isset($cart_item['base_price']) ? $cart_item['base_price'] : 0,
                'dimensional_price' => isset($cart_item['base_price']) ? $cart_item['base_price'] : 0,
                'addon_price' => 0,
                'total_price' => isset($cart_item['base_price']) ? $cart_item['base_price'] : 0,
                'calculations' => array(),
                'errors' => array($e->getMessage())
            );
        }
    }
}

// Initialize the cart price updater
Limes_Cart_Price_Updater::init();
