/*************************************************
 * ADDON DUPLICATE LINES FIX
 * This file contains the fix for duplicate addon lines issue
 * Include this after main.js to override the problematic behavior
 *************************************************/

// Override the performAddonDisplayUpdate function with enhanced duplicate prevention
if (typeof performAddonDisplayUpdate === 'function') {
	// Store the original function
	const originalPerformAddonDisplayUpdate = performAddonDisplayUpdate;
	
	// Override with enhanced version
	window.performAddonDisplayUpdate = function(updateData) {
		let $list = jQuery(".product-addon-totals ul");
		if (!$list.length) return;
		
		// Prevent re-entry
		if (isUpdatingDisplay) return;
		isUpdatingDisplay = true;
		
		// Store current timestamp
		lastUpdateTimestamp = updateData.timestamp;
		
		// Temporarily disable MutationObserver if it exists
		if (window.addonMutationObserver) {
			window.addonMutationObserver.disconnect();
		}
		
		// Add a class to indicate we're updating
		$list.addClass('limes-updating');
		
		// Wrap updates in requestAnimationFrame for smoother rendering
		requestAnimationFrame(function() {
			// Enhanced detection of official addon lines
			const $officialAddonLines = $list.find("li").filter(function() {
				const $li = jQuery(this);
				// Look for official WooCommerce addon patterns
				return $li.find(".wc-pao-addon-name").length > 0 || 
				       $li.find(".wc-pao-addon-value").length > 0 ||
				       ($li.hasClass('wc-pao-addon-total-line') && !$li.hasClass('limes-addon-line'));
			});
			
			const hasOfficialAddonLines = $officialAddonLines.length > 0;
			
			// Remove only our custom addon lines (not official ones)
			$list.find("li.limes-addon-line").remove();

			// Update base product price
			$list
				.find("li:first-child .wc-pao-col2 .amount")
				.html(updateData.productPrice.toFixed(2) + " <span class='woocommerce-Price-currencySymbol'>₪</span>");

			// Handle addon lines based on what exists
			if (hasOfficialAddonLines && Array.isArray(updateData.addonBreakdown) && updateData.addonBreakdown.length > 0) {
				// Official addon lines exist - update their prices with correct values
				updateData.addonBreakdown.forEach(function(addon) {
					const addonLabel = addon.label ? String(addon.label).trim() : "";
					const addonFee = parseFloat(addon.fee) || 0;
					
					if (addonFee === 0 && addonLabel === "") return;
					
					// Find official addon line that matches this addon
					$officialAddonLines.each(function() {
						const $line = jQuery(this);
						const $addonNameEl = $line.find(".wc-pao-addon-name, .wc-pao-col1 strong");
						const lineText = $addonNameEl.text().trim();
						
						// Check if this line matches our addon (by label or contains the label)
						if (lineText === addonLabel || lineText.includes(addonLabel) || addonLabel.includes(lineText)) {
							// Update the price in the official line
							const $priceEl = $line.find(".wc-pao-col2 .amount").first();
							if ($priceEl.length) {
								$priceEl.html(addonFee.toFixed(2) + ' <span class="woocommerce-Price-currencySymbol">₪</span>');
							} else {
								// Fallback: update any price element in the line
								const $fallbackPriceEl = $line.find(".wc-pao-col2 .price, .wc-pao-col2").first();
								if ($fallbackPriceEl.length) {
									$fallbackPriceEl.html('<span class="amount">' + addonFee.toFixed(2) + ' ₪</span>');
								}
							}
						}
					});
				});
				
				console.log('🔧 [LIMES ADDON FIX] Updated official addon lines with correct prices, prevented duplicates');
				
			} else if (!hasOfficialAddonLines && Array.isArray(updateData.addonBreakdown)) {
				// No official lines exist, add our custom ones (original behavior)
				updateData.addonBreakdown.forEach(function (ad) {
					let label = ad.label ? String(ad.label).trim() : "";
					let fee = parseFloat(ad.fee) || 0;

					if (fee === 0 && label === "") {
						return;
					}
					
					let addonHTML = `
<li class="wc-pao-addon-total-line limes-addon-line"> 
<div class="wc-pao-col1">
<strong>${label}</strong>
</div>
<div class="wc-pao-col2">
<span class="price"><span class="woocommerce-Price-amount amount">${fee.toFixed(2)}</span> <span class="woocommerce-Price-currencySymbol">₪</span></span>
</div>
</li>`;
					$list.find("li.wc-pao-subtotal-line").before(addonHTML);
				});
				
				console.log('🔧 [LIMES ADDON FIX] Added custom addon lines (no official lines found)');
			}

			// Update final price
			$list
				.find("li.wc-pao-subtotal-line .price")
				.html("<span class='woocommerce-Price-amount amount'>" + updateData.finalPrice.toFixed(2) + "</span> <span class='woocommerce-Price-currencySymbol'>₪</span>");

			// Also update the "Final Price" label if it exists separately
			jQuery('.wrap_cart_btns.has_addons .price.woocommerce-Price-amount.amount').html(updateData.finalPrice.toFixed(2) + " <span class='woocommerce-Price-currencySymbol'>₪</span>");
			
			// Update any other price displays that might exist
			jQuery('#product-addons-total .wc-pao-subtotal-line .price').html(
				"<span class='woocommerce-Price-amount amount'>" + updateData.finalPrice.toFixed(2) + "</span> <span class='woocommerce-Price-currencySymbol'>₪</span>"
			);
			
			// Remove updating class
			$list.removeClass('limes-updating');
			
			// Re-enable MutationObserver after a delay
			setTimeout(function() {
				isUpdatingDisplay = false;
				if (window.addonMutationObserver && window.addonTotalsEl) {
					window.addonMutationObserver.observe(window.addonTotalsEl, { 
						childList: true, 
						subtree: true, 
						characterData: true 
					});
				}
			}, 100);
		});
	};
	
	console.log('🔧 [LIMES ADDON FIX] Enhanced duplicate addon lines fix loaded');
}
