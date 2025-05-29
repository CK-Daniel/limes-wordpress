<?php

/**
 * Enqueue the WooCommerce variation script on product pages.
 */
function my_enqueue_wc_variation_script() {
	if ( is_product() ) {
		wp_enqueue_script( 'wc-add-to-cart-variation' );
	}
}
add_action( 'wp_enqueue_scripts', 'my_enqueue_wc_variation_script' );

/**
 * Display custom dimension fields in the add-to-cart form,
 * based on "product_type_dimensions" ACF (sqm, rm, roll).
 */
function my_custom_dimensions_fields() {
	global $product;
	// Ensure we have a product object
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$product_id              = $product->get_id();
	$pro_order_min_price     = get_field( 'pro_order_min_price', $product_id );
	$product_type_dimensions = get_field( 'product_type_dimensions', $product_id );
	$pro_min_width           = get_field( 'pro_min_width', $product_id );
	$pro_max_width           = get_field( 'pro_max_width', $product_id );
	$pro_min_height          = get_field( 'pro_min_height', $product_id );
	$pro_max_height          = get_field( 'pro_max_height', $product_id );

	// 1) SQM: Show width & height
	if ( $product_type_dimensions === "sqm" ) : ?>
<div class="wrap_dimensions">
    <div class="wrap_dim wrap_width">
        <label for="prod_width">*רוחב הוילון: <span class="must">(שדה חובה)</span></label>
        <div class="wrap_input">
            <input type="number" placeholder="הזן מידה בס״מ" name="prod_width" id="prod_width"
                min="<?php echo esc_attr( $pro_min_width ); ?>" max="<?php echo esc_attr( $pro_max_width ); ?>"
                tabindex="1" required oninvalid="this.setCustomValidity('שדה זה חובה');"
                oninput="this.setCustomValidity('');">
            <div class="validation_tip">
                <span class="validation_min_width">רוחב מינמלי: <?php echo esc_html( $pro_min_width ); ?> ס״מ</span>
            </div>
            <div class="validation_tip">
                <span class="validation_max_width">רוחב מקסימלי: <?php echo esc_html( $pro_max_width ); ?> ס״מ</span>
            </div>
        </div>
    </div>
    <div class="wrap_dim wrap_height">
        <label for="prod_height">*גובה הוילון: <span class="must">(שדה חובה)</span></label>
        <div class="wrap_input">
            <input type="number" placeholder="הזן מידה בס״מ" name="prod_height" id="prod_height"
                min="<?php echo esc_attr( $pro_min_height ); ?>" max="<?php echo esc_attr( $pro_max_height ); ?>"
                tabindex="1" required oninvalid="this.setCustomValidity('שדה זה חובה');"
                oninput="this.setCustomValidity('');">
            <div class="validation_tip">
                <span class="validation_min_height">גובה מינמלי: <?php echo esc_html( $pro_min_height ); ?> ס״מ</span>
            </div>
            <div class="validation_tip">
                <span class="validation_max_height">גובה מקסימלי: <?php echo esc_html( $pro_max_height ); ?> ס״מ</span>
            </div>
        </div>
    </div>
</div>
<?php
	// 2) RM: Show width only
	elseif ( $product_type_dimensions === "rm" ) : ?>
<div class="wrap_dimensions">
    <div class="wrap_dim wrap_width">
        <label for="prod_width">*רוחב הוילון: <span class="must">(שדה חובה)</span></label>
        <div class="wrap_input">
            <input type="number" placeholder="הזן מידה בס״מ" name="prod_width" id="prod_width"
                min="<?php echo esc_attr( $pro_min_width ?: 30 ); ?>"
                max="<?php echo esc_attr( $pro_max_width ?: 800 ); ?>" tabindex="1" required
                oninvalid="this.setCustomValidity('שדה זה חובה');" oninput="this.setCustomValidity('');">
            <div class="validation_tip">
                <span class="validation_min_width">רוחב מינמלי: <?php echo esc_html( $pro_min_width ?: 30 ); ?>
                    ס״מ</span>
            </div>
            <div class="validation_tip">
                <span class="validation_max_width">רוחב מקסימלי: <?php echo esc_html( $pro_max_width ?: 800 ); ?>
                    ס״מ</span>
            </div>
        </div>
    </div>
    <div class="wrap_dim wrap_height wrap_height_rm">
        <label for="prod_height">*גובה הוילון: <span class="must">(שדה חובה)</span></label>
        <div class="wrap_input">
            <input type="number" placeholder="הזן מידה בס״מ" name="prod_height" id="prod_height"
                min="<?php echo esc_attr( $pro_min_height ); ?>" max="<?php echo esc_attr( $pro_max_height ); ?>"
                tabindex="1" required oninvalid="this.setCustomValidity('שדה זה חובה');"
                oninput="this.setCustomValidity('');">
            <div class="validation_tip">
                <span class="validation_min_height">גובה מינמלי: <?php echo esc_html( $pro_min_height ); ?> ס״מ</span>
            </div>
            <div class="validation_tip">
                <span class="validation_max_height">גובה מקסימלי: <?php echo esc_html( $pro_max_height ); ?> ס״מ</span>
            </div>
        </div>
    </div>
</div>
<?php
	// 3) ROLL: Show coverage input & read-only "rolls needed," plus hidden fields for roll dims
	elseif ( $product_type_dimensions === "roll" ) :
	// Pull roll dimensions from ACF
	$roll_w = get_field( 'roll_width',  $product_id );
	$roll_l = get_field( 'roll_length', $product_id );
?>
<div class="wrap_dimensions">
    <!-- Coverage input -->
    <div class="wrap_dim wrap_coverage">
        <label for="prod_coverage">*כמות כיסוי (מ"ר): <span class="must">(שדה חובה)</span></label>
        <div class="wrap_input">
            <input type="number" placeholder="הזן כמות כיסוי במ״ר" step="0.01" name="prod_coverage" id="prod_coverage"
                min="0.1" tabindex="1" required oninvalid="this.setCustomValidity('שדה זה חובה');"
                oninput="this.setCustomValidity('');">
        </div>
    </div>
    <!-- Rolls needed (read-only) -->
    <div class="wrap_dim wrap_rolls_needed">
        <label for="prod_rolls_needed">גלילים נדרשים:</label> <!-- Removed * and mandatory text as it's calculated -->
        <div class="wrap_input">
            <input type="number" placeholder="0" name="prod_rolls_needed" id="prod_rolls_needed" value="0" step="1"
                readonly tabindex="-1"> <!-- Removed required as it's read-only -->
        </div>
    </div>
</div>

<!-- Hidden fields for the roll's dimensions (cm) -->
<input type="hidden" id="roll_width" value="<?php echo esc_attr( $roll_w ); ?>">
<input type="hidden" id="roll_length" value="<?php echo esc_attr( $roll_l ); ?>">
<?php
	endif; ?>
<div class="wrap_from_price_whatsapp">
    <div class="from_price">
        <span>מחיר:</span>
        <span class="from_span">החל מ -</span>
        <?php if ( $pro_order_min_price ) : ?>
        <span>
            <span class="woocommerce-Price-amount amount">
                <bdi><?php echo wc_price($pro_order_min_price); // Use wc_price for formatting ?></bdi>
            </span>
        </span>
        <?php else: ?>
        <span><?php echo $product->get_price_html(); ?></span>
        <?php endif; ?>
    </div>
    <div class="whatsapp_price share-whatsapp">
        <a href="https://api.whatsapp.com/send/?phone&text=<?php echo urlencode( get_the_permalink( $product_id ) ); ?>"
            target="_blank">
            <img class="check_icon" src="<?php echo get_template_directory_uri() ?>/images/whatsapp_icon2.png"
                alt="whatsapp_icon">
            <span>שתף מחיר</span>
        </a>
    </div>
</div>
<?php
}
add_action( 'woocommerce_before_variations_form', 'my_custom_dimensions_fields', 5 );

/**
 * Remove the default add-to-cart button and output custom cart buttons.
 */
function custom_woocommerce_single_variation_add_to_cart_button() {
	global $product;
    // Ensure we have a product object
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$product_id      = $product->get_id();
	$has_addons      = '';
	// Correct way to check product addons meta
    $product_addons_data = $product->get_meta( '_product_addons', true );
	if ( !empty($product_addons_data) ) {
		$has_addons = 'has_addons';
	}

	$product_type_dimensions = get_field( 'product_type_dimensions', $product->get_id() );
?>
<?php // Removed .roll_price and .final_price divs that were here ?>
<div class="wrap_cart_btns <?php echo $has_addons; ?>">
    <div class="wrap_add_cart_btn">
        <!-- Use a button or a link that JS can easily target -->
        <button type="submit"
            class="single_add_to_cart_button button alt add_to_cart_trigger_btn"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
        <!-- Keep your custom styled link if needed, but ensure form submission happens -->
        <!-- <a href="#" class="add_to_cart_trigger_btn">הוסף לסל</a> -->
    </div>
    <div class="wrap_cart_link_btn">
        <!-- The "Buy Now" functionality is handled elsewhere, this could link to cart -->
        <a class="cart-contents button" href="<?php echo esc_url( wc_get_cart_url() ); ?>"
            title="<?php esc_attr_e( 'View your shopping cart', 'woocommerce' ); ?>">לצפייה בסל</a>
        <!-- Original button -->
        <!-- <a class="cart-contents buy_now_trigger_btn" href="#" title="<?php _e( 'צפה בעגלת הקניות' ); ?>">לביצוע הזמנה</a> -->
    </div>
</div>
<?php
}
// Use the correct hook for single variation add to cart button area
add_action( 'woocommerce_single_variation', 'custom_woocommerce_single_variation_add_to_cart_button', 30 ); // Priority 30 to appear after price

// We'll keep the default variation add to cart button and enhance it
// remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );


/**
 * Display custom installation mechanism fields along with color attribute selection.
 * Hides mechanism/installation options for 'roll' type products.
 */
function my_custom_instalations_fields() {
	global $product;
    // Ensure we have a product object
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$product_id              = $product->get_id();
	$product_type_dimensions = get_field( 'product_type_dimensions', $product_id ); // Get the dimension type

	$attributes = $product->get_attributes();
	$pa_color   = isset( $attributes["pa_color"] ) ? $attributes["pa_color"] : false;

	// --- Color Attribute Section (Displayed for all types if available) ---
	if ( ! empty( $pa_color ) && $pa_color->get_options() ) : ?>
<div class="wrap_attrs">
    <div class="wrap_title">
        <span>בחר גוון:</span>
    </div>
    <div class="flex_attrs">
        <?php foreach ( $pa_color->get_terms() as $key => $term ) : // Use get_terms() for WC_Product_Attribute
	        $term_img = get_field( 'attr_color_img', $term ); // Get field from term object
	        ?>
        <label for="pcolor-<?php echo esc_attr( $term->slug ); ?>" class="wrap_item">
            <!-- Important: Use attribute_pa_color for the name to link with variations -->
            <input type="radio" name="attribute_pa_color" value="<?php echo esc_attr( $term->slug ); ?>"
                id="pcolor-<?php echo esc_attr( $term->slug ); ?>" data-attribute_name="attribute_pa_color"
                data-slug="<?php echo esc_attr( $term->slug ); ?>">
            <div class="wrap_img">
                <?php if ( ! empty( $term_img ) ) : ?>
                <img src="<?php echo esc_url( $term_img['url'] ); ?>" alt="<?php echo esc_attr( $term->name ); ?>">
                <?php endif; ?>
            </div>
            <div class="tooltip">
                <?php if ( ! empty( $term_img ) ) : ?>
                <div class="tooltip_img">
                    <img src="<?php echo esc_url( $term_img['url'] ); ?>" alt="<?php echo esc_attr( $term->name ); ?>">
                </div>
                <?php endif; ?>
                <span><?php echo esc_html( $term->name ); ?></span>
            </div>
        </label>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; // End color attribute section

	// --- Conditional Mechanism/Installation Section ---
	// Only show if the product type is NOT 'roll'
	if ( $product_type_dimensions !== 'roll' ) : ?>
<div class="wrap_mechanism_installation">
    <div class="wrap_mechanism">
        <div class="wrap_title">
            <span>*בחר צד מנגנון: <span class="must">(שדה חובה)</span></span>
        </div>
        <div class="flex_wrap">
            <label for="opt-4" class="wrap_item">
                <input type="radio" name="prod_radio-gr2" value="צד מנגנון - ימין" id="opt-4" required>
                <!-- Added required -->
                <div class="wrap_img_opt">
                    <img class="check_icon" src="<?php echo get_template_directory_uri() ?>/images/check_pro_icon2.png"
                        alt="check_pro_icon2">
                    <div class="inner">
                        <div class="wrap_img">
                            <img src="<?php echo get_template_directory_uri() ?>/images/mech_right.png"
                                alt="mech_right">
                        </div>
                        <div class="wrap_span">
                            <span>צד ימין</span>
                        </div>
                    </div>
                </div>
            </label>
            <label for="opt-3" class="wrap_item">
                <input type="radio" name="prod_radio-gr2" value="צד מנגנון - שמאל" id="opt-3" required>
                <!-- Added required -->
                <div class="wrap_img_opt">
                    <img class="check_icon" src="<?php echo get_template_directory_uri() ?>/images/check_pro_icon2.png"
                        alt="check_pro_icon2">
                    <div class="inner">
                        <div class="wrap_img">
                            <img src="<?php echo get_template_directory_uri() ?>/images/mech_left.png" alt="mech_left">
                        </div>
                        <div class="wrap_span">
                            <span>צד שמאל</span>
                        </div>
                    </div>
                </div>
            </label>
        </div>
    </div>
    <div class="wrap_installation">
        <div class="wrap_title">
            <span>*בחר סוג התקנה <span class="must">(שדה חובה)</span></span>
        </div>
        <div class="flex_wrap">
            <label for="opt-1" class="wrap_item">
                <input type="radio" name="prod_radio-gr1" value="סוג התקנה - קיר" id="opt-1" required>
                <!-- Added required -->
                <div class="wrap_img_opt">
                    <img class="check_icon" src="<?php echo get_template_directory_uri() ?>/images/check_pro_icon2.png"
                        alt="check_pro_icon2">
                    <div class="inner">
                        <div class="wrap_img">
                            <img src="<?php echo get_template_directory_uri() ?>/images/install_celling.png"
                                alt="install_celling">
                        </div>
                        <div class="wrap_span">
                            <span>קיר</span>
                        </div>
                    </div>
                </div>
            </label>
            <label for="opt-2" class="wrap_item">
                <input type="radio" name="prod_radio-gr1" value="סוג התקנה - תקרה" id="opt-2" required>
                <!-- Added required -->
                <div class="wrap_img_opt">
                    <img class="check_icon" src="<?php echo get_template_directory_uri() ?>/images/check_pro_icon2.png"
                        alt="check_pro_icon2">
                    <div class="inner">
                        <div class="wrap_img">
                            <img src="<?php echo get_template_directory_uri() ?>/images/install_wall.png"
                                alt="install_wall">
                        </div>
                        <div class="wrap_span">
                            <span>תקרתי</span>
                        </div>
                    </div>
                </div>
            </label>
        </div>
    </div>
</div>
<?php endif; // End conditional display for mechanism/installation
}
add_action( 'woocommerce_before_single_variation', 'my_custom_instalations_fields', 15 ); // Adjusted priority slightly

/**
 * Ensure all products have a consistent price display container
 * This creates a price container similar to the addons container for ALL products
 * to provide consistent final price display regardless of whether they have addons
 */
function ensure_price_display_container() {
    global $product;
    
    if (!$product) return;
    
    // Get product information
    $product_name = $product->get_name();
    $product_id = $product->get_id();
    $product_type_dimensions = get_field('product_type_dimensions', $product_id);
    
    // Check if this product has actual addons
    $product_addons_data = $product->get_meta( '_product_addons', true );
    $has_addons = !empty($product_addons_data);
    
    // Add CSS class based on product type for styling
    $product_type_class = $product_type_dimensions ? 'product-type-' . $product_type_dimensions : '';
    $dimensions_class = $product_type_dimensions ? 'dimensions-required' : '';
    
    // Always create the price container for consistent display
    // If the product has official addons, this will be enhanced by the addons plugin
    // If not, this provides the base structure
    ?>
    <div class="wc-pao-addons-container <?php echo esc_attr($product_type_class); ?>" data-product-type="<?php echo esc_attr($product_type_dimensions); ?>">
        <div id="product-addons-total" data-show-incomplete-sub-total="" data-show-sub-total="1" data-type="<?php echo $product->is_type('variable') ? 'variable' : 'simple'; ?>" data-tax-mode="excl" data-tax-display-mode="excl" data-price="<?php echo esc_attr($product->get_price()); ?>" data-raw-price="<?php echo esc_attr($product->get_price()); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
            <div class="product-addon-totals">
                <ul>
                    <?php if (!$has_addons): ?>
                    <!-- Base product line for products without addons -->
                    <li>
                        <div class="wc-pao-col1"><strong><span>x 1</span> <?php echo esc_html($product_name); ?></strong></div>
                        <div class="wc-pao-col2"><strong><span class="amount"><?php echo wc_price($product->get_price()); ?></span></strong></div>
                    </li>
                    <?php endif; ?>
                    <!-- Final price line (always present) -->
                    <li class="wc-pao-subtotal-line">
                        <p class="price"><?php echo wc_price($product->get_price()); ?></p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php if ($dimensions_class): ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add dimensions-required class to the form for products with dimensions
        $('form.cart').addClass('<?php echo esc_js($dimensions_class); ?>');
        
        // Add product type class to the body for CSS targeting
        $('body').addClass('<?php echo esc_js($product_type_class); ?>');
        
        <?php if ($product_type_dimensions === 'roll'): ?>
        // For roll products, immediately hide the variation price
        $('.woocommerce-variation-price, .single_variation .woocommerce-variation-price').hide();
        
        // Also hide it when variations change
        $(document).on('found_variation', function() {
            $('.woocommerce-variation-price, .single_variation .woocommerce-variation-price').hide();
        });
        <?php endif; ?>
        
        // Initialize price validation state
        if (typeof window.validateAllFields === 'function') {
            // Set initial state as invalid until all fields are filled
            $('form.cart').addClass('price-invalid');
        }
    });
    </script>
    <?php endif;
}
// Hook this for all products to ensure consistent price display
add_action( 'woocommerce_single_product_summary', 'ensure_price_display_container', 25 );

