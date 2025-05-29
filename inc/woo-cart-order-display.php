<?php

/**
 * Display custom dimensions and attribute selections in the cart (name => value).
 */
function my_display_custom_dimensions_cart( $item_data, $cart_item ) {

    // Display selected variation attribute (e.g., Color) - Handled by WC Default if using 'attribute_pa_color' name

	// Width
	if ( isset( $cart_item['prod_width'] ) && $cart_item['prod_width'] !== '' ) {
		$item_data['prod_width'] = array( // Use unique key
			'key'     => 'רוחב הוילון',
			'display' => esc_html( $cart_item['prod_width'] . ' ס"מ' ),
		);
	}
	// Height
	if ( isset( $cart_item['prod_height'] ) && $cart_item['prod_height'] !== '' ) {
		$item_data['prod_height'] = array( // Use unique key
			'key'     => 'גובה הוילון',
			'display' => esc_html( $cart_item['prod_height'] . ' ס"מ' ),
		);
	}
	// Coverage area (if roll)
	if ( isset( $cart_item['prod_coverage'] ) && $cart_item['prod_coverage'] !== '' ) {
		$item_data['prod_coverage'] = array( // Use unique key
			'key'     => 'כמות כיסוי (מ"ר)',
			'display' => esc_html( number_format( (float)$cart_item['prod_coverage'], 2 ) ), // Format number
		);
	}
	// Rolls needed
	if ( isset( $cart_item['rolls_needed'] ) && $cart_item['rolls_needed'] !== '' ) {
		$item_data['rolls_needed'] = array( // Use unique key
			'key'     => 'גלילים נדרשים',
			'display' => esc_html( $cart_item['rolls_needed'] ),
		);
	}
	// Mechanism side
	if ( isset( $cart_item['prod_radio-gr2'] ) && $cart_item['prod_radio-gr2'] !== '' ) {
		$item_data['prod_radio_gr2'] = array( // Use unique key
			'key'     => 'צד מנגנון',
			'display' => esc_html( $cart_item['prod_radio-gr2'] ),
		);
	}
	// Mechanism install
	if ( isset( $cart_item['prod_radio-gr1'] ) && $cart_item['prod_radio-gr1'] !== '' ) {
		$item_data['prod_radio_gr1'] = array( // Use unique key
			'key'     => 'סוג התקנה',
			'display' => esc_html( $cart_item['prod_radio-gr1'] ),
		);
	}

    // Display calculated Area / Running Meters AFTER other dimensions
    $width_cm  = isset( $cart_item['prod_width'] )  ? (float) $cart_item['prod_width']  : 0;
	$height_cm = isset( $cart_item['prod_height'] ) ? (float) $cart_item['prod_height'] : 0;
    // Get product type again if needed
    $product_id_for_acf    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
    $product_type_dimensions = get_field( 'product_type_dimensions', $product_id_for_acf );

	// If SQM type and dimensions exist => show area
	if ( $product_type_dimensions === 'sqm' && $width_cm > 0 && $height_cm > 0 ) {
		$area_m2 = ( $width_cm / 100 ) * ( $height_cm / 100 );
		$min_area = 1; // Use same min area logic as price calculation
        // Optional: $min_area_acf = get_field('min_sqm_area', $product_id_for_acf); if ($min_area_acf && is_numeric($min_area_acf)) $min_area = (float)$min_area_acf;
		if ( $area_m2 < $min_area ) {
			$area_m2 = $min_area;
		}
		$formatted_area = number_format( $area_m2, 2 );
		$item_data['calculated_area'] = array( // Use unique key
			'key'     => 'שטח לחיוב (מ"ר)',
			'display' => $formatted_area . ' מ"ר',
		);
	}
	// If RM type and width exists => show running meter
	elseif ( $product_type_dimensions === 'rm' && $width_cm > 0 ) {
		$width_m = $width_cm / 100;
		$formatted_run = number_format( $width_m, 2 );
		$item_data['calculated_running_meter'] = array( // Use unique key
			'key'     => 'מטר רץ לחיוב',
			'display' => $formatted_run . ' מ׳',
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'my_display_custom_dimensions_cart', 10, 2 );


/**
 * Save custom dimensions and attribute selections as order item meta.
 */
function my_save_custom_dimensions_order( $item, $cart_item_key, $values, $order ) {

    // Color is usually saved automatically if variation attribute exists

	// Width
	if ( isset( $values['prod_width'] ) ) {
		$item->add_meta_data( 'רוחב הוילון', $values['prod_width'] . ' ס"מ' );
	}
	// Height
	if ( isset( $values['prod_height'] ) ) {
		$item->add_meta_data( 'גובה הוילון', $values['prod_height'] . ' ס"מ' );
	}
	// Coverage
	if ( isset( $values['prod_coverage'] ) ) {
		$item->add_meta_data( 'כמות כיסוי (מ"ר)', number_format( (float)$values['prod_coverage'], 2 ) );
	}
	// Rolls needed
	if ( isset( $values['rolls_needed'] ) ) {
		$item->add_meta_data( 'גלילים נדרשים', $values['rolls_needed'] );
	}
	// Mechanism side
	if ( isset( $values['prod_radio-gr2'] ) ) {
		$item->add_meta_data( 'צד מנגנון', $values['prod_radio-gr2'] );
	}
	// Mechanism install
	if ( isset( $values['prod_radio-gr1'] ) ) {
		$item->add_meta_data( 'סוג התקנה', $values['prod_radio-gr1'] );
	}

    // Save Calculated Area / Running Meter if present
    $width_cm  = isset( $values['prod_width'] )  ? (float) $values['prod_width']  : 0;
	$height_cm = isset( $values['prod_height'] ) ? (float) $values['prod_height'] : 0;
    $product_id_for_acf    = $values['variation_id'] ? $values['variation_id'] : $values['product_id'];
    $product_type_dimensions = get_field( 'product_type_dimensions', $product_id_for_acf );

    if ( $product_type_dimensions === 'sqm' && $width_cm > 0 && $height_cm > 0 ) {
		$area_m2 = ( $width_cm / 100 ) * ( $height_cm / 100 );
		$min_area = 1; // Use same min area logic
        // Optional: $min_area_acf = get_field('min_sqm_area', $product_id_for_acf); if ($min_area_acf && is_numeric($min_area_acf)) $min_area = (float)$min_area_acf;
		if ( $area_m2 < $min_area ) $area_m2 = $min_area;
		$item->add_meta_data( 'שטח לחיוב (מ"ר)', number_format( $area_m2, 2 ) . ' מ"ר' );
	}
	elseif ( $product_type_dimensions === 'rm' && $width_cm > 0 ) {
		$width_m = $width_cm / 100;
		$item->add_meta_data( 'מטר רץ לחיוב', number_format( $width_m, 2 ) . ' מ׳' );
	}

    // Base price used for calculation (for reference/debugging)
    if ( isset( $values['base_price'] ) ) {
        $item->add_meta_data( '_base_price', wc_format_decimal($values['base_price'], wc_get_price_decimals()), true ); // Hidden meta
    }

}
add_action( 'woocommerce_checkout_create_order_line_item', 'my_save_custom_dimensions_order', 10, 4 );


/**
 * Display the custom dimensions and attribute selections in order details (admin + front-end emails/view).
 * WC 3.0+ uses item meta data automatically. This function might be redundant unless you want specific formatting.
 * Keep it simple, just rely on WC's default display of meta data saved above.
 */
// add_filter( 'woocommerce_order_item_meta_end', 'my_display_custom_dimensions_order', 10, 4 ); // Might not be needed
// function my_display_custom_dimensions_order( $item_id, $item, $order, $plain_text ) {
//    // Meta data saved via add_meta_data should display automatically.
//    // You could add extra formatting here if required.
// }


/**
 * Display the calculated area (m²) or running meter in the cart if relevant.
 * This logic is now integrated into my_display_custom_dimensions_cart filter.
 */
// remove_filter( 'woocommerce_get_item_data', 'my_display_area_cart', 20, 2 ); // Remove the old separate function


/**
 * If product is running meter, append text in cart line item name.
 */
function custom_product_name_in_cart( $product_name, $cart_item, $cart_item_key ) {
    // Get product type from cart item if possible, fallback to querying DB
    $product_type_dimensions = '';
    $product_id_for_acf = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
    $product_type_dimensions = get_field( 'product_type_dimensions', $product_id_for_acf );

	if ( $product_type_dimensions === "rm" ) {
		// Append the text. Consider using a class for styling.
		$product_name .= ' <span class="run_meter_label">(מחיר לפי מטר רץ)</span>';
	}
    elseif ( $product_type_dimensions === "sqm" ) {
        $product_name .= ' <span class="sqm_label">(מחיר לפי מ"ר)</span>';
    }
     elseif ( $product_type_dimensions === "roll" ) {
        $product_name .= ' <span class="roll_label">(מחיר לגליל)</span>';
    }
	return $product_name;
}
add_filter( 'woocommerce_cart_item_name', 'custom_product_name_in_cart', 10, 3 );


/**
 * Debug info on the cart page (optional).
 */
//add_action( 'woocommerce_after_cart', 'my_debug_calculation_info' ); // Hook after cart totals for better visibility
function my_debug_calculation_info() {
	if ( ! current_user_can('manage_options') ) return; // Only show for admins

	if ( is_cart() || ( defined('DOING_AJAX') && DOING_AJAX ) ) { // Also show on AJAX updates
		echo '<div style="border:1px solid #f00; padding:15px; margin:20px 0; background:#fff; color:#333;">';
		echo '<h3><strong style="color:#f00;">DEBUG INFO (Admin Only)</strong></h3><pre style="white-space: pre-wrap; word-wrap: break-word;">';

		$cart = WC()->cart;
        if ( ! $cart ) { echo "WC Cart not available."; }
        elseif ( $cart->is_empty() ) { echo "Cart is empty."; }
        else {
            foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                echo "--- Item Key: {$cart_item_key} ---\n";
                echo "Product ID: " . $cart_item['product_id'] . "\n";
                if ( $cart_item['variation_id'] ) {
                    echo "Variation ID: " . $cart_item['variation_id'] . "\n";
                }
                echo "Product Name: " . $cart_item['data']->get_name() . "\n";
                echo "Quantity: " . $cart_item['quantity'] . "\n";
                echo "Stored Base Price (base_price): " . ( isset($cart_item['base_price']) ? wc_price($cart_item['base_price']) : 'N/A' ) . "\n";
                echo "Product Type Dimensions (ACF): " . ( get_field('product_type_dimensions', $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id']) ?: 'N/A' ) . "\n";

                if ( isset( $cart_item['prod_width'] ) ) echo "Width (cm): " . $cart_item['prod_width'] . "\n";
                if ( isset( $cart_item['prod_height'] ) ) echo "Height (cm): " . $cart_item['prod_height'] . "\n";
                if ( isset( $cart_item['prod_coverage'] ) ) {
                    echo "Coverage Input (m²): " . $cart_item['prod_coverage'] . "\n";
                    echo "Coverage +5% Margin (m²): " . number_format($cart_item['prod_coverage'] * 1.05, 2) . "\n";
                     $roll_w = get_field( 'roll_width', $cart_item['variation_id'] ?: $cart_item['product_id'] );
                     $roll_l = get_field( 'roll_length', $cart_item['variation_id'] ?: $cart_item['product_id'] );
                     echo "Roll Dimensions (WxL cm): " . $roll_w . 'x' . $roll_l . "\n";
                     if ($roll_w > 0 && $roll_l > 0) {
                         echo "Single Roll Area (m²): " . number_format(($roll_w/100)*($roll_l/100), 2) . "\n";
                     }
                }
                 if ( isset( $cart_item['rolls_needed'] ) ) echo "Calculated Rolls Needed: " . $cart_item['rolls_needed'] . "\n";

                if (isset($cart_item['addons']) && !empty($cart_item['addons'])) {
                    echo "Addons:\n";
                    foreach ($cart_item['addons'] as $addon) {
                        echo "  - " . $addon['name'] . ": " . $addon['value'] . " (Price: " . $addon['price'] . ", Type: " . $addon['price_type'] . ")\n";
                    }
                }

                echo "Final Calculated Item Price (set_price): " . wc_price($cart_item['data']->get_price()) . "\n";
                echo "Line Subtotal (Before Tax): " . wc_price($cart_item['line_subtotal']) . "\n";
                echo "Line Total (Before Tax): " . wc_price($cart_item['line_total']) . "\n";
                echo "---------------------------------\n";
            }
             echo "\nCart Subtotal: " . WC()->cart->get_cart_subtotal() . "\n";
             echo "Cart Total: " . WC()->cart->get_total('edit') . "\n"; // Raw total
        }

		echo '</pre></div>';
	}
}


/**
 * Add "Edit" link in admin bar if on the Shop page.
 */
add_action('admin_bar_menu', 'add_item', 100);
function add_item( $admin_bar ){
	if ( function_exists('is_shop') && is_shop() && current_user_can('edit_pages') ) { // Add capability check
		$id   = wc_get_page_id( 'shop' );
		if ( $id ) {
            $href = get_edit_post_link($id);
            if ( $href ) {
                $admin_bar->add_menu( array( 'id' => 'edit-shop-page', 'parent' => false, 'title' => 'עריכת עמוד חנות', 'href' => $href ) );
            }
        }
	}
    // Maybe add edit link for product page too?
    elseif ( is_product() && current_user_can('edit_products') ) {
        $product_id = get_the_ID();
        if ( $product_id ) {
             $href = get_edit_post_link($product_id);
             if ( $href ) {
                $admin_bar->add_menu( array( 'id' => 'edit-product-page', 'parent' => false, 'title' => 'עריכת מוצר', 'href' => $href ) );
            }
        }
    }
}

/**
 * Remove WooCommerce's noindex tags from pages.
 * Be careful with this, ensure you want WC pages indexed.
 */
add_action( 'init', 'remove_wc_page_noindex' );
function remove_wc_page_noindex(){
	// remove_action( 'wp_head', 'wc_page_noindex' ); // Deprecated?
	remove_filter( 'wp_robots', 'wc_page_no_robots' );
}

/* WooCommerce: The Code Below Removes Checkout Fields */
add_filter( 'woocommerce_checkout_fields', 'custom_override_checkout_fields', 20 ); // Use higher priority
function custom_override_checkout_fields( $fields ) {
	// Unset state field if not needed for your country/shipping setup
	// unset( $fields['billing']['billing_state'] );
    // unset( $fields['shipping']['shipping_state'] );

    // Example: Make company field not required
    // $fields['billing']['billing_company']['required'] = false;

    // --- Reorder Billing Fields ---
    // Lower number = higher priority = appears first
	$billing_order = array(
        "billing_first_name" => 10,
        "billing_last_name"  => 20,
        "billing_company"    => 30, // Optional
        "billing_country"    => 40,
        // "billing_state"      => 50, // If kept
        "billing_address_1"  => 60,
        "billing_address_2"  => 70, // Optional
        "billing_city"       => 80,
        "billing_postcode"   => 90, // Optional for some countries
        "billing_phone"      => 100, // Moved Phone before Email
        "billing_email"      => 110,
    );

    foreach($billing_order as $field_key => $priority) {
        if( isset($fields['billing'][$field_key]) ) {
            $fields['billing'][$field_key]['priority'] = $priority;
        }
    }

	return $fields;
}

// The second reordering function `custom_override_checkout_fields_fn` is redundant
// remove_filter("woocommerce_checkout_fields", "custom_override_checkout_fields_fn", 1);




















/**
 * Fix for percentage-based addon price display in the cart
 * This version supports multiple instances of the same product with different dimensions
 */

// Add our filter with a high priority to ensure it runs after others
add_filter('woocommerce_get_item_data', 'fix_percentage_addon_display_in_cart', 999, 2);

function fix_percentage_addon_display_in_cart($item_data, $cart_item) {
    // Skip if no addons
    if (empty($cart_item['addons'])) {
        return $item_data;
    }
    
    // We need to extract the true base price WITHOUT addons
    // for THIS SPECIFIC cart item with its unique dimensions
    $base_price = false;
    
    // If we have dimensions stored, recalculate the base price
    if (!empty($cart_item['dimensions'])) {
        $width = $cart_item['dimensions']['width'];
        $height = $cart_item['dimensions']['height'];
        
        // Get product or variation
        $product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
        $product = wc_get_product($product_id);
        
        if ($product) {
            // Get raw product price
            $price_per_unit = $product->get_price('edit');
            
            // Calculate area (sqm)
            $area = ($width/100) * ($height/100);
            
            // Calculate dimensional price
            $dimensional_price = $price_per_unit * $area;
            
            // Check for minimum price
            $min_price = get_field('pro_order_min_price', $product_id);
            if ($min_price && $dimensional_price < $min_price) {
                $dimensional_price = $min_price;
            }
            
            $base_price = $dimensional_price;
            error_log("Cart item {$cart_item['key']} - Calculated base price from dimensions: $base_price (width: $width, height: $height)");
        }
    }
    // Use other methods if dimensions aren't available
    elseif (isset($cart_item['base_price'])) {
        $base_price = $cart_item['base_price'];
        error_log("Cart item {$cart_item['key']} - Using stored base_price: $base_price");
    }
    elseif (isset($cart_item['calculated_price'])) {
        $base_price = $cart_item['calculated_price'];
        error_log("Cart item {$cart_item['key']} - Using calculated_price: $base_price");
    }
    else {
        // Reverse-engineer by removing addon amounts
        $current_price = $cart_item['data']->get_price();
        $total_addon_amount = 0;
        
        // Sum up fixed addon amounts
        foreach ($cart_item['addons'] as $addon) {
            if (isset($addon['price_type']) && $addon['price_type'] !== 'percentage_based') {
                $total_addon_amount += (float)$addon['price'];
            }
        }
        
        // Find percentage addons
        $percentage_addons = array();
        foreach ($cart_item['addons'] as $addon) {
            if (isset($addon['price_type']) && $addon['price_type'] === 'percentage_based') {
                $percentage_addons[] = (float)$addon['price'] / 100;
            }
        }
        
        // Reverse calculate the base price
        if (!empty($percentage_addons)) {
            // If there are percentage addons, solve for base price mathematically
            $percentage_sum = array_sum($percentage_addons);
            
            // Solve for base price
            $base_price = ($current_price - $total_addon_amount) / (1 + $percentage_sum);
            error_log("Cart item {$cart_item['key']} - Reverse-calculated base price: $base_price");
        } else {
            // If only fixed addons, just subtract them
            $base_price = $current_price - $total_addon_amount;
            error_log("Cart item {$cart_item['key']} - Subtracted fixed addons to get base price: $base_price");
        }
    }
    
    // If we still don't have a base price, use a fallback approach
    if (!$base_price) {
        error_log("Cart item {$cart_item['key']} - WARNING: Could not determine base price, using current price as fallback");
        $base_price = $cart_item['data']->get_price();
    }
    
    // Store the base price in the cart item for later use
    // This allows us to access it in other filters
    $cart_key = isset($cart_item['key']) ? $cart_item['key'] : '';
    if ($cart_key && isset(WC()->cart->cart_contents[$cart_key])) {
        WC()->cart->cart_contents[$cart_key]['correct_base_price'] = $base_price;
        foreach ($cart_item['addons'] as $idx => $addon) {
            if (isset($addon['price_type']) && $addon['price_type'] === 'percentage_based') {
                $percentage = (float)$addon['price'];
                $correct_price = $base_price * ($percentage / 100);
                WC()->cart->cart_contents[$cart_key]['addons'][$idx]['correct_display_price'] = $correct_price;
            }
        }
    }
    
    // Process all addons
    foreach ($cart_item['addons'] as $addon) {
        // Only process percentage-based addons
        if (!isset($addon['price_type']) || $addon['price_type'] !== 'percentage_based') {
            continue;
        }
        
        // Extract percentage value
        $percentage = (float)$addon['price'];
        
        // Calculate correct monetary value (percentage of BASE price)
        $correct_price = $base_price * ($percentage / 100);
        
        error_log("Cart item {$cart_item['key']} - Addon: {$addon['name']}, Percentage: $percentage%, Base Price: $base_price, Calculated: $correct_price");
        
        // Find the matching item_data entry for this addon
        foreach ($item_data as $key => $data) {
            // Check if this data item contains the addon name
            if (strpos($data['name'], $addon['name']) !== false) {
                // Found a match, now extract and replace price in parentheses
                if (preg_match('/\(.*?\)/', $data['name'], $matches)) {
                    $original_price_text = $matches[0];
                    $corrected_price_text = '(' . wc_price($correct_price) . ')';
                    
                    error_log("Cart item {$cart_item['key']} - Replacing {$original_price_text} with {$corrected_price_text}");
                    
                    // Replace the price text
                    $item_data[$key]['name'] = str_replace(
                        $original_price_text,
                        $corrected_price_text,
                        $data['name']
                    );
                }
                break;
            }
        }
    }
    
    return $item_data;
}

/**
 * Another approach - direct HTML modification for cart meta display
 * This version uses cart_item_key to ensure correct matching
 */
add_filter('woocommerce_cart_item_name', 'fix_cart_item_addon_display', 999, 3);

function fix_cart_item_addon_display($product_name, $cart_item, $cart_item_key) {
    // Only process on cart and checkout pages
    if (!is_cart() && !is_checkout()) {
        return $product_name;
    }
    
    // Skip if no addons
    if (empty($cart_item['addons'])) {
        return $product_name;
    }
    
    // Check if we have stored correct prices from previous filter
    $has_correct_prices = false;
    if (isset($cart_item['correct_base_price'])) {
        $base_price = $cart_item['correct_base_price'];
        $has_correct_prices = true;
    } else {
        // Calculate base price using same approach as above
        $base_price = false;
        
        // Get base price from dimensions if available
        if (!empty($cart_item['dimensions'])) {
            $width = $cart_item['dimensions']['width'];
            $height = $cart_item['dimensions']['height'];
            
            $product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
            $product = wc_get_product($product_id);
            
            if ($product) {
                $price_per_unit = $product->get_price('edit');
                $area = ($width/100) * ($height/100);
                $dimensional_price = $price_per_unit * $area;
                
                $min_price = get_field('pro_order_min_price', $product_id);
                if ($min_price && $dimensional_price < $min_price) {
                    $dimensional_price = $min_price;
                }
                
                $base_price = $dimensional_price;
            }
        }
        elseif (isset($cart_item['base_price'])) {
            $base_price = $cart_item['base_price'];
        }
        elseif (isset($cart_item['calculated_price'])) {
            $base_price = $cart_item['calculated_price'];
        }
        else {
            // Fallback to reverse engineering
            $current_price = $cart_item['data']->get_price();
            $total_addon_amount = 0;
            
            // Sum fixed addons
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price_type']) && $addon['price_type'] !== 'percentage_based') {
                    $total_addon_amount += (float)$addon['price'];
                }
            }
            
            // Get percentage addons
            $percentage_addons = array();
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price_type']) && $addon['price_type'] === 'percentage_based') {
                    $percentage_addons[] = (float)$addon['price'] / 100;
                }
            }
            
            // Calculate base price
            if (!empty($percentage_addons)) {
                $percentage_sum = array_sum($percentage_addons);
                $base_price = ($current_price - $total_addon_amount) / (1 + $percentage_sum);
            } else {
                $base_price = $current_price - $total_addon_amount;
            }
        }
        
        // Fallback
        if (!$base_price) {
            $base_price = $cart_item['data']->get_price();
        }
    }
    
    // Check if we have a <dl class="variation"> in the product name
    if (strpos($product_name, '<dl class="variation">') !== false) {
        // Process each percentage addon
        foreach ($cart_item['addons'] as $addon) {
            // Skip non-percentage addons
            if (!isset($addon['price_type']) || $addon['price_type'] !== 'percentage_based') {
                continue;
            }
            
            // Get the correct price for this addon
            if ($has_correct_prices && isset($addon['correct_display_price'])) {
                $correct_price = $addon['correct_display_price'];
            } else {
                $percentage = (float)$addon['price'];
                $correct_price = $base_price * ($percentage / 100);
            }
            
            // Format price
            $formatted_price = wc_price($correct_price);
            
            // Create a regex pattern to find this addon in the HTML
            $addon_pattern = '/<dt[^>]*>([^<]*' . preg_quote($addon['name'], '/') . '[^<]*)\([^)]+\)([^<]*)<\/dt>/i';
            
            // Replace with correct price
            $product_name = preg_replace(
                $addon_pattern,
                '<dt$1(' . $formatted_price . ')$2</dt>',
                $product_name
            );
        }
    }
    
    return $product_name;
}

