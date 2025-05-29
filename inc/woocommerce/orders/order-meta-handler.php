<?php
/**
 * Order Meta Handler
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order meta handler class
 */
class Limes_Order_Meta_Handler {

    /**
     * Initialize order meta handling
     */
    public static function init() {
        add_action('woocommerce_checkout_create_order_line_item', array(__CLASS__, 'save_custom_dimensions_order'), 10, 4);
    }

    /**
     * Save custom dimensions and selections as order item meta
     *
     * @param WC_Order_Item_Product $item Order item
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item values
     * @param WC_Order $order Order object
     */
    public static function save_custom_dimensions_order($item, $cart_item_key, $values, $order) {
        $dimensions = Limes_Cart_Data_Handler::get_cart_item_dimensions($values);
        $installation = Limes_Cart_Data_Handler::get_cart_item_installation($values);

        // Save dimensions
        if ($dimensions['width'] > 0) {
            $item->add_meta_data('רוחב הוילון', $dimensions['width'] . ' ס"מ');
        }
        if ($dimensions['height'] > 0) {
            $item->add_meta_data('גובה הוילון', $dimensions['height'] . ' ס"מ');
        }
        if ($dimensions['coverage'] > 0) {
            $item->add_meta_data('כמות כיסוי (מ"ר)', number_format($dimensions['coverage'], 2));
        }
        if ($dimensions['rolls_needed'] > 0) {
            $item->add_meta_data('גלילים נדרשים', $dimensions['rolls_needed']);
        }

        // Save installation data
        if (!empty($installation['mechanism_side'])) {
            $item->add_meta_data('צד מנגנון', $installation['mechanism_side']);
        }
        if (!empty($installation['installation_type'])) {
            $item->add_meta_data('סוג התקנה', $installation['installation_type']);
        }

        // Save calculated area or running meters
        $calculation_data = Limes_Cart_Data_Handler::get_calculation_data($values);
        
        if ($calculation_data['product_type'] === 'sqm' && $dimensions['width'] > 0 && $dimensions['height'] > 0) {
            $area_m2 = ($dimensions['width'] / 100) * ($dimensions['height'] / 100);
            $settings = limes_get_product_type_settings('sqm');
            if ($area_m2 < $settings['min_area']) {
                $area_m2 = $settings['min_area'];
            }
            $item->add_meta_data('שטח לחיוב (מ"ר)', number_format($area_m2, 2) . ' מ"ר');
        } elseif ($calculation_data['product_type'] === 'rm' && $dimensions['width'] > 0) {
            $width_m = $dimensions['width'] / 100;
            $item->add_meta_data('מטר רץ לחיוב', number_format($width_m, 2) . ' מ׳');
        }

        // Save base price for reference (hidden meta)
        if (isset($values['base_price'])) {
            $item->add_meta_data('_base_price', wc_format_decimal($values['base_price'], wc_get_price_decimals()), true);
        }

        // Save price breakdown for reference (hidden meta)
        $breakdown = Limes_Cart_Price_Updater::get_price_breakdown($values);
        if (!empty($breakdown) && empty($breakdown['errors'])) {
            $item->add_meta_data('_price_breakdown', wp_json_encode($breakdown), true);
        }
    }

    /**
     * Get order item price breakdown
     *
     * @param WC_Order_Item_Product $item Order item
     * @return array|null Price breakdown or null if not available
     */
    public static function get_order_item_breakdown($item) {
        $breakdown_json = $item->get_meta('_price_breakdown', true);
        if ($breakdown_json) {
            return json_decode($breakdown_json, true);
        }
        return null;
    }

    /**
     * Display price breakdown in admin order details
     *
     * @param WC_Order_Item_Product $item Order item
     */
    public static function display_admin_breakdown($item) {
        if (!is_admin()) {
            return;
        }

        $breakdown = self::get_order_item_breakdown($item);
        if (!$breakdown) {
            return;
        }

        echo '<div class="limes-price-breakdown" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 3px solid #0073aa;">';
        echo '<h4 style="margin: 0 0 10px 0;">Price Breakdown</h4>';
        echo '<ul style="margin: 0; padding-left: 20px;">';
        
        if (!empty($breakdown['calculations'])) {
            foreach ($breakdown['calculations'] as $calc) {
                echo '<li><strong>' . esc_html($calc['description']) . ':</strong> ' . esc_html($calc['value']) . '</li>';
            }
        }
        
        echo '<li style="border-top: 1px solid #ddd; margin-top: 5px; padding-top: 5px;"><strong>Total:</strong> ' . wc_price($breakdown['total_price']) . '</li>';
        echo '</ul>';
        echo '</div>';
    }
}

// Initialize the order meta handler
Limes_Order_Meta_Handler::init();