/**
 * Create a "Buy Now" button after Add to Cart button (optional).
 * This seems redundant with the 'buy_now_trigger_btn' in custom_woocommerce_single_variation_add_to_cart_button
 * Recommend removing one or integrating logic. Keeping original for now.
 */
function add_content_after_addtocart() {
    // global $product;
    // if ( ! $product ) return;
	// Only show for variable products if a variation is selected? Or always?
    // This button likely won't work correctly with the custom dimension fields without JS intervention.
	//$current_product_id = get_the_ID();
	//$cart_url           = wc_get_cart_url();
	//echo '<a href="' . esc_url( $cart_url . '?add-to-cart=' . $current_product_id ) . '" class="buy-now buy_now button">לביצוע הזמנה</a>';
}
// add_action( 'woocommerce_after_add_to_cart_button', 'add_content_after_addtocart' ); // Maybe remove this action

/**
 * Buy Now Button Logic (Simplified & Corrected)
 * This logic conflicts with custom fields. A JS approach is usually needed.
 * The simple redirect won't include dimensions, color, mechanism etc.
 * Recommend disabling this PHP-based Buy Now or building a JS solution.
 */

// Function to add button (Consider using JS to handle this)
function add_buy_now_button_php() {
	// global $product;
	// if ($product && ($product->is_type('simple') || $product->is_type('variable'))) {
	// 	$checkout_url = wc_get_checkout_url();
    //  // This link bypasses custom fields, NOT RECOMMENDED with your setup
	// 	// echo '<a href="' . esc_url(add_query_arg('add-to-cart', $product->get_id(), $checkout_url)) . '" class="button buy-now-button">Buy Now (Simple Link - No Custom Data)</a>';
	// }
}
//add_action('woocommerce_after_add_to_cart_button', 'add_buy_now_button_php');

