<?php

/**
 * Save the custom dimensions fields (including coverage & rolls needed) to the cart.
 * Also save the product's (variation) base price for later calculations.
 * IMPORTANT: Use a later priority to ensure variation data is potentially set first.
 */
function my_save_custom_dimensions( $cart_item_data, $product_id, $variation_id = 0 ) {
	$has_width        = isset( $_POST['prod_width'] ) && $_POST['prod_width'] !== '';
	$has_height       = isset( $_POST['prod_height'] ) && $_POST['prod_height'] !== '';
	$has_coverage     = isset( $_POST['prod_coverage'] ) && $_POST['prod_coverage'] !== '';
	$has_rolls_needed = isset( $_POST['prod_rolls_needed'] ); // Can be "0"

	// Width / Height
	if ( $has_width ) {
		$cart_item_data['prod_width']  = sanitize_text_field( $_POST['prod_width'] );
	}
	if ( $has_height ) {
		$cart_item_data['prod_height'] = sanitize_text_field( $_POST['prod_height'] );
	}

	// Coverage for roll
	if ( $has_coverage ) {
		$cart_item_data['prod_coverage'] = sanitize_text_field( $_POST['prod_coverage'] );
	}

	// Rolls needed (read-only field updated by JS)
	if ( $has_rolls_needed ) {
		// Sanitize as integer or float depending on needs, int seems appropriate
		$cart_item_data['rolls_needed'] = absint( $_POST['prod_rolls_needed'] );
	}

	// Get product/variation for base price
	$product_for_price = wc_get_product( $variation_id ? $variation_id : $product_id );

	// Variation logic: set base_price if dimension or coverage is provided AND product exists
	if ( ($has_width || $has_height || $has_coverage) && $product_for_price ) {
		// Store the price used BEFORE any dimension calculations
		$cart_item_data['base_price'] = $product_for_price->get_price( 'edit' ); // Get raw price
	}

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'my_save_custom_dimensions', 15, 3 ); // Use priority 15, accept 3 args


/**
 * Save the custom installation mechanism fields.
 * Color attribute is handled by default WC variation logic if name="attribute_pa_color" is used.
 * Base price should already be set by my_save_custom_dimensions if needed.
 */
function my_save_custom_installs_mechanism( $cart_item_data, $product_id, $variation_id = 0 ) {
	if ( isset( $_POST['prod_radio-gr1'] ) ) {
		$cart_item_data['prod_radio-gr1'] = sanitize_text_field( $_POST['prod_radio-gr1'] );
	}
	if ( isset( $_POST['prod_radio-gr2'] ) ) {
		$cart_item_data['prod_radio-gr2'] = sanitize_text_field( $_POST['prod_radio-gr2'] );
	}

	// Note: Color attribute is saved automatically by WC if input name is correct (attribute_pa_color)
	// We don't need to save 'prod_attr_color' separately anymore.

	// Ensure base_price is set if it wasn't by the previous function (e.g., simple product without dimensions)
	if ( empty( $cart_item_data['base_price'] ) ) {
		$product_for_price = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( $product_for_price ) {
			$cart_item_data['base_price'] = $product_for_price->get_price('edit');
		}
	}

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'my_save_custom_installs_mechanism', 20, 3 ); // Use priority 20, accept 3 args


/**
 * Main price adjustment function:
 *   - sqm (width+height => area, min 1m²)
 *   - rm (width => running meter)
 *   - roll (use coverage + ACF fields roll_width & roll_length to find # of rolls + 5% margin)
 */
