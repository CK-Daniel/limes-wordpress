<?php
/**
 * Main WooCommerce Integration
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main WooCommerce integration class
 */
class Limes_WooCommerce_Integration {

    /**
     * Initialize WooCommerce integration
     */
    public static function init() {
        // Load configuration
        require_once get_template_directory() . '/inc/woocommerce/config/calculation-settings.php';
        
        // Load calculation engine
        require_once get_template_directory() . '/inc/woocommerce/calculations/price-calculator.php';
        
        // Load cart modules
        require_once get_template_directory() . '/inc/woocommerce/cart/cart-data-handler.php';
        require_once get_template_directory() . '/inc/woocommerce/cart/cart-price-updater.php';
        require_once get_template_directory() . '/inc/woocommerce/cart/cart-display.php';
        
        // Load order modules
        require_once get_template_directory() . '/inc/woocommerce/orders/order-meta-handler.php';
        
        // Load product modules
        self::load_product_modules();
        
        // Load hooks
        self::load_hooks();
        
        // Initialize frontend scripts
        add_action('wp_footer', array(__CLASS__, 'load_frontend_scripts'));
    }

    /**
     * Load product-related modules
     */
    private static function load_product_modules() {
        // Product pricing display
        add_filter('woocommerce_get_price_html', array(__CLASS__, 'custom_dimensional_price_display'), 10, 2);
        add_filter('woocommerce_variation_price_html', array(__CLASS__, 'force_min_price_variation_html'), 10, 2);
        
        // Product validation
        add_filter('woocommerce_add_to_cart_validation', array(__CLASS__, 'validate_minimum_price_before_add_to_cart'), 10, 5);
    }

    /**
     * Load WooCommerce hooks
     */
    private static function load_hooks() {
        // Admin bar enhancements
        add_action('admin_bar_menu', array(__CLASS__, 'add_admin_bar_items'), 100);
        
        // Remove WooCommerce noindex
        add_action('init', array(__CLASS__, 'remove_wc_page_noindex'));
        
        // Checkout field customization
        add_filter('woocommerce_checkout_fields', array(__CLASS__, 'custom_override_checkout_fields'), 20);
        
        // Universal addon section
        add_action('woocommerce_single_product_summary', array(__CLASS__, 'add_universal_addon_section'), 25);
    }

    /**
     * Load frontend scripts for dimensional pricing
     */
    public static function load_frontend_scripts() {
        if (!is_product()) {
            return;
        }

        $product = wc_get_product(get_the_ID());
        if (!$product) {
            return;
        }

        $currency_symbol = get_woocommerce_currency_symbol();
        $product_type_dimensions = get_field('product_type_dimensions', get_the_ID());
        $min_price = (float) get_field('pro_order_min_price', get_the_ID());

        ?>
        <script>
        jQuery(function($){
            console.log('🚀 Limes WooCommerce Integration initialized');

            // Configuration
            var limesConfig = {
                basePrice: <?php echo json_encode((float) $product->get_price()); ?>,
                minPrice: <?php echo json_encode($min_price); ?>,
                productType: <?php echo json_encode($product_type_dimensions); ?>,
                currencySymbol: <?php echo json_encode($currency_symbol); ?>,
                settings: <?php echo json_encode(limes_get_calculation_settings()); ?>
            };

            // Initialize the modular price calculator
            if (typeof LimesPriceCalculator !== 'undefined') {
                LimesPriceCalculator.init(limesConfig);
            } else {
                console.warn('LimesPriceCalculator not loaded, falling back to legacy system');
                // Fallback to existing system if new JS modules aren't loaded yet
            }
        });
        </script>
        <?php
    }

    /**
     * Custom dimensional price display
     */
    public static function custom_dimensional_price_display($price_html, $product) {
        if (!is_product()) {
            return $price_html;
        }
        
        // Only show dimensional pricing info for variable products
        if ($product->is_type('simple')) {
            return $price_html;
        }

        $min = get_field('pro_order_min_price', $product->get_id());
        if ($min) {
            $price_html .= '<div style="margin-top:10px;font-size:0.9em;">'
                . '<span>המחיר הוא לפי מ״ר.</span><br>'
                . '<span>מחיר מינימלי להזמנה: ' . wc_price($min) . '</span>'
                . '</div>';
        }
        return $price_html;
    }