// JS for variable products (Needs significant rework for custom fields)
function buy_now_variable_products_script_php() {
?>
<script type="text/javascript">
// jQuery(document).ready(function($) {
//     // This script needs to be rewritten to:
//     // 1. Validate custom fields (width, height, coverage, mechanism, install)
//     // 2. Gather ALL data (product_id, variation_id, quantity, custom fields)
//     // 3. Submit the add-to-cart form via AJAX
//     // 4. On successful AJAX response, THEN redirect to checkout.
//     // The simple redirect in the original script is insufficient.

//     // Example Placeholder - Actual implementation is complex
//     $('body').on('click', '.buy_now_trigger_btn', function(e) {
//          e.preventDefault();
//          var $form = $(this).closest('form.cart');
//          // --- Add validation logic here for your custom fields ---
//          var isValid = true; // Assume valid initially
//          // Example validation:
//          // if ( $form.find('#prod_width').length && !$form.find('#prod_width').val() ) isValid = false;
//          // ... more validation ...

//          if (isValid) {
//              // Add a flag to the form data maybe?
//              $form.append('<input type="hidden" name="buy_now_flag" value="1" />');

//              // Submit the form normally or via AJAX
//              // Option 1: Normal submit (redirect handled by PHP filter, if PHP filter is adjusted correctly)
//              // $form.submit();

//              // Option 2: AJAX submit (more control)
//              $.ajax({
//                  type: 'POST',
//                  url: $form.attr('action'), // Or wc_add_to_cart_params.wc_ajax_url ?
//                  data: $form.serialize(),
//                  success: function(response) {
//                      if (response.error) {
//                          // Handle add to cart error (e.g., show messages)
//                          console.error('Add to cart failed:', response.message);
//                          // Maybe remove the flag?
//                          $form.find('input[name="buy_now_flag"]').remove();
//                      } else {
//                          // Success! Redirect to checkout
//                          window.location.href = '<?php echo wc_get_checkout_url(); ?>';
//                      }
//                  },
//                  error: function(xhr, status, error) {
//                       console.error('AJAX error:', error);
//                       $form.find('input[name="buy_now_flag"]').remove();
//                  }
//              });

//          } else {
//              alert('Please fill in all required fields.'); // Or better error handling
//          }
//     });
// });
</script>
<?php
}
// add_action('wp_footer', 'buy_now_variable_products_script_php'); // Add the script