function my_custom_dimensions_price_adjustment( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	// Make sure WC()->cart is loaded
	if ( ! $cart instanceof WC_Cart ) {
		$cart = WC()->cart;
		if( ! $cart ) return;
	}
	// file_put_contents( LOG_DIR .'limmes.log','func my_custom_dimensions_price_adjustment - before foreach:' . $calculated_price .PHP_EOL, FILE_APPEND);

	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		// Must have a base price stored
		if ( ! isset( $cart_item['base_price'] ) || $cart_item['base_price'] === '' ) {
			// Fallback: Try to get current price if base_price wasn't stored
			$cart_item['base_price'] = $cart_item['data']->get_price('edit');
			if( ! isset( $cart_item['base_price'] ) || $cart_item['base_price'] === '' ) {
				continue; // Skip if no base price can be determined
			}
		}

		$base_price              = (float) $cart_item['base_price'];
		// Use variation ID if available, otherwise product ID
		// $product_id_for_acf    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id']; // why variation ID ?
		$product_id_for_acf    = $cart_item['variation_id'] ? $cart_item['product_id'] : $cart_item['product_id'];
		$product_type_dimensions = get_field( 'product_type_dimensions', $product_id_for_acf );

		// Ensure we use the base price unless modified
		$calculated_price = $base_price;
		// file_put_contents( LOG_DIR .'limmes.log','func my_custom_dimensions_price_adjustment- product_id_for_acf:' . $product_id_for_acf .PHP_EOL, FILE_APPEND);
		// file_put_contents( LOG_DIR .'limmes.log','func my_custom_dimensions_price_adjustment- type:' . $product_type_dimensions .PHP_EOL, FILE_APPEND);
		// file_put_contents( LOG_DIR .'limmes.log','func my_custom_dimensions_price_adjustment - calculated_price - base_price:' . $calculated_price .PHP_EOL, FILE_APPEND);

		// 1) SQM
		if ( $product_type_dimensions === "sqm" ) {
			// file_put_contents( LOG_DIR .'limmes.log','func my_custom_dimensions_price_adjustment sqm1 - calculated_price:' . $calculated_price .PHP_EOL, FILE_APPEND);

			$width_cm  = isset( $cart_item['prod_width'] )  ? (float) $cart_item['prod_width']  : 0;
			$height_cm = isset( $cart_item['prod_height'] ) ? (float) $cart_item['prod_height'] : 0;

			if ( $width_cm > 0 && $height_cm > 0 ) {
				// file_put_contents( LOG_DIR .'limmes.log','func my_custom_dimensions_price_adjustment sqm2- calculated_price:' . $calculated_price .PHP_EOL, FILE_APPEND);

				$width_m  = $width_cm / 100;
				$height_m = $height_cm / 100;
				$area_m2  = $width_m * $height_m;
				// Minimum area calculation
				$min_area = 1; // Default minimum 1 sqm
				// Optional: Get product-specific minimum area if needed via ACF
				// $min_area_acf = get_field('min_sqm_area', $product_id_for_acf);
				// if ($min_area_acf && is_numeric($min_area_acf)) $min_area = (float)$min_area_acf;

				if ( $area_m2 < $min_area ) {
					$area_m2 = $min_area;
				}
				$calculated_price = $base_price * $area_m2;

				// file_put_contents( LOG_DIR .'limmes.log','func my_custom_dimensions_price_adjustment sqm3 - calculated_price:' . $calculated_price .PHP_EOL, FILE_APPEND);



				// --- ADDON LOGIC FOR SQM (copied, verify logic carefully) ---
				$meter_addon_total     = 0;
				$non_meter_addon_total = 0;
				if ( isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) ) {
					foreach ( $cart_item['addons'] as $addon ) {
						if ( isset( $addon['price'] ) && $addon['price_type'] !== 'quantity_based' ) { // Exclude quantity based addons here
							$price = (float) $addon['price'];
							$addon_name = isset( $addon['name'] ) ? trim( $addon['name'] ) : '';

							// Check price type: percentage or flat fee
							$is_percentage = $addon['price_type'] === 'percentage_based';

							if ( mb_strpos( $addon_name, 'למטר' ) !== false ) {
								$meter_fee = $is_percentage ? ( $base_price * $price / 100 ) : $price;
								$meter_addon_total += $meter_fee;
							} else { // Treat 'למחיר' and others similarly
								$non_meter_fee = $is_percentage ? ( $calculated_price * $price / 100 ) : $price;
								$non_meter_addon_total += $non_meter_fee;
							}
						}
					}
				}
				// Apply addon costs
				$calculated_price = $calculated_price + $non_meter_addon_total + ( $meter_addon_total * $area_m2 );
				// --- END ADDON LOGIC FOR SQM ---
			}
		}
		// 2) RM
		elseif ( $product_type_dimensions === "rm" ) {
			$width_cm = isset( $cart_item['prod_width'] ) ? (float) $cart_item['prod_width'] : 0;
			if ( $width_cm > 0 ) {
				$width_m = $width_cm / 100;
				$calculated_price = $base_price * $width_m;

				// --- ADDON LOGIC FOR RM (copied, verify logic carefully) ---
				$meter_addon_total     = 0;
				$non_meter_addon_total = 0;
				if ( isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) ) {
					foreach ( $cart_item['addons'] as $addon ) {
						if ( isset( $addon['price'] ) && $addon['price_type'] !== 'quantity_based' ) { // Exclude quantity based addons here
							$price = (float) $addon['price'];
							$addon_name = isset( $addon['name'] ) ? trim( $addon['name'] ) : '';

							// Check price type: percentage or flat fee
							$is_percentage = $addon['price_type'] === 'percentage_based';

							if ( mb_strpos( $addon_name, 'למטר' ) !== false ) {
								$meter_fee = $is_percentage ? ( $base_price * $price / 100 ) : $price;
								$meter_addon_total += $meter_fee;
							} else { // Treat 'למחיר' and others similarly
								$non_meter_fee = $is_percentage ? ( $calculated_price * $price / 100 ) : $price;
								$non_meter_addon_total += $non_meter_fee;
							}
						}
					}
				}
				// Apply addon costs
				$calculated_price = $calculated_price + $non_meter_addon_total + ( $meter_addon_total * $width_m );
				// --- END ADDON LOGIC FOR RM ---
			}
		}
		// 3) ROLL - Now with 5% extra margin
		elseif ( $product_type_dimensions === "roll" ) {
			// Base price for a roll product IS the price per roll
			$price_per_roll = $base_price;

			if ( isset( $cart_item['prod_coverage'] ) ) {
				$coverage_needed_sqm = (float) $cart_item['prod_coverage']; // m²

				// Add 5% extra margin to the required coverage area
				$coverage_with_margin_sqm = $coverage_needed_sqm * 1.05;

				// Get roll dimensions from ACF (stored in centimeters)
				$roll_width_cm  = (float) get_field( 'roll_width', $product_id_for_acf );
				$roll_length_cm = (float) get_field( 'roll_length', $product_id_for_acf );

				// Calculate area of one roll in m² (convert cm to meters)
				$roll_area_sqm = 0;
				if ( $roll_width_cm > 0 && $roll_length_cm > 0 ) {
					$roll_area_sqm = ($roll_width_cm / 100) * ($roll_length_cm / 100);
				}

				// Determine how many rolls are needed (ceiling) based on coverage WITH margin
				if ( $roll_area_sqm <= 0 ) {
					$rolls_needed = 1; // Fallback: if roll dimensions are invalid, assume 1 roll needed
					wc_get_logger()->warning( sprintf('Product ID %d: Invalid roll dimensions (W: %s, L: %s) or area (%s). Falling back to 1 roll.', $product_id_for_acf, $roll_width_cm, $roll_length_cm, $roll_area_sqm), array( 'source' => 'my-custom-pricing' ) );
				} else {
					// Use the coverage with margin to calculate rolls needed
					$rolls_needed = ceil( $coverage_with_margin_sqm / $roll_area_sqm );
					// Ensure at least 1 roll is ordered if coverage is > 0
					if ($coverage_needed_sqm > 0 && $rolls_needed < 1) {
						$rolls_needed = 1;
					}
				}

				// Update the 'rolls_needed' in the cart item data for display purposes
				// This requires modifying the $cart_item directly, which is generally okay within this hook
				WC()->cart->cart_contents[ $cart_item_key ]['rolls_needed'] = $rolls_needed;

				// Final price: price per roll * # of rolls needed
				$calculated_price = $price_per_roll * $rolls_needed;

				// Add-on logic for Roll (assuming addons apply per roll or to total?)
				// This needs clarification - applying per roll here.
				$addon_total_per_roll = 0;
				if ( isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) ) {
					foreach ( $cart_item['addons'] as $addon ) {
						if ( isset( $addon['price'] ) && $addon['price_type'] !== 'quantity_based' ) {
							$price = (float) $addon['price'];
							$is_percentage = $addon['price_type'] === 'percentage_based';
							// Apply addon price per roll or based on total roll price? Assuming per roll.
							$addon_total_per_roll += $is_percentage ? ( $price_per_roll * $price / 100 ) : $price;
						}
					}
					// Apply total addon cost across all rolls
					$calculated_price += ($addon_total_per_roll * $rolls_needed);
				}
			} else {
				// If coverage isn't set for a roll product, maybe default to 1 roll price? Or log error.
				$calculated_price = $price_per_roll; // Default to price of 1 roll
				wc_get_logger()->warning( sprintf('Product ID %d (Roll): Coverage not set. Defaulting to price for 1 roll.', $product_id_for_acf), array( 'source' => 'my-custom-pricing' ) );
			}
		}

		// Set the final calculated price in the cart item
		$cart_item['data']->set_price( $calculated_price );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'my_custom_dimensions_price_adjustment', 25, 1 ); // Increased priority, ensure it runs after addons might modify base_price


