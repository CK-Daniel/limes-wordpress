<?php
/**
 * Universal Addon Totals Template
 * 
 * This template shows the price breakdown for all products,
 * regardless of whether they have addons or not.
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $product;

if (!$product) {
    return;
}

$product_type = get_field('product_type_dimensions', $product->get_id());
$is_variable = $product->is_type('variable');
$base_price = $product->get_price();

// Get roll dimensions if it's a roll product
$roll_width = '';
$roll_length = '';
if ($product_type === 'roll') {
    $roll_width = get_field('roll_width', $product->get_id());
    $roll_length = get_field('roll_length', $product->get_id());
}

// Get minimum area setting
$min_sqm = get_field('min_sqm', $product->get_id()) ?: 1;
?>

<div class="product-addon-totals universal-addon-section" style="display: none;" data-product-type="<?php echo esc_attr($product_type); ?>">
    <div class="addon-section-header">
        <h4>פירוט מחיר</h4>
    </div>
    
    <ul class="addon-totals-list">
        <li class="wc-pao-base-price-line">
            <div class="wc-pao-col1">
                <strong>מחיר בסיס:</strong>
            </div>
            <div class="wc-pao-col2">
                <span class="price">
                    <span class="woocommerce-Price-amount amount">0.00</span>
                    <span class="woocommerce-Price-currencySymbol">₪</span>
                </span>
            </div>
        </li>
        
        <!-- Dynamic addon lines will be inserted here by JavaScript -->
        
        <li class="wc-pao-subtotal-line final-price-line">
            <div class="wc-pao-col1">
                <strong>מחיר סופי:</strong>
            </div>
            <div class="wc-pao-col2">
                <span class="price final-price">
                    <span class="woocommerce-Price-amount amount">0.00</span>
                    <span class="woocommerce-Price-currencySymbol">₪</span>
                </span>
            </div>
        </li>
    </ul>
    
    <!-- Hidden fields for JavaScript calculations -->
    <input type="hidden" id="base_price" data-base-price="<?php echo esc_attr($base_price); ?>" data-default-price="<?php echo esc_attr($base_price); ?>">
    <input type="hidden" id="product_type" value="<?php echo esc_attr($product_type); ?>">
    <input type="hidden" id="is_variable" value="<?php echo $is_variable ? '1' : '0'; ?>">
    <input type="hidden" id="min_sqm" value="<?php echo esc_attr($min_sqm); ?>">
    
    <?php if ($product_type === 'roll' && $roll_width && $roll_length): ?>
    <input type="hidden" id="roll_width" value="<?php echo esc_attr($roll_width); ?>">
    <input type="hidden" id="roll_length" value="<?php echo esc_attr($roll_length); ?>">
    <?php endif; ?>
    
    <input type="hidden" id="prod_rolls_needed" value="0">
</div>

<script>
// Add product data to global scope for JavaScript access
window.limesProductData = window.limesProductData || {};
window.limesProductData = {
    productType: '<?php echo esc_js($product_type); ?>',
    isVariable: <?php echo $is_variable ? 'true' : 'false'; ?>,
    productId: <?php echo $product->get_id(); ?>,
    basePrice: <?php echo $base_price; ?>,
    minSqm: <?php echo $min_sqm; ?>,
    rollWidth: <?php echo $roll_width ? $roll_width : 0; ?>,
    rollLength: <?php echo $roll_length ? $roll_length : 0; ?>
};
</script>