// Add JavaScript to integrate with the existing price calculator in woo-cart-calculations.php
function enhance_dimensional_price_calculator() {
    if (!is_product()) return;
    ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Wait for the page to fully load
    $(window).on('load', function() {
        // Remove any duplicate addon totals containers
        // The official WooCommerce Product Addons plugin adds its own container with ID 'product-addons-total'
        // Our custom container has class 'product-addon-totals'
        // If both exist, remove our custom one
        if ($('#product-addons-total').length > 0 && $('.product-addon-totals').length > 0) {
            console.log('Found both official and custom addon totals containers, removing custom one');
            $('.product-addon-totals').remove();
        }
        
        // Ensure the product-addons-total container is visible on page load
        // This is important to show the totals even if no addon is selected
        if ($('#product-addons-total').length > 0) {
            $('#product-addons-total').show();
            
            // Force update of addon totals on page load
            setTimeout(function() {
                // Trigger dimension field change to update the price
                if ($('#prod_width').length > 0) {
                    $('#prod_width').trigger('change');
                } else if ($('#prod_coverage').length > 0) {
                    $('#prod_coverage').trigger('change');
                }
                
                // Trigger the official addon totals update
                $(document.body).trigger('update_addon_totals');
            }, 500);
        }
        
        // Enhance the existing updateAllPrices function in woo-cart-calculations.php
        if (typeof window.updateAllPrices === 'function') {
            // Store the original function
            var originalUpdateAllPrices = window.updateAllPrices;
            
            // Override the function
            window.updateAllPrices = function() {
                // Call the original function
                originalUpdateAllPrices.apply(this, arguments);
                
                // Additional enhancements after the original function runs
                setTimeout(function() {
                    // Format the subtotal price with the WooCommerce price format
                    var $subtotal = $('.product-addon-totals .wc-pao-subtotal-line .price');
                    if ($subtotal.length) {
                        var currentPrice = $subtotal.text().replace(/[^\d\.]/g, "");
                        if (currentPrice) {
                            var formattedPrice = '<span class="woocommerce-Price-amount amount"><bdi>' + 
                                                parseFloat(currentPrice).toFixed(2) + '&nbsp;<span class="woocommerce-Price-currencySymbol">₪</span></bdi></span>';
                            $subtotal.html(formattedPrice);
                        }
                    }
                }, 150);
            };
        }
        
        // Enhance the existing updateAddonDisplay function
        if (typeof window.updateAddonDisplay === 'function') {
            // Store the original function
            var originalUpdateAddonDisplay = window.updateAddonDisplay;
            
            // Override the function
            window.updateAddonDisplay = function(productPrice, addonBreakdown, finalPrice) {
                // Get the minimum price from the page
                var minPrice = parseFloat($('.from_price .woocommerce-Price-amount').text().replace(/[^\d\.]/g, "")) || 0;
                
                // Check if the product price is less than the minimum price
                if (productPrice < minPrice && minPrice > 0) {
                    // Update the product price to the minimum price
                    productPrice = minPrice;
                    
                    // Update the base product price display
                    $('.product-addon-totals ul li:first-child .wc-pao-col2 .amount').html(
                        productPrice.toFixed(2) + ' ₪'
                    );
                }
                
                // Check if the final price is less than the minimum price
                if (finalPrice < minPrice && minPrice > 0) {
                    // Update the final price to the minimum price
                    finalPrice = minPrice;
                }
                
                // Now call the original function with potentially updated prices
                originalUpdateAddonDisplay.call(this, productPrice, addonBreakdown, finalPrice);
                
                // Make sure the subtotal line has the correct final price with proper formatting
                var formattedPrice = '<span class="woocommerce-Price-amount amount"><bdi>' + 
                                    finalPrice.toFixed(2) + '&nbsp;<span class="woocommerce-Price-currencySymbol">₪</span></bdi></span>';
                $('.product-addon-totals .wc-pao-subtotal-line .price').html(formattedPrice);
                
                // Update the calculated price input
                $('input[name="calculated_price"]').val(finalPrice);
            };
        }
        
        // Fix the add to cart button functionality
        // Ensure the original click handler for add_to_cart_trigger_btn works properly
        $(document).off('click', '.add_to_cart_trigger_btn').on('click', '.add_to_cart_trigger_btn', function(e) {
            e.preventDefault();
            
            // Prevent multiple clicks or alert loops
            if ($(this).data('processing')) {
                return;
            }
            
            // Set processing flag to prevent multiple clicks
            $(this).data('processing', true);
            
            var $form = $(this).closest('form.cart');
            var allFieldsFilled = true;
            var missingFields = [];
            
            // Check required fields regardless of price-invalid class
            // Check width field
            var $width = $('#prod_width');
            if ($width.length && $width.prop('required') && !$width.val()) {
                missingFields.push('רוחב');
                $width.addClass('not-valid');
                allFieldsFilled = false;
            }
            
            // Check height field
            var $height = $('#prod_height');
            if ($height.length && $height.prop('required') && !$height.val()) {
                missingFields.push('גובה');
                $height.addClass('not-valid');
                allFieldsFilled = false;
            }
            
            // Check coverage field
            var $coverage = $('#prod_coverage');
            if ($coverage.length && $coverage.prop('required') && !$coverage.val()) {
                missingFields.push('כיסוי');
                $coverage.addClass('not-valid');
                allFieldsFilled = false;
            }
            
            // Check mechanism fields
            var $mechRadios = $('input[name="prod_radio-gr2"]');
            if ($mechRadios.length && $mechRadios.first().prop('required') && !$mechRadios.is(':checked')) {
                missingFields.push('צד מנגנון');
                allFieldsFilled = false;
            }
            
            // Check installation fields
            var $installRadios = $('input[name="prod_radio-gr1"]');
            if ($installRadios.length && $installRadios.first().prop('required') && !$installRadios.is(':checked')) {
                missingFields.push('סוג התקנה');
                allFieldsFilled = false;
            }
            
            // Show error message if fields are missing
            if (!allFieldsFilled) {
                alert('אנא מלא את השדות הבאים: ' + missingFields.join(', '));
                // Reset processing flag after a short delay to prevent alert loop
                setTimeout(function() {
                    $('.add_to_cart_trigger_btn').data('processing', false);
                }, 500);
                return;
            }
            
            // If all required fields are filled, remove the price-invalid class
            if (allFieldsFilled && $form.hasClass('price-invalid')) {
                $form.removeClass('price-invalid');
            }
            
            // Make sure variation is selected
            if ($form.hasClass('variations_form')) {
                // Check if variation is selected using multiple methods
                var variationSelected = false;
                
                // Method 1: Check for variation_id input
                if ($('input[name="variation_id"]').length && $('input[name="variation_id"]').val()) {
                    variationSelected = true;
                }
                
                // Method 2: Check if a variation is selected in the dropdown
                if ($('select[name^="attribute_"]').length) {
                    var allSelected = true;
                    $('select[name^="attribute_"]').each(function() {
                        if (!$(this).val()) {
                            allSelected = false;
                        }
                    });
                    if (allSelected) {
                        variationSelected = true;
                    }
                }
                
                // Method 3: Check if a radio button is selected
                if ($('input[type="radio"][name^="attribute_"]:checked').length) {
                    variationSelected = true;
                }
                
                // If no variation is selected, show alert
                if (!variationSelected) {
                    alert('אנא בחר וריאציה');
                    // Reset processing flag after a short delay to prevent alert loop
                    setTimeout(function() {
                        $('.add_to_cart_trigger_btn').data('processing', false);
                    }, 500);
                    return;
                }
                
                // Add the variation ID to the form if it's not already there
                if (!$('input[name="variation_id"]').length) {
                    // Get the selected variation from the data attribute
                    var variations = $form.data('product_variations');
                    if (variations) {
                        // Get all selected attributes
                        var selectedAttributes = {};
                        $('select[name^="attribute_"], input[type="radio"][name^="attribute_"]:checked').each(function() {
                            selectedAttributes[$(this).attr('name')] = $(this).val();
                        });
                        
                        // Find the matching variation
                        var matchingVariation = null;
                        for (var i = 0; i < variations.length; i++) {
                            var variation = variations[i];
                            var allMatch = true;
                            
                            for (var attrName in selectedAttributes) {
                                if (variation.attributes[attrName] !== selectedAttributes[attrName]) {
                                    allMatch = false;
                                    break;
                                }
                            }
                            
                            if (allMatch) {
                                matchingVariation = variation;
                                break;
                            }
                        }
                        
                        // If we found a matching variation, add it to the form
                        if (matchingVariation) {
                            $form.append('<input type="hidden" name="variation_id" value="' + matchingVariation.variation_id + '">');
                        }
                    }
                }
            }
            
            // If this is a submit button within a form, submit the form directly
            if ($(this).is('button[type="submit"]')) {
                // Force recalculation of price before submission
                if (typeof window.recalcFinalPrice === 'function') {
                    window.recalcFinalPrice();
                }
                
                // Add a small delay to ensure calculations are complete
                setTimeout(function() {
                    $form.submit();
                }, 100);
                return;
            }
            
            // Otherwise, find and click the actual add to cart button
            var $addToCartBtn = $(this).parents('.single_variation_wrap').find('.single_add_to_cart_button');
            if ($addToCartBtn.length) {
                $addToCartBtn.trigger('click');
            }
        });
    });
});
</script>
    <?php
}
add_action('wp_footer', 'enhance_dimensional_price_calculator', 99); // Use a high priority to ensure it runs after other scripts