/**
 * Ensure items added to cart meet minimum price threshold by adjusting price if needed
 * Instead of preventing items from being added, we'll adjust their price to meet the minimum
 */

// Remove the existing validation hooks that prevent adding to cart
remove_filter('woocommerce_add_to_cart_validation', 'check_minimum_price_before_add', 99);
remove_filter('woocommerce_ajax_add_to_cart_validation', 'ajax_min_price_validation', 10);

// Global setting for notifications - can be changed anywhere in the code
$show_min_price_adjustment_notice = false;

// Hook into cart item data to store information about minimum price requirement
add_filter('woocommerce_add_cart_item_data', 'store_minimum_price_data', 30, 3);

function store_minimum_price_data($cart_item_data, $product_id, $variation_id = 0) {
	// Get the product
	$product = wc_get_product($product_id);

	// Get minimum price from ACF field
	$min_price = get_field('pro_order_min_price', $product_id);

	// If min price is set, store it in cart item data
	if (!empty($min_price)) {
		$cart_item_data['min_price_threshold'] = (float)$min_price;
	}

	return $cart_item_data;
}

// Hook into price calculation to enforce minimum price
add_action('woocommerce_before_calculate_totals', 'enforce_minimum_price_threshold', 30, 1);

/**
 * Enforce minimum price threshold by adjusting item prices if needed
 * 
 * @param WC_Cart $cart The cart object
 * @param bool $show_notice Whether to show notice to the customer about price adjustment (default: true)
 */
function enforce_minimum_price_threshold($cart, $show_notice = false) {
	if (is_admin() && !defined('DOING_AJAX')) {
		return;
	}

	// Make sure WC()->cart is loaded
	if (!$cart instanceof WC_Cart) {
		$cart = WC()->cart;
		if (!$cart) return;
	}

	foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
		// Skip if no minimum price threshold is set
		if (!isset($cart_item['min_price_threshold'])) {
			continue;
		}

		$min_price = $cart_item['min_price_threshold'];
		$current_price = $cart_item['data']->get_price();
		$quantity = $cart_item['quantity'];

		// Calculate total price for this item with current quantity
		$total_price = $current_price * $quantity;

		// If total price is below minimum, adjust the per-item price
		if ($total_price < $min_price) {
			// Calculate the new per-item price to meet minimum threshold
			$adjusted_price = $min_price / $quantity;

			// Set the adjusted price
			$cart_item['data']->set_price($adjusted_price);

			// Add notice only if notifications are enabled
			if ($show_notice && !isset($cart_item['min_price_applied'])) {
				wc_add_notice(
					sprintf('המחיר עודכן ל-%s כדי לעמוד במחיר המינימום להזמנה של %s',
							wc_price($min_price),
							wc_price($min_price)
						   ),
					'notice'
				);

				// Mark this item as having had the minimum price applied to avoid duplicate notices
				WC()->cart->cart_contents[$cart_item_key]['min_price_applied'] = true;
			} else {
				// Still mark this item to track that minimum price was applied, even if no notice is shown
				WC()->cart->cart_contents[$cart_item_key]['min_price_applied'] = true;
			}
		}
	}
}

// Optional: Add a more visible notice on the cart page explaining the price adjustment
add_action('woocommerce_before_cart', 'display_minimum_price_notice');
add_action('woocommerce_before_checkout_form', 'display_minimum_price_notice');

/**
 * Display a notice about minimum price adjustment on cart and checkout pages
 * 
 * @param bool $show_notice Whether to show notice to the customer (default: true)
 */