/**
 * Fix each meta item individually
 */
add_filter('woocommerce_display_item_meta', 'fix_item_meta_for_cart_item', 999, 3);

function fix_item_meta_for_cart_item($html, $item, $args) {
    // Only on cart and checkout
    if (!is_cart() && !is_checkout()) {
        return $html;
    }
    
    // Find the matching cart item by cart_item_key
    $cart_item_key = isset($item['key']) ? $item['key'] : '';
    if (!$cart_item_key) {
        return $html;
    }
    
    // Get the cart item
    $cart = WC()->cart;
    if (!$cart || !isset($cart->cart_contents[$cart_item_key])) {
        return $html;
    }
    
    $cart_item = $cart->cart_contents[$cart_item_key];
    
    // Skip if no addons
    if (empty($cart_item['addons'])) {
        return $html;
    }
    
    // Use stored correct prices if available, otherwise calculate
    $base_price = isset($cart_item['correct_base_price']) ? $cart_item['correct_base_price'] : false;
    
    if (!$base_price) {
        // Calculate base price (same methods as above)
        if (!empty($cart_item['dimensions'])) {
            $width = $cart_item['dimensions']['width'];
            $height = $cart_item['dimensions']['height'];
            
            $product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
            $product = wc_get_product($product_id);
            
            if ($product) {
                $price_per_unit = $product->get_price('edit');
                $area = ($width/100) * ($height/100);
                $dimensional_price = $price_per_unit * $area;
                
                $min_price = get_field('pro_order_min_price', $product_id);
                if ($min_price && $dimensional_price < $min_price) {
                    $dimensional_price = $min_price;
                }
                
                $base_price = $dimensional_price;
            }
        } elseif (isset($cart_item['base_price'])) {
            $base_price = $cart_item['base_price'];
        } elseif (isset($cart_item['calculated_price'])) {
            $base_price = $cart_item['calculated_price'];
        } else {
            // Reverse engineer as before
            $current_price = $cart_item['data']->get_price();
            $total_addon_amount = 0;
            
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price_type']) && $addon['price_type'] !== 'percentage_based') {
                    $total_addon_amount += (float)$addon['price'];
                }
            }
            
            $percentage_addons = array();
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price_type']) && $addon['price_type'] === 'percentage_based') {
                    $percentage_addons[] = (float)$addon['price'] / 100;
                }
            }
            
            if (!empty($percentage_addons)) {
                $percentage_sum = array_sum($percentage_addons);
                $base_price = ($current_price - $total_addon_amount) / (1 + $percentage_sum);
            } else {
                $base_price = $current_price - $total_addon_amount;
            }
        }
        
        // Fallback
        if (!$base_price) {
            $base_price = $cart_item['data']->get_price();
        }
    }
    
    // Process percentage addons
    foreach ($cart_item['addons'] as $addon) {
        if (!isset($addon['price_type']) || $addon['price_type'] !== 'percentage_based') {
            continue;
        }
        
        // Get correct price
        if (isset($addon['correct_display_price'])) {
            $correct_price = $addon['correct_display_price'];
        } else {
            $percentage = (float)$addon['price'];
            $correct_price = $base_price * ($percentage / 100);
        }
        
        // Format for display
        $formatted_price = wc_price($correct_price);
        
        // Create pattern to find in HTML
        $pattern = '/<dt[^>]*>\s*([^<]*?' . preg_quote($addon['name'], '/') . 
                  '[^<]*?)\s*\(\s*<span[^>]*>[^<]*?<\/span>\s*\)\s*:?\s*<\/dt>/is';
        
        if (preg_match($pattern, $html, $matches)) {
            $full_match = $matches[0];
            $addon_text = $matches[1];
            
            // Create replacement
            $replacement = '<dt class="variation-' . sanitize_title($addon['name']) . '">' . 
                          trim($addon_text) . ' (' . $formatted_price . '):</dt>';
            
            // Replace in HTML
            $html = str_replace($full_match, $replacement, $html);
        }
    }
    
    return $html;
}