// The add_product_addon_totals function has been removed since the official WooCommerce Product Addons plugin
// already adds its own container. We'll use the official container instead of creating our own.

/**
 * Add a safety check for add to cart validation to ensure dimensions are provided
 */
add_filter('woocommerce_add_to_cart_validation', 'ensure_dimensions_in_add_to_cart', 10, 3);

function ensure_dimensions_in_add_to_cart($valid, $product_id, $quantity) {
    $product = wc_get_product($product_id);
    $product_type_dimensions = get_field('product_type_dimensions', $product_id);
    
    // Only validate for products with dimension requirements
    if ($product_type_dimensions) {
        if ($product_type_dimensions === 'sqm' || $product_type_dimensions === 'rm') {
            if (!isset($_POST['prod_width']) || empty($_POST['prod_width'])) {
                wc_add_notice(__('נא להזין את הרוחב.', 'woocommerce'), 'error');
                return false;
            }
            
            // For sqm products, height is also required
            if ($product_type_dimensions === 'sqm' && (!isset($_POST['prod_height']) || empty($_POST['prod_height']))) {
                wc_add_notice(__('נא להזין את הגובה.', 'woocommerce'), 'error');
                return false;
            }
        } elseif ($product_type_dimensions === 'roll') {
            if (!isset($_POST['prod_coverage']) || empty($_POST['prod_coverage'])) {
                wc_add_notice(__('נא להזין את שטח הכיסוי.', 'woocommerce'), 'error');
                return false;
            }
        }
    }
    
    return $valid;
}