    /**
     * Force minimum price for variations
     */
    public static function force_min_price_variation_html($price_html, $variation) {
        $min = get_field('pro_order_min_price', $variation->get_id());
        if ($min && $variation->get_price() < $min) {
            return wc_price($min);
        }
        return $price_html;
    }

    /**
     * Validate minimum price before add to cart
     */
    public static function validate_minimum_price_before_add_to_cart($passed, $product_id, $qty, $variation_id = 0) {
        $id = $variation_id ?: $product_id;
        $min = get_field('pro_order_min_price', $id);
        
        if (!$min) {
            return $passed;
        }

        // Get calculation data from POST
        $calculation_data = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'width' => isset($_POST['prod_width']) ? (float) $_POST['prod_width'] : 0,
            'height' => isset($_POST['prod_height']) ? (float) $_POST['prod_height'] : 0,
            'coverage' => isset($_POST['prod_coverage']) ? (float) $_POST['prod_coverage'] : 0,
            'quantity' => $qty
        );

        try {
            $result = Limes_Price_Calculator::calculate_price($calculation_data);
            $total_price = $result['total_price'] * $qty;

            if ($total_price < $min) {
                wc_add_notice(
                    sprintf('המחיר המינימלי להזמנה הוא %s. אנא התאם את המידות.', wc_price($min)),
                    'error'
                );
                return false;
            }

            // Store calculated price for later use
            $_POST['calculated_price'] = $result['total_price'];

        } catch (Exception $e) {
            wc_add_notice('שגיאה בחישוב המחיר. אנא נסה שוב.', 'error');
            return false;
        }

        return $passed;
    }

    /**
     * Add admin bar items
     */
    public static function add_admin_bar_items($admin_bar) {
        if (function_exists('is_shop') && is_shop() && current_user_can('edit_pages')) {
            $id = wc_get_page_id('shop');
            if ($id) {
                $href = get_edit_post_link($id);
                if ($href) {
                    $admin_bar->add_menu(array(
                        'id' => 'edit-shop-page',
                        'parent' => false,
                        'title' => 'עריכת עמוד חנות',
                        'href' => $href
                    ));
                }
            }
        } elseif (is_product() && current_user_can('edit_products')) {
            $product_id = get_the_ID();
            if ($product_id) {
                $href = get_edit_post_link($product_id);
                if ($href) {
                    $admin_bar->add_menu(array(
                        'id' => 'edit-product-page',
                        'parent' => false,
                        'title' => 'עריכת מוצר',
                        'href' => $href
                    ));
                }
            }
        }
    }

    /**
     * Remove WooCommerce noindex tags
     */
    public static function remove_wc_page_noindex() {
        remove_filter('wp_robots', 'wc_page_no_robots');
    }

    /**
     * Add universal addon section to all products
     */
    public static function add_universal_addon_section() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Always include the section, regardless of product type or addon presence
        wc_get_template(
            'single-product/addon-totals-universal.php',
            array('product' => $product),
            '',
            get_template_directory() . '/woocommerce/'
        );
    }

    /**
     * Custom checkout fields override
     */
    public static function custom_override_checkout_fields($fields) {
        // Reorder billing fields
        $billing_order = array(
            "billing_first_name" => 10,
            "billing_last_name" => 20,
            "billing_company" => 30,
            "billing_country" => 40,
            "billing_address_1" => 60,
            "billing_address_2" => 70,
            "billing_city" => 80,
            "billing_postcode" => 90,
            "billing_phone" => 100,
            "billing_email" => 110,
        );

        foreach ($billing_order as $field_key => $priority) {
            if (isset($fields['billing'][$field_key])) {
                $fields['billing'][$field_key]['priority'] = $priority;
            }
        }

        return $fields;
    }
}

// Initialize WooCommerce integration
if (class_exists('WooCommerce')) {
    Limes_WooCommerce_Integration::init();
}
