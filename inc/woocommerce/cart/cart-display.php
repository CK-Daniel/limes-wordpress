<?php
/**
 * Cart Display Handler
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cart display handler class
 */
class Limes_Cart_Display {

    /**
     * Initialize cart display handling
     */
    public static function init() {
        add_filter('woocommerce_get_item_data', array(__CLASS__, 'display_custom_dimensions_cart'), 10, 2);
        add_filter('woocommerce_cart_item_name', array(__CLASS__, 'add_product_type_label'), 10, 3);
        add_action('woocommerce_after_cart', array(__CLASS__, 'display_debug_info'));
    }

    /**
     * Display custom dimensions and selections in cart
     *
     * @param array $item_data Item data
     * @param array $cart_item Cart item
     * @return array Modified item data
     */
    public static function display_custom_dimensions_cart($item_data, $cart_item) {
        $dimensions = Limes_Cart_Data_Handler::get_cart_item_dimensions($cart_item);
        $installation = Limes_Cart_Data_Handler::get_cart_item_installation($cart_item);

        // Display width
        if ($dimensions['width'] > 0) {
            $item_data['prod_width'] = array(
                'key' => 'רוחב הוילון',
                'display' => esc_html($dimensions['width'] . ' ס"מ'),
            );
        }

        // Display height
        if ($dimensions['height'] > 0) {
            $item_data['prod_height'] = array(
                'key' => 'גובה הוילון',
                'display' => esc_html($dimensions['height'] . ' ס"מ'),
            );
        }

        // Display coverage
        if ($dimensions['coverage'] > 0) {
            $item_data['prod_coverage'] = array(
                'key' => 'כמות כיסוי (מ"ר)',
                'display' => esc_html(number_format($dimensions['coverage'], 2)),
            );
        }

        // Display rolls needed
        if ($dimensions['rolls_needed'] > 0) {
            $item_data['rolls_needed'] = array(
                'key' => 'גלילים נדרשים',
                'display' => esc_html($dimensions['rolls_needed']),
            );
        }

        // Display mechanism side
        if (!empty($installation['mechanism_side'])) {
            $item_data['prod_radio_gr2'] = array(
                'key' => 'צד מנגנון',
                'display' => esc_html($installation['mechanism_side']),
            );
        }

        // Display installation type
        if (!empty($installation['installation_type'])) {
            $item_data['prod_radio_gr1'] = array(
                'key' => 'סוג התקנה',
                'display' => esc_html($installation['installation_type']),
            );
        }

        // Display calculated area or running meters
        $calculation_data = Limes_Cart_Data_Handler::get_calculation_data($cart_item);
        
        if ($calculation_data['product_type'] === 'sqm' && $dimensions['width'] > 0 && $dimensions['height'] > 0) {
            $area_m2 = ($dimensions['width'] / 100) * ($dimensions['height'] / 100);
            $settings = limes_get_product_type_settings('sqm');
            if ($area_m2 < $settings['min_area']) {
                $area_m2 = $settings['min_area'];
            }
            
            $item_data['calculated_area'] = array(
                'key' => 'שטח לחיוב (מ"ר)',
                'display' => number_format($area_m2, 2) . ' מ"ר',
            );
        } elseif ($calculation_data['product_type'] === 'rm' && $dimensions['width'] > 0) {
            $width_m = $dimensions['width'] / 100;
            $item_data['calculated_running_meter'] = array(
                'key' => 'מטר רץ לחיוב',
                'display' => number_format($width_m, 2) . ' מ׳',
            );
        }

        return $item_data;
    }

    /**
     * Add product type label to cart item name
     *
     * @param string $product_name Product name
     * @param array $cart_item Cart item
     * @param string $cart_item_key Cart item key
     * @return string Modified product name
     */
    public static function add_product_type_label($product_name, $cart_item, $cart_item_key) {
        $calculation_data = Limes_Cart_Data_Handler::get_calculation_data($cart_item);
        
        switch ($calculation_data['product_type']) {
            case 'rm':
                $product_name .= ' <span class="run_meter_label">(מחיר לפי מטר רץ)</span>';
                break;
            case 'sqm':
                $product_name .= ' <span class="sqm_label">(מחיר לפי מ"ר)</span>';
                break;
            case 'roll':
                $product_name .= ' <span class="roll_label">(מחיר לגליל)</span>';
                break;
        }

        return $product_name;
    }

    /**
     * Display debug information for admins
     */
    public static function display_debug_info() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!is_cart() && !(defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return;
        }

        echo '<div style="border:1px solid #f00; padding:15px; margin:20px 0; background:#fff; color:#333;">';
        echo '<h3><strong style="color:#f00;">DEBUG INFO (Admin Only)</strong></h3>';
        echo '<pre style="white-space: pre-wrap; word-wrap: break-word;">';

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            echo "--- Item Key: {$cart_item_key} ---\n";
            echo "Product ID: " . $cart_item['product_id'] . "\n";
            
            if ($cart_item['variation_id']) {
                echo "Variation ID: " . $cart_item['variation_id'] . "\n";
            }
            
            echo "Product Name: " . $cart_item['data']->get_name() . "\n";
            echo "Quantity: " . $cart_item['quantity'] . "\n";
            
            // Display stored data
            if (isset($cart_item['base_price'])) {
                echo "Stored Base Price: " . wc_price($cart_item['base_price']) . "\n";
            }
            
            // Get calculation data
            $calculation_data = Limes_Cart_Data_Handler::get_calculation_data($cart_item);
            echo "Product Type: " . ($calculation_data['product_type'] ?: 'N/A') . "\n";
            
            // Display dimensions
            $dimensions = Limes_Cart_Data_Handler::get_cart_item_dimensions($cart_item);
            if ($dimensions['width'] > 0) echo "Width: " . $dimensions['width'] . "cm\n";
            if ($dimensions['height'] > 0) echo "Height: " . $dimensions['height'] . "cm\n";
            if ($dimensions['coverage'] > 0) {
                echo "Coverage: " . $dimensions['coverage'] . " m²\n";
                echo "Coverage +5% Margin: " . number_format($dimensions['coverage'] * 1.05, 2) . " m²\n";
            }
            if ($dimensions['rolls_needed'] > 0) echo "Rolls Needed: " . $dimensions['rolls_needed'] . "\n";
            
            // Display price breakdown
            $breakdown = Limes_Cart_Price_Updater::get_price_breakdown($cart_item);
            echo "Price Breakdown:\n";
            echo "  Base Price: " . wc_price($breakdown['base_price']) . "\n";
            echo "  Dimensional Price: " . wc_price($breakdown['dimensional_price']) . "\n";
            echo "  Addon Price: " . wc_price($breakdown['addon_price']) . "\n";
            echo "  Total Price: " . wc_price($breakdown['total_price']) . "\n";
            
            if (!empty($breakdown['errors'])) {
                echo "Errors: " . implode(', ', $breakdown['errors']) . "\n";
            }
            
            echo "Final Item Price: " . wc_price($cart_item['data']->get_price()) . "\n";
            echo "Line Total: " . wc_price($cart_item['line_total']) . "\n";
            echo "---------------------------------\n";
        }

        echo "\nCart Subtotal: " . WC()->cart->get_cart_subtotal() . "\n";
        echo "Cart Total: " . WC()->cart->get_total('edit') . "\n";

        echo '</pre></div>';
    }
}

// Initialize the cart display handler
Limes_Cart_Display::init();
