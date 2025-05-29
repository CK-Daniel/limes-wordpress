<?php
/**
 * Final Price Label Display for Variable Products
 * 
 * This file adds a simple "מחיר סופי:" label above the addon section
 * for variable products with dimensions.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add final price label above addons section
 */
add_action( 'woocommerce_before_single_variation', 'limes_add_final_price_label', 25 );
function limes_add_final_price_label() {
    global $product;
    
    // Only show for variable products
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
    
    // Check if product has dimensions
    $product_type_dimensions = get_field( 'product_type_dimensions', $product->get_id() );
    if ( ! $product_type_dimensions ) {
        return;
    }
    ?>
    <div class="limes-final-price-label-only">
        <span class="final-price-label">מחיר סופי:</span>
    </div>
    <?php
}

/**
 * Add CSS for final price label
 */
add_action( 'wp_head', 'limes_final_price_label_css' );
function limes_final_price_label_css() {
    if ( ! is_product() ) {
        return;
    }
    ?>
    <style>
    /* Simple Final Price Label Above Addons */
    .limes-final-price-label-only {
        margin: 20px 0 10px;
        text-align: right;
        direction: rtl;
    }
    
    .limes-final-price-label-only .final-price-label {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        display: inline-block;
        border-bottom: 2px solid #333;
        padding-bottom: 5px;
    }
    
    /* Keep the final price text inside addon totals as well */
    .wc-pao-subtotal-line .price {
        font-size: 18px !important;
        font-weight: 600;
    }
    
    /* Make sure the addon container is visible when we have addons */
    .wc-pao-addons-container.limes-price-verified {
        display: block ;
    }
    </style>
    <?php
}