/**
 * JavaScript fix as a final fallback
 * This version uses the cart_item_key for precise matching
 */
add_action('wp_footer', 'fix_percentage_addon_prices_js', 99);

function fix_percentage_addon_prices_js() {
    // Only add on cart and checkout pages
    if (!is_cart() && !is_checkout()) {
        return;
    }
    
    // Get cart data for JS
    $cart_data = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (empty($cart_item['addons'])) {
            continue;
        }
        
        // Extract base price using same logic
        $base_price = isset($cart_item['correct_base_price']) ? $cart_item['correct_base_price'] : false;
        
        if (!$base_price) {
            // Calculate as before
            if (!empty($cart_item['dimensions'])) {
                $width = $cart_item['dimensions']['width'];
                $height = $cart_item['dimensions']['height'];
                
                $product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
                $product = wc_get_product($product_id);
                
                if ($product) {
                    $price_per_unit = $product->get_price('edit');
                    $area = ($width/100) * ($height/100);
                    $dimensional_price = $price_per_unit * $area;
                    
                    $min_price = get_field('pro_order_min_price', $product_id);
                    if ($min_price && $dimensional_price < $min_price) {
                        $dimensional_price = $min_price;
                    }
                    
                    $base_price = $dimensional_price;
                }
            } elseif (isset($cart_item['base_price'])) {
                $base_price = $cart_item['base_price'];
            } elseif (isset($cart_item['calculated_price'])) {
                $base_price = $cart_item['calculated_price'];
            } else {
                // Reverse engineer
                $current_price = $cart_item['data']->get_price();
                $total_addon_amount = 0;
                
                foreach ($cart_item['addons'] as $addon) {
                    if (isset($addon['price_type']) && $addon['price_type'] !== 'percentage_based') {
                        $total_addon_amount += (float)$addon['price'];
                    }
                }
                
                $percentage_addons = array();
                foreach ($cart_item['addons'] as $addon) {
                    if (isset($addon['price_type']) && $addon['price_type'] === 'percentage_based') {
                        $percentage_addons[] = (float)$addon['price'] / 100;
                    }
                }
                
                if (!empty($percentage_addons)) {
                    $percentage_sum = array_sum($percentage_addons);
                    $base_price = ($current_price - $total_addon_amount) / (1 + $percentage_sum);
                } else {
                    $base_price = $current_price - $total_addon_amount;
                }
            }
            
            // Fallback
            if (!$base_price) {
                $base_price = $cart_item['data']->get_price();
            }
        }
        
        $addon_data = array();
        foreach ($cart_item['addons'] as $addon) {
            if (isset($addon['price_type']) && $addon['price_type'] === 'percentage_based') {
                $correct_price = isset($addon['correct_display_price']) 
                    ? $addon['correct_display_price'] 
                    : ($base_price * ((float)$addon['price'] / 100));
                
                $addon_data[] = array(
                    'name' => $addon['name'],
                    'percentage' => (float)$addon['price'],
                    'correct_price' => $correct_price,
                    'formatted_price' => wp_strip_all_tags(html_entity_decode(wc_price($correct_price)))
                );
            }
        }
        
        if (!empty($addon_data)) {
            $cart_data[$cart_item_key] = array(
                'key' => $cart_item_key,
                'product_id' => $cart_item['product_id'],
                'variation_id' => isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0,
                'base_price' => $base_price,
                'dimensions' => isset($cart_item['dimensions']) ? $cart_item['dimensions'] : array(),
                'addons' => $addon_data
            );
        }
    }
    
    // If we have no percentage addons to fix, exit
    if (empty($cart_data)) {
        return;
    }
    
    // Output JS to fix the prices in the DOM
    ?>