function display_minimum_price_notice($show_notice = true) {
	// Skip if notifications are disabled
	if (!$show_notice) {
		return;
	}

	if (!is_admin() && WC()->cart) {
		foreach (WC()->cart->get_cart() as $cart_item) {
			if (isset($cart_item['min_price_applied']) && $cart_item['min_price_applied']) {
				$product_name = $cart_item['data']->get_name();
				$min_price = $cart_item['min_price_threshold'];

				wc_print_notice(
					sprintf('המחיר של "%s" הותאם כדי לעמוד במחיר המינימום להזמנה של %s',
							esc_html($product_name),
							wc_price($min_price)
						   ),
					'notice'
				);

				// Only show once
				break;
			}
		}
	}
}

// To change the notification setting at any point, you can call the functions with false:
// add_action('init', function() {
//     // Remove the default action
//     remove_action('woocommerce_before_calculate_totals', 'enforce_minimum_price_threshold', 30);
//     
//     // Add it back with notifications disabled
//     add_action('woocommerce_before_calculate_totals', function($cart) {
//         enforce_minimum_price_threshold($cart, false);
//     }, 30);
//     
//     // Also disable notices on cart/checkout pages
//     remove_action('woocommerce_before_cart', 'display_minimum_price_notice');
//     remove_action('woocommerce_before_checkout_form', 'display_minimum_price_notice');
// });

/**  
 * 1) Set up our hooks  
 */
add_action('wp_footer',                          'add_dimensional_price_calculator_js');
add_filter('woocommerce_get_price_html',         'custom_dimensional_price_display',          10, 2);
add_filter('woocommerce_variation_price_html',   'force_min_price_variation_html',            10, 2);
add_filter('woocommerce_before_calculate_totals','enforce_minimum_total_before_checkout',     10, 1);
add_filter('woocommerce_add_to_cart_validation','validate_minimum_price_before_add_to_cart', 10, 5);
add_action('woocommerce_before_calculate_totals','set_calculated_price_in_cart',             20, 1);
add_filter('woocommerce_product_get_price',       'ensure_dimensional_price_update',           99, 2);
add_filter('woocommerce_add_cart_item_data',      'add_dimensions_to_cart_item_data',         10, 3);
add_filter('woocommerce_get_item_data',           'display_dimensions_in_cart',               10, 2);

/**
 * Front-end JS: dimensions → price (always at least minPrice)
 * Updated implementation with better handling of different product types and validation
 */
