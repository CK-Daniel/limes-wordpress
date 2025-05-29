<?php
/**
 * Simple Product Customizations for Limes Theme
 * 
 * This file handles the customization of simple products to match
 * the button layout and styling of variable products.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Remove default add to cart button for simple products
 * We'll replace it with our custom wrapper
 */
add_action( 'init', 'limes_remove_simple_product_add_to_cart' );
function limes_remove_simple_product_add_to_cart() {
    remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
}

/**
 * Add custom add to cart wrapper for simple products
 */
add_action( 'woocommerce_simple_add_to_cart', 'limes_custom_simple_add_to_cart', 30 );
function limes_custom_simple_add_to_cart() {
    global $product;
    
    if ( ! $product->is_purchasable() ) {
        return;
    }
    
    // Check if product has addons
    $product_addons_data = $product->get_meta( '_product_addons', true );
    $has_addons = !empty($product_addons_data) ? 'has_addons' : '';
    
    // Get product type dimensions
    $product_type_dimensions = get_field( 'product_type_dimensions', $product->get_id() );
    
    woocommerce_quantity_input( array(
        'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
        'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
        'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
    ) );
    
    ?>
    <div class="wrap_cart_btns <?php echo $has_addons; ?>">
        <div class="wrap_add_cart_btn">
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" 
                    class="single_add_to_cart_button button alt add_to_cart_trigger_btn">
                <?php echo esc_html( $product->single_add_to_cart_text() ); ?>
            </button>
        </div>
        <div class="wrap_cart_link_btn">
            <a class="cart-contents button" href="<?php echo esc_url( wc_get_cart_url() ); ?>" 
               title="<?php esc_attr_e( 'View your shopping cart', 'woocommerce' ); ?>">לצפייה בסל</a>
        </div>
    </div>
    <?php
}

/**
 * Add custom CSS for simple product quantity styling
 */
add_action( 'wp_head', 'limes_simple_product_custom_css' );
function limes_simple_product_custom_css() {
    if ( ! is_product() ) {
        return;
    }
    
    global $product;
    if ( ! $product || ! $product->is_type( 'simple' ) ) {
        return;
    }
    ?>
    <style>
    /* Modern quantity input styling for simple products */
    .single-product .product-type-simple form.cart .quantity {
        display: inline-flex;
        align-items: center;
        background: #f7f7f7;
        border-radius: 40px;
        padding: 5px;
        margin-bottom: 20px;
        position: relative;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        min-width: 140px;
    }
    
    .single-product .product-type-simple form.cart .quantity .screen-reader-text {
        position: absolute;
        left: -9999px;
    }
    
    .single-product .product-type-simple form.cart .quantity input.qty {
        background: transparent;
        border: none;
        text-align: center;
        width: 60px;
        font-size: 18px;
        font-weight: 600;
        color: #333;
        padding: 10px 5px;
        -moz-appearance: textfield;
        margin: 0 10px;
    }
    
    .single-product .product-type-simple form.cart .quantity input.qty::-webkit-outer-spin-button,
    .single-product .product-type-simple form.cart .quantity input.qty::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    .single-product .product-type-simple form.cart .quantity input.qty:focus {
        outline: none;
    }
    
    /* Style quantity buttons */
    .single-product .product-type-simple form.cart .quantity .qty-minus,
    .single-product .product-type-simple form.cart .quantity .qty-plus {
        position: relative;
        width: 35px;
        height: 35px;
        background: #fff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        line-height: 1;
        color: #666;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: normal;
    }
    
    .single-product .product-type-simple form.cart .quantity .qty-minus:hover,
    .single-product .product-type-simple form.cart .quantity .qty-plus:hover {
        background: #f0f0f0;
        transform: scale(1.1);
    }
    
    .single-product .product-type-simple form.cart .quantity .qty-minus:active,
    .single-product .product-type-simple form.cart .quantity .qty-plus:active {
        transform: scale(0.95);
    }
    
    /* Style the simple product price display */
    .single-product .product-type-simple .price.simple-product-price {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
    }
    
    .single-product .product-type-simple .price.simple-product-price del {
        opacity: 0.5;
        margin-right: 10px;
    }
    
    .single-product .product-type-simple .price.simple-product-price ins {
        text-decoration: none;
        color: #d9534f;
    }
    
    .single-product .product-type-simple .price.simple-product-price .woocommerce-Price-amount {
        font-weight: 600;
    }
    
    /* Ensure consistent button styling */
    .single-product .product-type-simple .wrap_cart_btns {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .single-product .product-type-simple .wrap_cart_btns .wrap_add_cart_btn,
    .single-product .product-type-simple .wrap_cart_btns .wrap_cart_link_btn {
        flex: 1;
    }
    
    .single-product .product-type-simple .wrap_cart_btns .button {
        width: 100%;
        text-align: center;
        padding: 12px 20px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    /* Style the addon container for simple products */
    .single-product .product-type-simple .wc-pao-addons-container {
        margin-bottom: 20px;
    }
    
    /* Adjust form.cart for simple products to match variable products */
    .single-product .product-type-simple form.cart {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    /* Fix quantity alignment */
    .single-product .product-type-simple form.cart > .quantity {
        align-self: flex-start;
        margin-bottom: 0;
    }
    </style>
    <?php
}

/**
 * Add JavaScript for quantity buttons on simple products
 */
add_action( 'wp_footer', 'limes_simple_product_quantity_js' );
function limes_simple_product_quantity_js() {
    if ( ! is_product() ) {
        return;
    }
    
    global $product;
    if ( ! $product || ! $product->is_type( 'simple' ) ) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add plus and minus functionality for simple products
        var $quantityWrapper = $('.product-type-simple form.cart .quantity');
        
        if ($quantityWrapper.length) {
            // Add buttons
            $quantityWrapper.prepend('<button type="button" class="qty-minus">-</button>');
            $quantityWrapper.append('<button type="button" class="qty-plus">+</button>');
            
            // Handle minus click
            $quantityWrapper.on('click', '.qty-minus', function(e) {
                e.preventDefault();
                var $input = $(this).siblings('.qty');
                var val = parseInt($input.val()) || 1;
                var min = parseInt($input.attr('min')) || 1;
                
                if (val > min) {
                    $input.val(val - 1).trigger('change');
                }
            });
            
            // Handle plus click
            $quantityWrapper.on('click', '.qty-plus', function(e) {
                e.preventDefault();
                var $input = $(this).siblings('.qty');
                var val = parseInt($input.val()) || 1;
                var max = parseInt($input.attr('max')) || 9999;
                
                if (val < max) {
                    $input.val(val + 1).trigger('change');
                }
            });
        }
        
        // Add body class for CSS targeting
        $('body').addClass('product-type-simple');
    });
    </script>
    <?php
}