<script type="text/javascript">
jQuery(function($) {
    // Cart data with correct addon prices
    var cart_data = <?php echo json_encode($cart_data); ?>;
    
    console.log('Multiple items addon price fix data:', cart_data);
    
    // Function to fix addon prices across different cart HTML structures
    function fixAddonPrices() {
        // Process each cart item
        $.each(cart_data, function(cart_item_key, item_data) {
            // Find the cart row that contains this specific cart item
            var $cartRow = $('tr.cart_item[data-cart-item-key="' + cart_item_key + '"]');
            
            // If we can't find a direct match by key attribute, try to match by product and dimensions
            if ($cartRow.length === 0) {
                // This is the fallback approach - try to identify the correct row
                // by matching product ID and dimensions
                $('tr.cart_item').each(function() {
                    var $row = $(this);
                    var rowMatchesItem = false;
                    
                    // Check if this row has the same product ID
                    if ($row.find('a[data-product_id="' + item_data.product_id + '"]').length > 0) {
                        // Check dimensions if available
                        if (item_data.dimensions.width && item_data.dimensions.height) {
                            var rowHtml = $row.html();
                            // Look for dimension text in the row
                            if (rowHtml.indexOf(item_data.dimensions.width + ' ס״מ') !== -1 &&
                                rowHtml.indexOf(item_data.dimensions.height + ' ס״מ') !== -1) {
                                rowMatchesItem = true;
                            }
                        } else {
                            // If no dimensions, just match by product ID
                            rowMatchesItem = true;
                        }
                    }
                    
                    if (rowMatchesItem) {
                        $cartRow = $row;
                        // Save the cart_item_key for future reference
                        $cartRow.attr('data-cart-item-key', cart_item_key);
                    }
                });
            }
            
            // If we found a matching row, fix the addon prices
            if ($cartRow.length > 0) {
                console.log('Found cart row for item:', cart_item_key);
                
                // Fix each addon
                $.each(item_data.addons, function(index, addon) {
                    console.log('Fixing addon:', addon.name, 'to price:', addon.correct_price);
                    
                    // Find addon text in this row
                    $cartRow.find('dl.variation dt').each(function() {
                        var $dt = $(this);
                        var dtText = $dt.text();
                        
                        // If this dt contains the addon name
                        if (dtText.indexOf(addon.name) !== -1) {
                            // Replace the content with corrected price
                            // Use .html() instead of .text() to properly render the HTML entities
                            var newText = addon.name + ' (' + addon.formatted_price + '):';
                            $dt.html(newText);
                            console.log('Fixed price in dt element for item:', cart_item_key);
                        }
                    });
                });
            } else {
                console.log('Could not find cart row for item:', cart_item_key);
            }
        });
    }
    
    // Initial fix
    fixAddonPrices();
    
    // Fix after any AJAX completes (for cart updates)
    $(document).ajaxComplete(function() {
        setTimeout(fixAddonPrices, 100);
    });
});
</script>
    <?php
}