function add_dimensional_price_calculator_js() {
    if ( ! is_product() ) {
        return;
    }
    
    // Get current product and currency symbol
    $product = wc_get_product(get_the_ID());
    $currency_symbol = get_woocommerce_currency_symbol();
    $product_type_dimensions = get_field('product_type_dimensions', get_the_ID());
    ?>
    <script>
    jQuery(function($){
        console.log('🚀 Dimensional pricing calculator initialized');

        // 1) Cache DOM elements and initial state
        var $form       = $('form.variations_form'),
            $w          = $('#prod_width'),
            $h          = $('#prod_height'),
            $coverage   = $('#prod_coverage'),
            $rollsNeeded = $('#prod_rolls_needed'),
            $rollWidth  = $('#roll_width'),
            $rollLength = $('#roll_length'),
            $dims       = $w.add($h),
            basePrice   = parseFloat($('#base_price').data('base-price')) 
                         || <?php echo json_encode((float)$product->get_price()); ?>,
            minPrice    = <?php echo json_encode((float)get_field('pro_order_min_price', get_the_ID())); ?>,
            $priceMain  = $('.woocommerce-variation-price .price, p.price'),
            $priceLbl   = $('.always-visible-final-price-label'),
            $calcInput  = $('input[name="calculated_price"]').length ? 
                          $('input[name="calculated_price"]') : 
                          $('<input type="hidden" name="calculated_price">').appendTo($form),
            $addons     = $('#product-addons-total'),
            currSymbol  = '<?php echo $currency_symbol; ?>',
            debounceTimer,
            productType = '<?php echo $product_type_dimensions; ?>', // 'sqm', 'rm', or 'roll'
            priceDisplayed = false;

        // Detect product type if not explicitly set
        if (!productType) {
            if ($coverage.length > 0 && $rollsNeeded.length > 0) {
                productType = 'roll';
                console.log('🔍 Detected product type: roll based on form fields');
            } else if ($w.length > 0 && $h.length > 0) {
                productType = 'sqm';
                console.log('🔍 Detected product type: sqm based on form fields');
            } else if ($w.length > 0) {
                productType = 'rm';
                console.log('🔍 Detected product type: rm based on form fields');
            }
        }

        console.log('💰 Initial pricing data:', { 
            basePrice: basePrice, 
            minPrice: minPrice, 
            productType: productType,
            hasCoverage: $coverage.length > 0,
            hasWidth: $w.length > 0,
            hasHeight: $h.length > 0
        });

        // 2) Check if fields are valid for price calculation
        function areFieldsValid() {
            // First check if this is a variable product and if variation is selected
            if ($form.hasClass('variations_form')) {
                // Check if color attribute exists on this product
                var hasColorAttribute = $('input[name="attribute_pa_color"]').length > 0 || 
                                       $('select[name="attribute_pa_color"]').length > 0;
                
                if (hasColorAttribute) {
                    // Check color attribute selection (both radio and select)
                    var colorSelected = $('input[name="attribute_pa_color"]:checked').val() || 
                                       $('select[name="attribute_pa_color"]').val();
                    
                    if (!colorSelected) {
                        console.log('🛑 Color variation not selected yet');
                        return false; // Don't show price until color is selected
                    }
                    
                    console.log('✅ Color variation selected:', colorSelected);
                }
            }
            
            // Check required mechanism and installation fields (only for products that have them)
            var hasMechanismField = $('input[name="prod_radio-gr2"]').length > 0;
            var hasInstallationField = $('input[name="prod_radio-gr1"]').length > 0;
            
            if (hasMechanismField) {
                var mechanismSelected = $('input[name="prod_radio-gr2"]:checked').length > 0;
                if (!mechanismSelected) {
                    console.log('🛑 Mechanism side not selected yet');
                    return false;
                }
            }
            
            if (hasInstallationField) {
                var installationSelected = $('input[name="prod_radio-gr1"]:checked').length > 0;
                if (!installationSelected) {
                    console.log('🛑 Installation type not selected yet');
                    return false;
                }
            }
            
            if (hasMechanismField || hasInstallationField) {
                console.log('✅ Required selection fields are complete');
            }
            
            // Now check dimension fields based on product type
            if (productType === 'roll') {
                // For roll products, we just need the coverage field to have a value > 0
                var coverage = parseFloat($coverage.val()) || 0;
                var minCoverage = parseFloat($coverage.attr('min')) || 0.1;
                
                console.log('🧮 Validating roll fields:', { 
                    coverage: coverage, 
                    minCoverage: minCoverage,
                    isValid: coverage >= minCoverage
                });
                
                return coverage >= minCoverage;
            } else {
                // For sqm and rm products, we need both width and height (for sqm) or at least width (for rm)
                var w = parseFloat($w.val()) || 0;
                var h = parseFloat($h.val()) || 0;
                var minWidth = parseFloat($w.attr('min')) || 0;
                var minHeight = parseFloat($h.attr('min')) || 0;
                
                if (productType === 'sqm') {
                    var isValid = w >= minWidth && h >= minHeight;
                    
                    console.log('🧮 Validating sqm fields:', { 
                        width: w, 
                        height: h, 
                        minWidth: minWidth,
                        minHeight: minHeight,
                        isValid: isValid
                    });
                    
                    return isValid;
                } else if (productType === 'rm') {
                    var isValid = w >= minWidth;
                    
                    console.log('🧮 Validating rm fields:', { 
                        width: w, 
                        minWidth: minWidth,
                        isValid: isValid
                    });
                    
                    return isValid;
                }
            }
            return false;
        }

        // 3) Compute dimensional price with minimum enforcement
        function calculatePrice() {
            if (productType === 'roll') {
                // Roll calculation
                var coverage = parseFloat($coverage.val()) || 0;
                
                if (coverage <= 0) {
                    console.log('📏 Invalid coverage, using minPrice:', minPrice);
                    return minPrice;
                }
                
                // Get roll dimensions
                var rollWidth = parseFloat($rollWidth.val()) || 0;
                var rollLength = parseFloat($rollLength.val()) || 0;
                var rollArea = 0;
                
                if (rollWidth > 0 && rollLength > 0) {
                    // Convert cm to m
                    rollArea = (rollWidth / 100) * (rollLength / 100);
                }
                
                // Calculate how many rolls are needed
                var rollsNeeded = 1;
                if (rollArea > 0) {
                    // Add 5% margin to coverage
                    var coverageWithMargin = coverage * 1.05;
                    rollsNeeded = Math.ceil(coverageWithMargin / rollArea);
                    if (rollsNeeded < 1) rollsNeeded = 1;
                    
                    // Update the rolls needed field
                    $rollsNeeded.val(rollsNeeded);
                }
                
                // Calculate raw price based on rolls needed
                var rawPrice = basePrice * rollsNeeded;
                
                // Enforce minimum price
                var finalPrice = rawPrice < minPrice ? minPrice : rawPrice;
                
                console.log('💰 Roll price calculation:', { 
                    coverage: coverage.toFixed(2) + ' m²', 
                    coverageWithMargin: (coverage * 1.05).toFixed(2) + ' m²',
                    rollWidth: rollWidth + ' cm',
                    rollLength: rollLength + ' cm',
                    rollArea: rollArea.toFixed(2) + ' m²',
                    rollsNeeded: rollsNeeded,
                    basePrice: basePrice, 
                    rawPrice: rawPrice.toFixed(2), 
                    minPrice: minPrice,
                    finalPrice: finalPrice.toFixed(2) 
                });
                
                return finalPrice;
            } else {
                // SQM or RM calculation
                var w = parseFloat($w.val()) || 0;
                var h = parseFloat($h.val()) || 0;
                
                if (productType === 'sqm' && (w <= 0 || h <= 0)) {
                    console.log('📏 Invalid dimensions for SQM, using minPrice:', minPrice);
                    return minPrice;
                } else if (productType === 'rm' && w <= 0) {
                    console.log('📏 Invalid width for RM, using minPrice:', minPrice);
                    return minPrice;
                }
                
                // Calculate based on product type
                var rawPrice;
                if (productType === 'sqm') {
                    // Calculate area in square meters (converting from cm to m)
                    var area = (w/100) * (h/100);
                    
                    // Apply 1 sqm minimum if needed
                    var minArea = 1;  // 1 sqm minimum
                    if (area < minArea) {
                        area = minArea;
                    }
                    
                    rawPrice = basePrice * area;
                    
                    console.log('💰 SQM price calculation:', { 
                        width: w, 
                        height: h, 
                        area: area.toFixed(2) + ' m²', 
                        minArea: minArea + ' m²',
                        basePrice: basePrice, 
                        rawPrice: rawPrice.toFixed(2)
                    });
                } else if (productType === 'rm') {
                    // Calculate running meters (converting from cm to m)
                    var width_m = w/100;
                    rawPrice = basePrice * width_m;
                    
                    console.log('💰 RM price calculation:', { 
                        width: w, 
                        width_m: width_m.toFixed(2) + ' m', 
                        basePrice: basePrice, 
                        rawPrice: rawPrice.toFixed(2)
                    });
                }
                
                // Enforce minimum price
                var finalPrice = rawPrice < minPrice ? minPrice : rawPrice;
                
                console.log('💰 Final price with min check:', {
                    rawPrice: rawPrice.toFixed(2),
                    minPrice: minPrice,
                    finalPrice: finalPrice.toFixed(2)
                });
                
                return finalPrice;
            }
        }

        // 4) Create price HTML for consistent formatting
        function getPriceHTML(price) {
            return '<span class="woocommerce-Price-amount amount">' +
                   '<bdi>' + price.toFixed(2) + ' ' +
                   '<span class="woocommerce-Price-currencySymbol">' + currSymbol + '</span>' +
                   '</bdi></span>';
        }

        // 5) Calculate total with addons
        function calculateTotalWithAddons() {
            // Get base dimensional price (with minimum price applied)
            var baseTotal = calculatePrice();
            var addonTotal = 0;
            var qty = parseInt($('input.qty').val(), 10) || 1;
            
            console.log('📊 Starting addon calculation with base price:', baseTotal);
            
            // Process select dropdowns first
            $('select.wc-pao-addon-select').each(function() {
                var $select = $(this);
                var $selected = $select.find('option:selected');
                
                // Skip if nothing selected or default option
                if (!$selected.length || $selected.val() === '') {
                    return;
                }
                
                var addonPrice = parseFloat($selected.data('price')) || 0;
                var priceType = $selected.data('price-type') || 'flat_fee';
                var addonLabel = $selected.data('label') || $selected.text();
                
                if (addonPrice <= 0) {
                    return;
                }
                
                var thisAddonTotal = 0;
                
                // Calculate based on price type
                if (priceType === 'percentage_based') {
                    thisAddonTotal = (baseTotal * addonPrice / 100);
                } else if (priceType === 'quantity_based') {
                    thisAddonTotal = (addonPrice * qty);
                } else {
                    // flat_fee or default
                    thisAddonTotal = addonPrice;
                }
                
                addonTotal += thisAddonTotal;
                
                console.log('🧩 Select addon:', {
                    name: addonLabel,
                    price: addonPrice,
                    type: priceType,
                    contribution: thisAddonTotal.toFixed(2)
                });
            });
            
            // Process checkboxes and radio buttons
            $('input.wc-pao-addon-checkbox:checked, input.wc-pao-addon-radio:checked').each(function() {
                var $input = $(this);
                var addonPrice = parseFloat($input.data('price')) || 0;
                var priceType = $input.data('price-type') || 'flat_fee';
                var addonLabel = $input.data('label') || $input.attr('name');
                
                if (addonPrice <= 0) {
                    return;
                }
                
                var thisAddonTotal = 0;
                
                // Calculate based on price type
                if (priceType === 'percentage_based') {
                    thisAddonTotal = (baseTotal * addonPrice / 100);
                } else if (priceType === 'quantity_based') {
                    thisAddonTotal = (addonPrice * qty);
                } else {
                    // flat_fee or default
                    thisAddonTotal = addonPrice;
                }
                
                addonTotal += thisAddonTotal;
                
                console.log('🧩 Input addon:', {
                    name: addonLabel,
                    price: addonPrice,
                    type: priceType,
                    contribution: thisAddonTotal.toFixed(2)
                });
            });
            
            // Check for text/textarea inputs with prices
            $('input.wc-pao-addon-custom-price, textarea.wc-pao-addon-custom').each(function() {
                var $input = $(this);
                var addonPrice = parseFloat($input.data('price')) || 0;
                var priceType = $input.data('price-type') || 'flat_fee';
                var addonLabel = $input.data('label') || $input.attr('name');
                
                if (addonPrice <= 0 || !$input.val()) {
                    return;
                }
                
                var thisAddonTotal = 0;
                
                // Calculate based on price type
                if (priceType === 'percentage_based') {
                    thisAddonTotal = (baseTotal * addonPrice / 100);
                } else if (priceType === 'quantity_based') {
                    thisAddonTotal = (addonPrice * qty);
                } else {
                    // flat_fee or default
                    thisAddonTotal = addonPrice;
                }
                
                addonTotal += thisAddonTotal;
                
                console.log('🧩 Custom addon:', {
                    name: addonLabel,
                    price: addonPrice,
                    type: priceType,
                    contribution: thisAddonTotal.toFixed(2)
                });
            });
            
            var finalTotal = baseTotal + addonTotal;
            
            console.log('💰 Total calculation:', {
                basePrice: baseTotal.toFixed(2),
                addonTotal: addonTotal.toFixed(2),
                finalTotal: finalTotal.toFixed(2)
            });
            
            return {
                basePrice: baseTotal,
                addonTotal: addonTotal,
                finalTotal: finalTotal
            };
        }

        // 6) Update all UI elements with calculated price
        function updateAllPrices() {
            // Check if fields are valid before showing price
            if (!areFieldsValid()) {
                console.log('🛑 Fields not valid yet, hiding all price displays');
                
                // Hide the price label
                $priceLbl.empty();
                
                // Hide the addons container completely when fields are not valid
                $('#product-addons-total').hide();
                $('.wc-pao-addons-container').hide();
                
                // Clear main price display
                $priceMain.empty();
                
                // Update flag to indicate price is not displayed
                priceDisplayed = false;
                return;
            }
            
            var priceData = calculateTotalWithAddons();
            var finalPrice = priceData.finalTotal;
            var priceHTML = getPriceHTML(finalPrice);
            
            console.log('🔄 Updating UI with final price:', finalPrice);

            // Show the addons container now that fields are valid
            $('#product-addons-total').show();
            $('.wc-pao-addons-container').show();

            // Update main price display
            $priceMain.html(priceHTML);
            
            // Update price label
            $priceLbl.html('מחיר סופי: ' + priceHTML);
            
            // Update hidden input for form submission
            $calcInput.val(finalPrice);
            
            // Update flag to indicate price is displayed
            priceDisplayed = true;
            
            // Update add-ons container attributes for compatibility
            if ($addons.length) {
                // Store original prices for addons
                var addonPriceInfo = {};
                
                // Collect information about selected addons for later use
                $('select.wc-pao-addon-select').each(function() {
                    var $select = $(this);
                    var $selected = $select.find('option:selected');
                    
                    if (!$selected.length || $selected.val() === '') {
                        return;
                    }
                    
                    var addonPrice = parseFloat($selected.data('price')) || 0;
                    var priceType = $selected.data('price-type') || 'flat_fee';
                    var addonLabel = $selected.data('label') || $selected.text();
                    
                    if (addonPrice <= 0) {
                        return;
                    }
                    
                    // Store the information for this addon
                    addonPriceInfo[addonLabel] = {
                        price: addonPrice,
                        type: priceType
                    };
                });
                
                // Set base price without addons for the addons calculation to work from
                $addons
                  .attr('data-price', priceData.basePrice)
                  .attr('data-raw-price', priceData.basePrice);
                
                // Let addons plugin do its own calculations first
                $(document.body).trigger('update_addon_totals');
                
                // After addons plugin updates, ensure our calculations are used
                setTimeout(function(){
                    // Find the base product price display and update it
                    var $baseAmt = $addons.find('.wc-pao-col2 .amount').first();
                    if ($baseAmt.length) {
                        $baseAmt.html(priceData.basePrice.toFixed(2) + ' ' + currSymbol);
                    }
                    
                    // Fix individual addon price displays
                    $addons.find('li').each(function(index) {
                        if (index === 0) return; // Skip the first item (base product)
                        if ($(this).hasClass('wc-pao-subtotal-line')) return; // Skip the total line
                        
                        var addonName = $(this).find('.wc-pao-col1 strong').text().trim();
                        var $addonPrice = $(this).find('.wc-pao-col2 .amount');
                        
                        if (addonPriceInfo[addonName]) {
                            var info = addonPriceInfo[addonName];
                            
                            if (info.type === 'percentage_based') {
                                // For percentage, calculate the actual amount and display it
                                var percentAmount = priceData.basePrice * (info.price / 100);
                                $addonPrice.html(percentAmount.toFixed(2) + ' ' + currSymbol);
                                console.log('📊 Fixed percentage addon display:', addonName, percentAmount.toFixed(2));
                            }
                        }
                    });
                    
                    // Find the subtotal price (base + addons) and update it
                    var $subTotal = $addons.find('.wc-pao-subtotal-line .price');
                    if ($subTotal.length) {
                        $subTotal.html(finalPrice.toFixed(2) + ' ' + currSymbol);
                    }
                }, 100);
            }
        }

        // 7) Debounced update handler for better performance
        function requestUpdate() {
            console.log('⌛ Update requested');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updateAllPrices, 50);
        }

        // 8) Event Bindings

        // Dimension input changes (width and height)
        $dims.on('input', function() {
            console.log('📏 Dimension changed:', this.id, $(this).val());
            requestUpdate();
        });

        // Coverage input for roll products
        $coverage.on('input', function() {
            console.log('📏 Coverage changed:', $(this).val());
            requestUpdate();
        });

        // Color variation changes (both radio buttons and select dropdowns)
        $(document).on('change', 'input[name="attribute_pa_color"], select[name="attribute_pa_color"]', function(){
            console.log('🎨 Color variation changed:', $(this).val());
            requestUpdate();
        });

        // Mechanism side selection changes
        $(document).on('change', 'input[name="prod_radio-gr2"]', function(){
            console.log('⚙️ Mechanism side changed:', $(this).val());
            requestUpdate();
        });

        // Installation type selection changes
        $(document).on('change', 'input[name="prod_radio-gr1"]', function(){
            console.log('🔧 Installation type changed:', $(this).val());
            requestUpdate();
        });

        // Variation changes
        $form.on('found_variation', function(e, variation){
            console.log('🔄 Variation changed:', variation);
            
            // Update base price from variation
            basePrice = variation.display_price || basePrice;
            
            // Update min price if provided in variation data
            if (variation.min_price) {
                minPrice = variation.min_price;
            }
            
            requestUpdate();
        });

        // Reset to defaults on variation reset
        $form.on('reset_data', function(){
            console.log('🔄 Variation reset to defaults');
            basePrice = <?php echo json_encode((float)$product->get_price()); ?>;
            minPrice = <?php echo json_encode((float)get_field('pro_order_min_price', get_the_ID())); ?>;
            requestUpdate();
        });

        // Listen for any change in the variations form (except dimension fields which are handled separately)
        $form.on('input change', 'input:not(#prod_width,#prod_height,#prod_coverage,input[name="attribute_pa_color"],input[name="prod_radio-gr1"],input[name="prod_radio-gr2"]), select:not(select[name="attribute_pa_color"]), textarea', function(){
            console.log('📝 Form field changed:', this.name || this.id);
            requestUpdate();
        });

        // Add-on field changes
        $(document).on('change', '.wc-pao-addon-field', function(){
            console.log('🔧 Add-on changed');
            requestUpdate();
        });

        // Listen for addon totals update event
        $(document.body).on('update_addon_totals', function(){
            console.log('🔄 Caught update_addon_totals event');
            requestUpdate();
        });

        // Custom add-to-cart button handler
        $('.add_to_cart_trigger_btn').on('click', function(e){
            e.preventDefault();
            
            // Validate dimensions before submitting
            if (!areFieldsValid()) {
                if (productType === 'roll') {
                    alert('נא להזין שטח תקין.');
                    $coverage.focus();
                } else {
                    alert('נא להזין רוחב וגובה תקינים.');
                    $dims.first().focus();
                }
                return false;
            }
            
            // Set final calculated price before submission
            $calcInput.val(calculatePrice());
            
            // Submit the form
            $(this).closest('form').submit();
        });

        // 9) Initialize
        console.log('🚀 Running initial price calculation');
        updateAllPrices();
    });
    </script>
    <?php
}

/**
 * 3) Show the base price + min‑order info under the product price
 */
function custom_dimensional_price_display($price_html, $product){
	if ( ! is_product() ) return $price_html;
	
	// Only show dimensional pricing info for variable products
	if ( $product->is_type( 'simple' ) ) {
		return $price_html;
	}
	
	$min = get_field('pro_order_min_price', $product->get_id());
	if ( $min ){
		$price_html .= '<div style="margin-top:10px;font-size:0.9em;">'
			. '<span>המחיר הוא לפי מ״ר.</span><br>'
			. '<span>מחיר מינימלי להזמנה: ' . wc_price($min) . '</span>'
			. '</div>';
	}
	return $price_html;
}

/**
 * 4) Variation‐price HTML: never show below ACF min
 */
function force_min_price_variation_html($price_html, $variation){
	$min = get_field('pro_order_min_price', $variation->get_id());
	if ( $min && $variation->get_price() < $min ){
		return wc_price($min);
	}
	return $price_html;
}


/**
 * 5) Validate before add_to_cart
 */
function validate_minimum_price_before_add_to_cart($passed, $product_id, $qty, $variation_id = 0){
	$id   = $variation_id ?: $product_id;
	$min  = get_field('pro_order_min_price', $id);
	if ( ! $min ) return $passed;
	$p    = wc_get_product($id)->get_price();
	$w    = floatval($_POST['prod_width']  ?? 0);
	$h    = floatval($_POST['prod_height'] ?? 0);
	if ( $w<=0 || $h<=0 ){
		wc_add_notice('נא להזין רוחב וגובה תקינים.', 'error');
		return false;
	}
	$area = ($w/100)*($h/100);
	$tot  = $p * $area;
	if ( $tot < $min ){
		wc_add_notice(sprintf(
			'המחיר המינימלי להזמנה הוא %s. אנא התאם את המידות.',
			wc_price($min)
		), 'error');
		return false;
	}
	$_POST['calculated_price'] = $tot;
	return $passed;
}

/**
 * 6) Enforce on cart/cart‐totals
 */
function enforce_minimum_total_before_checkout($cart){
	if ( is_admin() && ! defined('DOING_AJAX') ) return;
	foreach ( $cart->get_cart() as &$item ){
		$id  = $item['variation_id'] ?: $item['product_id'];
		$min = get_field('pro_order_min_price', $id);
		if ( ! $min ) continue;
		$price = $item['calculated_price'] ?? $item['data']->get_price();
		$item['data']->set_price( max($price, $min) );
	}
}

/**
 * 7) Store dimensions+calc price in cart item
 */
function add_dimensions_to_cart_item_data($data, $product_id){
	if ( isset($_POST['prod_width'], $_POST['prod_height']) ){
		$data['dimensions'] = [
			'width'  => floatval($_POST['prod_width']),
			'height' => floatval($_POST['prod_height']),
		];
	}
	if ( isset($_POST['calculated_price']) ){
		$data['calculated_price'] = floatval($_POST['calculated_price']);
	}
	return $data;
}

/**
 * 8) Show dimensions in cart
 */
function display_dimensions_in_cart($item_data, $cart_item){
	if ( ! empty($cart_item['dimensions']) ){
		$w = $cart_item['dimensions']['width'];
		$h = $cart_item['dimensions']['height'];
		$item_data[] = ['key'=>'רוחב','value'=> $w.' ס״מ'];
		$item_data[] = ['key'=>'גובה','value'=> $h.' ס״מ'];
	}
	return $item_data;
}

/**
 * 9) Re‐set price on each cart‐totals pass
 */
function set_calculated_price_in_cart($cart){
	if ( is_admin() && ! defined('DOING_AJAX') ) return;
	if ( did_action('woocommerce_before_calculate_totals') > 1 ) return;
	foreach ( $cart->get_cart() as &$item ){
		if ( isset($item['calculated_price']) ){
			$item['data']->set_price($item['calculated_price']);
		} elseif ( ! empty($item['dimensions']) ){
			$base = $item['data']->get_price();
			$area = ($item['dimensions']['width']/100)*($item['dimensions']['height']/100);
			$tot  = $base*$area;
			$min  = get_field('pro_order_min_price',
							  $item['variation_id']?:$item['product_id']);
			if ( $min && $tot < $min ) $tot = $min;
			$item['data']->set_price($tot);
		}
	}
}

/**
 * 10) Ensure cart/checkout always shows our calculated price
 */
function ensure_dimensional_price_update($price, $product){
	if ( ! is_cart() && ! is_checkout() ) return $price;
	foreach ( WC()->cart->get_cart() as $item ){
		if ( $item['data']->get_id() === $product->get_id()
			&& isset($item['calculated_price']) ){
			return $item['calculated_price'];
		}
	}
	return $price;
}
