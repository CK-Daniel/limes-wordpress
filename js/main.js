/*************************************************
 * GLOBAL VARIABLES & PARAMS
 *************************************************/
let params = new URLSearchParams(document.location.search);
let min_price_param = parseInt(params.get("min_price"), 10);
let max_price_param = parseInt(params.get("max_price"), 10);

var window_width = jQuery(window).width();

// Global configuration for addon loading overlay
window.LIMES_ADDON_LOADING_DURATION = 1500; // Default 1.5 seconds (1500ms)
// To change the duration, add this to your theme's custom JS:
// window.LIMES_ADDON_LOADING_DURATION = 1000; // For 1 second
// window.LIMES_ADDON_LOADING_DURATION = 2000; // For 2 seconds
// window.LIMES_ADDON_LOADING_DURATION = 3000; // For 3 seconds

/*************************************************
 * PLUS / MINUS QUANTITY LISTENERS
 *************************************************/
function plus_minns_listeners() {
	jQuery(".wrapper-quantity .button-minus").off('click').on("click", function () {
		var qty_input = jQuery(this).closest(".wrapper-quantity").find(".input-text.qty");
		if (!qty_input.length) qty_input = jQuery(this).closest(".wrapper-quantity").find(".input-text"); // Fallback
		var cur_num = parseInt(jQuery(qty_input).val(), 10);
		if (isNaN(cur_num)) cur_num = 1; // Handle non-numeric case
		cur_num--;
		if (cur_num < 1) cur_num = 1; // Or reference min attribute if present
		jQuery(qty_input).val(cur_num).trigger('change'); // Trigger change for WooCommerce
		jQuery('button[name="update_cart"]').prop("disabled", false);
	});
	jQuery(".wrapper-quantity .button-plus").off('click').on("click", function () {
		var qty_input = jQuery(this).closest(".wrapper-quantity").find(".input-text.qty");
		if (!qty_input.length) qty_input = jQuery(this).closest(".wrapper-quantity").find(".input-text"); // Fallback
		var cur_num = parseInt(jQuery(qty_input).val(), 10);
		if (isNaN(cur_num)) cur_num = 0; // Handle non-numeric case
		cur_num++;
		var max_val = jQuery(qty_input).attr('max');
		if (typeof max_val !== 'undefined' && max_val !== false && cur_num > parseInt(max_val, 10) ) {
			cur_num = parseInt(max_val, 10);
		}
		jQuery(qty_input).val(cur_num).trigger('change'); // Trigger change for WooCommerce
		jQuery('button[name="update_cart"]').prop("disabled", false);
	});
}

/*************************************************
 * CHECK OFFSET (Handles sticky near footer)
 *************************************************/
function checkOffset() {
	var $socialFloat = jQuery("#social-float");
	var $footer = jQuery("footer");

	if ($socialFloat.length && $footer.length && $footer.offset()) { // Ensure elements and offset exist
		if (
			$socialFloat.offset().top + $socialFloat.height() >=
			$footer.offset().top - 10
		) {
			jQuery("body").addClass("product_sticky_mode");
		}
		if (
			jQuery(document).scrollTop() + window.innerHeight <
			$footer.offset().top
		) {
			jQuery("body").removeClass("product_sticky_mode"); // Should remove the class added above
		}
	}
}

/*************************************************
 * GET FIELD VALUE BY NAME (For CF7 forms, etc.)
 *************************************************/
function getFieldValueByName(ar, name) {
	var result = "";
	if (Array.isArray(ar)) {
		ar.forEach(function (item) {
			if (item.name == name) result = item.value;
		});
	}
	return result;
}

/*************************************************
 * updateAddonDisplay()
 * Overwrites the .product-addon-totals lines with
 * correct per–add-on fees & final price.
 * NOTE: We'll skip lines if fee=0 & label is empty
 *************************************************/
let isUpdatingDisplay = false;
let updateQueue = null;
let lastUpdateTimestamp = 0;

function updateAddonDisplay(productPrice, addonBreakdown, finalPrice) {
	// Queue the update to prevent conflicts with addon plugin
	if (updateQueue) {
		clearTimeout(updateQueue);
	}
	
	// Store the latest values
	const updateData = {
		productPrice: parseFloat(productPrice) || 0,
		addonBreakdown: addonBreakdown,
		finalPrice: parseFloat(finalPrice) || 0,
		timestamp: Date.now()
	};
	
	// Only process if this is a newer update
	if (updateData.timestamp <= lastUpdateTimestamp) {
		return;
	}
	
	updateQueue = setTimeout(function() {
		performAddonDisplayUpdate(updateData);
	}, 50); // Small delay to batch updates
}

function performAddonDisplayUpdate(updateData) {
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
		// Remove ALL addon lines (both official and custom) except base product and subtotal
		$list.find("li").each(function() {
			const $li = jQuery(this);
			// Keep only the base product line (first child) and subtotal line
			if (!$li.is(':first-child') && !$li.hasClass('wc-pao-subtotal-line')) {
				$li.remove();
			}
		});
		
		// Also remove any addon lines that have both .wc-pao-addon-name and .wc-pao-addon-value
		// This targets the specific WooCommerce addon format
		$list.find("li:has(.wc-pao-addon-name):has(.wc-pao-addon-value)").remove();

		// Update base product price
		$list
			.find("li:first-child .wc-pao-col2 .amount")
			.html(updateData.productPrice.toFixed(2) + " <span class='woocommerce-Price-currencySymbol'>₪</span>");

		// Always add our correctly calculated custom addon lines
		if (Array.isArray(updateData.addonBreakdown)) {
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
<span class="amount">${fee.toFixed(2)} ₪</span>
</div>
</li>`;
				$list.find("li.wc-pao-subtotal-line").before(addonHTML);
			});
		}

		// Update final price - handle different subtotal line formats
		const $subtotalLine = $list.find("li.wc-pao-subtotal-line");
		if ($subtotalLine.length) {
			// First check if there's a .price element inside
			const $priceElement = $subtotalLine.find(".price");
			if ($priceElement.length) {
				// Check if it has text before the amount (like "סכום ביניים")
				const hasPrefix = $priceElement.text().includes("סכום ביניים");
				if (hasPrefix) {
					// Square meter format: "סכום ביניים 770.00 ₪"
					$priceElement.html('סכום ביניים <span class="amount">' + updateData.finalPrice.toFixed(2) + '&nbsp;₪</span>');
				} else {
					// Standard format - show "מחיר סופי" for variable products
					$priceElement.html('מחיר סופי <span class="woocommerce-Price-amount amount">' + updateData.finalPrice.toFixed(2) + '</span> <span class="woocommerce-Price-currencySymbol">₪</span>');
				}
			} else {
				// Fallback: look for p.price
				const $pPrice = $subtotalLine.find("p.price");
				if ($pPrice.length) {
					$pPrice.html('מחיר סופי <span class="woocommerce-Price-amount amount">' + updateData.finalPrice.toFixed(2) + '</span> <span class="woocommerce-Price-currencySymbol">₪</span>');
				}
			}
		}

		// Also update the "Final Price" label if it exists separately
		jQuery('.wrap_cart_btns.has_addons .price.woocommerce-Price-amount.amount').html(updateData.finalPrice.toFixed(2) + " <span class='woocommerce-Price-currencySymbol'>₪</span>");
		
		// Update any other price displays that might exist
		jQuery('#product-addons-total .wc-pao-subtotal-line .price').each(function() {
			const $this = jQuery(this);
			const hasPrefix = $this.text().includes("סכום ביניים");
			if (hasPrefix) {
				$this.html('סכום ביניים <span class="amount">' + updateData.finalPrice.toFixed(2) + '&nbsp;₪</span>');
			} else {
				$this.html('מחיר סופי <span class="woocommerce-Price-amount amount">' + updateData.finalPrice.toFixed(2) + '</span> <span class="woocommerce-Price-currencySymbol">₪</span>');
			}
		});
		
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
}


/*************************************************
 * FIRST DOCUMENT.READY (General UI code)
 *************************************************/
jQuery(document).ready(function ($) {
	if ($(".wrap_cart_btns.has_addons").length) {
		if (!$(".wrap_cart_btns.has_addons .price_label_text").length) { // Avoid duplicating label
			$(".wrap_cart_btns.has_addons").append(
				'<span class="price_label price_label_text">מחיר סופי: </span><span class="price woocommerce-Price-amount amount">' + (0).toFixed(2) + ' <span class="woocommerce-Price-currencySymbol">₪</span></span>' // Initial price
			);
		}
	}

	$("body").on("click", ".single_add_to_cart_button", function (e) {
		var prod_width_input = jQuery(".wrap_dimensions .wrap_dim .wrap_input input#prod_width");
		var prod_height_input = jQuery(".wrap_dimensions .wrap_dim .wrap_input input#prod_height");

		// Validate only if the fields are visible and part of a non-roll product implicitly
		// The recalcFinalPrice function will handle enabling/disabling the button more accurately.
		// This validation can be a visual cue but shouldn't prevent click if button isn't disabled.
		if (prod_width_input.is(':visible') && prod_width_input.length > 0 && prod_width_input.val().trim() === "") {
			prod_width_input.addClass("not-valid");
			// e.preventDefault(); // Only prevent if truly invalid and button was somehow not disabled
		}
		if (prod_height_input.is(':visible') && prod_height_input.length > 0 && prod_height_input.val().trim() === "") {
			prod_height_input.addClass("not-valid");
			// e.preventDefault();
		}
	});

	$(".wrap_dimensions .wrap_dim .wrap_input input#prod_width").on(
		"focus input", // Also remove on input
		function () {
			jQuery(this).removeClass("not-valid");
		}
	);
	$(".wrap_dimensions .wrap_dim .wrap_input input#prod_height").on(
		"focus input", // Also remove on input
		function () {
			jQuery(this).removeClass("not-valid");
		}
	);

	plus_minns_listeners();
	jQuery(document.body).on(
		"removed_from_cart updated_cart_totals wc_cart_emptied updated_wc_div", // Added more common WC events
		function () {
			plus_minns_listeners();
		}
	);

	var onScroll = function() { // Ensure onScroll is defined before use
		var distanceY = window.pageYOffset || document.documentElement.scrollTop;
		var shrinkOn = 30;
		var $header = $("header"); // Cache header
		if ($header.length) {
			if (distanceY > shrinkOn) {
				$header.addClass("scrolled");
			} else {
				$header.removeClass("scrolled");
			}
		}
	};
	window.addEventListener("scroll", onScroll);
	onScroll(); // Call on load

	if ($.fn.simpleMobileMenu) {
		$(".mobile_menu").simpleMobileMenu({
			menuStyle: "slide",
		});
	}

	document.addEventListener(
		"wpcf7mailsent",
		function (event) {
			if(event.detail && event.detail.inputs){
				var inputs = event.detail.inputs;
				var thankyouPage = getFieldValueByName(inputs, "thankyou-page");
				if (thankyouPage) window.location = thankyouPage;
			}
		},
		false
	);

	if ($("#tabs-nav li").length) {
		$("#tabs-nav li:first-child").addClass("active");
		$(".tab-content").hide().first().show(); // Chain methods
		$("#tabs-nav li").click(function (e) {
			e.preventDefault();
			var $this = $(this);
			if ($this.hasClass('active')) return; // Do nothing if already active

			$("#tabs-nav li").removeClass("active");
			$this.addClass("active");
			$(".tab-content").hide();
			var activeTab = $this.find("a").attr("href");
			$(activeTab).fadeIn();
		});
	}

	$(".set > a").on("click", function (e) {
		e.preventDefault();
		var $this = $(this);
		var $icon = $this.find("i");
		var $content = $this.siblings(".content");

		if ($this.hasClass("active")) {
			$this.removeClass("active");
			$content.slideUp(200);
			$icon.removeClass("fa-minus").addClass("fa-plus");
		} else {
			$(".set > a.active").find("i").removeClass("fa-minus").addClass("fa-plus");
			$(".set > a.active").removeClass("active").siblings(".content").slideUp(200);

			$this.addClass("active");
			$content.slideDown(200);
			$icon.removeClass("fa-plus").addClass("fa-minus");
		}
	});

	$('.wrap_attrs .flex_attrs .wrap_item input[type="radio"]').change(function () {
		var $thisRadio = $(this);
		var term_slug = $thisRadio.data("slug");
		$thisRadio.closest('.flex_attrs').find('.wrap_item.active').removeClass("active");
		$thisRadio.parent().addClass("active");

		var $selectPaColor = $("#pa_color");
		if ($selectPaColor.length) {
			$selectPaColor.val(term_slug).trigger("change"); // Triggers WooCommerce logic
		}
	});
	// Set initial active state for selected color attribute
	var $selectedColorRadio = $('.wrap_attrs .flex_attrs .wrap_item input[type="radio"]:checked');
	if ($selectedColorRadio.length) {
		$selectedColorRadio.parent().addClass("active");
	}

	$(".add_to_cart_trigger_btn").on("click", function (e) {
		e.preventDefault();
		$(this).closest(".single_variation_wrap").find(".single_add_to_cart_button").trigger("click");
	});
	$(".buy_now_trigger_btn").on("click", function (e) {
		e.preventDefault();
		$(this).closest(".single_variation_wrap").find(".buy_now").trigger("click");
	});

	$(".share-whatsapp a").on("click", function (e) {
		e.preventDefault();
		let url = $(this).data("url");
		if (url) {
			window.open(url, "_blank", "noopener,noreferrer");
		}
	});

	if ($("#slider").length && typeof $("#slider").slider === 'function') {
		var $minPriceInput = $("input[name='min_price']");
		var $maxPriceInput = $("input[name='max_price']");
		var $minPriceDefInput = $("input[name='min_price_def']");
		var $maxPriceDefInput = $("input[name='max_price_def']");

		var min_price = $minPriceInput.length ? parseInt($minPriceInput.val(), 10) : 0;
		var max_price = $maxPriceInput.length ? parseInt($maxPriceInput.val(), 10) : 1000;
		var min_price_def = $minPriceDefInput.length ? parseInt($minPriceDefInput.val(), 10) : 0;
		var max_price_def = $maxPriceDefInput.length ? parseInt($maxPriceDefInput.val(), 10) : 1000;

		// Ensure min_price and max_price are within def bounds
		min_price = Math.max(min_price_def, Math.min(max_price_def, isNaN(min_price) ? min_price_def : min_price));
		max_price = Math.max(min_price_def, Math.min(max_price_def, isNaN(max_price) ? max_price_def : max_price));
		if (min_price > max_price) min_price = max_price;


		$("#slider").slider({
			range: true,
			min: min_price_def,
			max: max_price_def,
			values: [min_price, max_price],
			slide: function (event, ui) {
				if (ui.values) {
					$(".from").val(ui.values[0]);
					$(".to").val(ui.values[1]);
					$(".min_val_text_0").html("₪" + ui.values[0]);
					$(".min_val_text_1").html("₪" + ui.values[1]);
				}
			},
		});

		function updateSliderText() {
			var values = $("#slider").slider("option", "values");
			if (values) {
				$(".min_val_text_0").html("₪" + values[0]);
				$(".min_val_text_1").html("₪" + values[1]);
			}
		}

		setTimeout(function () {
			if (min_price_param && !isNaN(min_price_param) && max_price_param && !isNaN(max_price_param)) {
				let currentMin = Math.max(min_price_def, Math.min(max_price_def, min_price_param));
				let currentMax = Math.max(min_price_def, Math.min(max_price_def, max_price_param));
				if (currentMin > currentMax) currentMin = currentMax;
				$("#slider").slider({ values: [currentMin, currentMax] });
			}
			updateSliderText(); // Update text after potentially setting values
		}, 400);

		$(".ui-slider-handle").each(function (index) {
			if (!$(this).find(".span_val_text").length) {
				$(this).append('<span class="span_val_text min_val_text_' + index + '"></span>');
			}
		});
		updateSliderText(); // Initial text

		$(".from, .to").on("change", function () {
			var val1 = parseInt($(".from").val(), 10);
			var val2 = parseInt($(".to").val(), 10);
			var currentValues = $("#slider").slider("option", "values");

			if (!isNaN(val1) && $(this).hasClass('from')) {
				$("#slider").slider("values", 0, Math.min(val1, currentValues[1]));
			} else if (!isNaN(val2) && $(this).hasClass('to')) {
				$("#slider").slider("values", 1, Math.max(val2, currentValues[0]));
			}
			updateSliderText();
		});
	}

	if ($("body").hasClass("single-product") && window_width > 1024) {
		var $window = $(window);
		var $body = $("body");
		var $footer = $("footer");
		var $stickyElement = $("#social-float"); // Or the actual product box if that's what should be sticky near footer

		if ($stickyElement.length && $footer.length) { // Ensure elements for sticky logic exist
			$window.scroll(function () {
				if (!$stickyElement.length || !$footer.length || !$footer.offset()) return; // Re-check inside scroll
				var scroll = $window.scrollTop();
				// Use checkOffset logic if #social-float is the primary sticky element controlled this way
				// The logic below seems to be for a different sticky behavior
				var footerActualOffset = $footer.offset().top;
				var stickyElementHeight = $stickyElement.outerHeight();
				var headerHeight = $("header.scrolled").length ? $("header.scrolled").outerHeight() : ($("header").length ? $("header").outerHeight() : 0);
				var stopPoint = footerActualOffset - stickyElementHeight - headerHeight - 20; // 20px buffer

				if (scroll >= (headerHeight + 30) && scroll < stopPoint) { // Start after header + some offset
					$body.addClass("product_sticky_mode_new"); // Use a new class if this is different logic
					// $stickyElement.css({'position': 'fixed', 'top': headerHeight + 'px'}); // Example fixed positioning
				} else {
					$body.removeClass("product_sticky_mode_new");
					// $stickyElement.css({'position': '', 'top': ''});
					// Handle collision with footer if product_sticky_mode_new makes it fixed
					if (scroll >= stopPoint) {
						// $body.addClass("product_sticky_at_bottom"); // Class to indicate it's at the stop point
						// $stickyElement.css({'position': 'absolute', 'top': stopPoint - headerHeight + 'px'}); // Adjust top to simulate sticking before footer
					} else {
						// $body.removeClass("product_sticky_at_bottom");
					}
				}
				checkOffset(); // Call the original checkOffset for its specific behavior
			});
		}
	}
}); // END FIRST DOCUMENT.READY

/*************************************************
 * SECOND DOCUMENT.READY (Dimension & Roll Calc)
 *************************************************/
jQuery(document).ready(function ($) {
	var $cartForm = $('form.cart');
	var $prodWidth = $('#prod_width');
	var $prodHeight = $('#prod_height');
	var $prodCoverage = $('#prod_coverage'); // CRUCIAL: This ID must be on the coverage input for roll products.

	var requiresDimensions = $prodWidth.length > 0 || $prodHeight.length > 0 || $prodCoverage.length > 0;
	var isRollProduct = $prodCoverage.length > 0; // This defines if it's a roll product.

	if (requiresDimensions) {
		$cartForm.addClass('dimensions-required');
		$cartForm.addClass('price-invalid'); 
		$('.single_add_to_cart_button, .add_to_cart_trigger_btn, .buy_now_trigger_btn, .buy_now').addClass('disabled');
	}

	$(".variations_form").on("found_variation.wc-variation-form", function (event, variation) {
		if (variation && typeof variation.display_price !== 'undefined') {
			$("#base_price").attr("data-base-price", variation.display_price);
		} else if (variation && typeof variation.price_html !== 'undefined' && $(variation.price_html).find('.amount').length) {
			// Fallback if display_price is not available but price_html is
			let priceText = $(variation.price_html).find('.amount').first().text();
			let parsedPrice = parseFloat(priceText.replace(/[^\d\.]/g, ""));
			if (!isNaN(parsedPrice)) {
				$("#base_price").attr("data-base-price", parsedPrice);
			}
		}
		recalcFinalPrice();
	});

	$(".variations_form").on("reset_data.wc-variation-form", function () {
		$("#base_price").attr("data-base-price", $("#base_price").data("default-price") || 0);
		recalcFinalPrice();
	});

	// Listen to WooCommerce's own event for when variation selection might have updated price.
	$( ".single_variation_wrap" ).on( "show_variation", function ( event, variation, purchasable ) {
		// Price might be updated by WooCommerce, recalc our total.
		setTimeout(function () { // Delay to ensure WC updates are rendered
			recalcFinalPrice();
		}, 150);
	});
	// Also listen to select changes as a fallback, debounced.
	var variationChangeTimeout;
	$(".variations_form select").on("change", function () {
		clearTimeout(variationChangeTimeout);
		variationChangeTimeout = setTimeout(function () {
			recalcFinalPrice();
		}, 200); 
	});


	$prodHeight.on("input", function () {
		validate_height($(this).val());
		recalcFinalPrice();
	});
	$prodWidth.on("input", function () {
		validate_width($(this).val());
		recalcFinalPrice();
	});

	$prodHeight.on("blur", function() { validate_height($(this).val()); });
	$prodWidth.on("blur", function() { validate_width($(this).val()); });

	var lastCoverage = $prodCoverage.val(); // Initialize lastCoverage
	$prodCoverage.off("keyup input change focus").on("input change", function () {
		var newCoverage = $(this).val();
		// Call recalcFinalPrice even if newCoverage === lastCoverage because other factors might have changed.
		// The internal validation in recalcFinalPrice handles if the value itself is valid.
		recalcFinalPrice();
		lastCoverage = newCoverage; 
	});

	// Comprehensive addon change listener
	$cartForm.on("change input", ".wc-pao-addon input, .wc-pao-addon select, .wc-pao-addon textarea", function() {
		recalcFinalPrice();
	});

	// Add mutation observer for the entire addons container
	var addonsContainer = document.querySelector(".wc-pao-addons-container");
	if (addonsContainer) {
		let lastCorrectPrice = 0;
		let priceCheckTimer = null;
		
		// Function to get the current calculated price
		function getCurrentCalculatedPrice() {
			// Get the last calculated price from recalcFinalPrice
			const $finalPriceEl = $('.product-addon-totals .wc-pao-subtotal-line .price, #product-addons-total .wc-pao-subtotal-line .price').first();
			if ($finalPriceEl.length) {
				const priceText = $finalPriceEl.text().replace(/[^\d\.]/g, "");
				return parseFloat(priceText) || 0;
			}
			return 0;
		}
		
		const addonsObserver = new MutationObserver(function(mutations) {
			// Check if price elements were modified
			let priceChanged = false;
			
			mutations.forEach(function(mutation) {
				const $target = $(mutation.target);
				
				// Check if the mutation affected price elements
				if ($target.hasClass('price') || $target.hasClass('amount') || 
				    $target.find('.price, .amount').length > 0 ||
				    $target.closest('.wc-pao-subtotal-line, .wc-pao-col2').length > 0) {
					priceChanged = true;
				}
			});
			
			if (priceChanged && !isUpdatingDisplay) {
				// Clear any pending price check
				if (priceCheckTimer) clearTimeout(priceCheckTimer);
				
				// Schedule immediate price verification
				priceCheckTimer = setTimeout(function() {
					// Get what the price should be
					const correctPrice = window.lastCalculatedFinalPrice || lastCorrectPrice;
					const correctBase = window.lastCalculatedBasePrice || 0;
					const correctAddons = window.lastCalculatedAddons || [];
					
					if (correctPrice > 0) {
						// Immediately force our correct price without waiting
						isUpdatingDisplay = true;
						
						// Use requestAnimationFrame for immediate visual update
						requestAnimationFrame(function() {
							// Update all price displays directly - handle different formats
							$('.product-addon-totals .wc-pao-subtotal-line .price, #product-addons-total .wc-pao-subtotal-line .price').each(function() {
								const $el = $(this);
								const hasPrefix = $el.text().includes("סכום ביניים");
								
								if (hasPrefix) {
									// Square meter format
									$el.html('סכום ביניים <span class="amount">' + correctPrice.toFixed(2) + '&nbsp;₪</span>');
								} else {
									// Standard format - show "מחיר סופי"
									$el.html(' <span class="woocommerce-Price-amount amount">' + correctPrice.toFixed(2) + '</span> <span class="woocommerce-Price-currencySymbol">₪</span>');
								}
							});
							
							// Update base price
							$('.product-addon-totals li:first-child .wc-pao-col2 .amount').each(function() {
								const $el = $(this);
								$el.html(correctBase.toFixed(2) + ' <span class="woocommerce-Price-currencySymbol">₪</span>');
							});
							
							// Remove ALL addon lines except base product and subtotal
							$('.product-addon-totals li').each(function() {
								const $li = $(this);
								if (!$li.is(':first-child') && !$li.hasClass('wc-pao-subtotal-line')) {
									$li.remove();
								}
							});
							
							// Re-add our correct addon lines
							if (correctAddons.length > 0) {
								correctAddons.forEach(function(addon) {
									const addonLabel = addon.label;
									const addonFee = addon.fee;
									
									if (addonFee > 0) {
										const addonHTML = '<li class="wc-pao-addon-total-line limes-addon-line">' +
											'<div class="wc-pao-col1"><strong>' + addonLabel + '</strong></div>' +
											'<div class="wc-pao-col2"><span class="amount">' + addonFee.toFixed(2) + ' ₪</span></div>' +
											'</li>';
										$('.product-addon-totals .wc-pao-subtotal-line').before(addonHTML);
									}
								});
							}
							
							// Mark container as price verified
							$('.wc-pao-addons-container').addClass('limes-price-verified');
							
							// Remove the class after a short time to allow future checks
							setTimeout(function() {
								$('.wc-pao-addons-container').removeClass('limes-price-verified');
							}, 500);
							
							isUpdatingDisplay = false;
						});
					}
				}, 0); // Immediate check - no delay
			}
		});
		
		// Observe the entire container for any changes
		addonsObserver.observe(addonsContainer, {
			childList: true,
			subtree: true,
			characterData: true,
			attributes: true,
			attributeFilter: ['data-price', 'data-raw-price']
		});
		
		// Store the observer globally
		window.addonsContainerObserver = addonsObserver;
		
		// Listen for our custom price calculation event
		$(document).on('limes_price_calculated', function(e, data) {
			if (data && data.totalPrice) {
				lastCorrectPrice = data.totalPrice;
			}
		});
	}

	var addonTotalsEl = document.querySelector(".product-addon-totals");
	if (addonTotalsEl) {
		// Store globally for access from updateAddonDisplay
		window.addonTotalsEl = addonTotalsEl;
		
		var moTimer = null;
		window.addonMutationObserver = new MutationObserver(function (mutations) {
			// Skip if we're currently updating
			if (isUpdatingDisplay) return;
			
			// Check if the mutation is from our updates
			if (mutations.some(m => {
				const $target = $(m.target);
				return $target.hasClass('limes-updating') || 
				       $target.closest('.limes-updating').length > 0 ||
				       $target.hasClass('limes-addon-line') ||
				       $target.closest('.limes-addon-line').length > 0;
			})) {
				return; // Skip our own updates
			}
			
			// Check if the addon plugin added new lines
			const hasNewAddonLines = mutations.some(m => {
				if (m.type === 'childList' && m.addedNodes.length > 0) {
					for (let node of m.addedNodes) {
						const $node = $(node);
						// Check if it's a li element with addon content
						if ($node.is('li') && ($node.find('.wc-pao-addon-name').length > 0 || 
						    $node.find('.wc-pao-addon-value').length > 0)) {
							return true;
						}
						// Check children of added nodes
						if ($node.find('li:has(.wc-pao-addon-name)').length > 0) {
							return true;
						}
					}
				}
				return false;
			});
			
			// Check if this is the addon plugin trying to update prices
			const isPriceUpdate = mutations.some(m => {
				const $target = $(m.target);
				return $target.hasClass('price') || 
				       $target.hasClass('amount') ||
				       $target.closest('.wc-pao-subtotal-line').length > 0;
			});
			
			if (hasNewAddonLines || isPriceUpdate) {
				// Cancel any pending updates from the addon plugin
				if (moTimer) clearTimeout(moTimer);
				
				// Immediately remove any addon lines that aren't ours
				$list.find("li:has(.wc-pao-addon-name):has(.wc-pao-addon-value)").not('.limes-addon-line').remove();
				
				// Force our calculation to override plugin's update
				moTimer = setTimeout(function () {
					console.log('Addon plugin tried to add/update lines, overriding with our calculation');
					recalcFinalPrice();
				}, 10); // Very short delay to override quickly
			} else {
				// For other changes, use normal debouncing
				if (moTimer) clearTimeout(moTimer);
				moTimer = setTimeout(function () {
					recalcFinalPrice();
				}, 250);
			}
		});
		window.addonMutationObserver.observe(addonTotalsEl, { childList: true, subtree: true, characterData: true });
	}

	function validate_width(newWidthStr) {
		var valid = true;
		var $prodWidthInput = $("#prod_width");
		if (!$prodWidthInput.length) return true; // No field to validate

		var prod_width_min = parseFloat($prodWidthInput.attr("min"));
		var prod_width_max = parseFloat($prodWidthInput.attr("max"));
		var newWidth = parseFloat(newWidthStr);

		$(".validation_tip .validation_min_width, .validation_tip .validation_max_width").removeClass("not-valid");

		if (newWidthStr.trim() !== '' && isNaN(newWidth)) { // Invalid characters like "abc"
			valid = false; // General invalid state, specific message could be added
			// $prodWidthInput.addClass("not-valid"); // Add to input itself
		} else if (!isNaN(newWidth)) {
			if (!isNaN(prod_width_min) && newWidth < prod_width_min) {
				valid = false;
				$(".validation_tip .validation_min_width").addClass("not-valid");
			}
			if (!isNaN(prod_width_max) && newWidth > prod_width_max) {
				valid = false;
				$(".validation_tip .validation_max_width").addClass("not-valid");
			}
		}
		return valid;
	}

	function validate_height(newHeightStr) {
		var valid = true;
		var $prodHeightInput = $("#prod_height");
		if (!$prodHeightInput.length) return true;

		var prod_height_min = parseFloat($prodHeightInput.attr("min"));
		var prod_height_max = parseFloat($prodHeightInput.attr("max"));
		var newHeight = parseFloat(newHeightStr);

		$(".validation_tip .validation_min_height, .validation_tip .validation_max_height").removeClass("not-valid");

		if (newHeightStr.trim() !== '' && isNaN(newHeight)) {
			valid = false;
			// $prodHeightInput.addClass("not-valid");
		} else if (!isNaN(newHeight)) {
			if (!isNaN(prod_height_min) && newHeight < prod_height_min) {
				valid = false;
				$(".validation_tip .validation_min_height").addClass("not-valid");
			}
			if (!isNaN(prod_height_max) && newHeight > prod_height_max) {
				valid = false;
				$(".validation_tip .validation_max_height").addClass("not-valid");
			}
		}
		return valid;
	}

	// Configuration for loading overlay
	const LOADING_MIN_DURATION = window.LIMES_ADDON_LOADING_DURATION || 1500; // Use global config or default to 1.5 seconds
	let loadingStartTime = null;
	let isLoadingActive = false;
	let hideLoadingTimer = null;
	
	// Helper function to show loading overlay
	function showAddonLoadingOverlay() {
		// If already loading, don't trigger again
		if (isLoadingActive) {
			return;
		}
		
		// Check if addon section is visible - don't show loading if section is hidden
		const $container = $('.wc-pao-addons-container');
		const isContainerVisible = $container.length > 0 && $container.is(':visible');
		
		// Only show loading if the addon container is visible
		if (!isContainerVisible) {
			console.log('🚫 Addon container not visible, skipping loading overlay');
			return;
		}
		
		// Mark as loading and store start time
		isLoadingActive = true;
		loadingStartTime = Date.now();
		
		// Clear any pending hide timer
		if (hideLoadingTimer) {
			clearTimeout(hideLoadingTimer);
			hideLoadingTimer = null;
		}
		
		if (!$('.addon-loading-overlay').length) {
			const overlayHtml = `
				<div class="addon-loading-overlay">
					<div class="addon-loading-spinner">
						<div class="spinner-circle"></div>
						<span>מחשב מחיר...</span>
					</div>
				</div>
			`;
			$container.append(overlayHtml);
		}
		$container.addClass('loading-active');
		$('.addon-loading-overlay').stop(true, true).fadeIn(100);
	}
	
	// Helper function to hide loading overlay
	function hideAddonLoadingOverlay() {
		// If not loading, nothing to hide
		if (!isLoadingActive) {
			return;
		}
		
		// Check if container is still visible
		const $container = $('.wc-pao-addons-container');
		if (!$container.is(':visible')) {
			// Container was hidden, immediately reset loading state
			isLoadingActive = false;
			$('.addon-loading-overlay').hide();
			return;
		}
		
		// Calculate how long the loading has been shown
		const elapsedTime = Date.now() - loadingStartTime;
		const remainingTime = LOADING_MIN_DURATION - elapsedTime;
		
		// If minimum duration hasn't passed, schedule hiding for later
		if (remainingTime > 0) {
			// Clear any existing timer
			if (hideLoadingTimer) {
				clearTimeout(hideLoadingTimer);
			}
			
			hideLoadingTimer = setTimeout(function() {
				isLoadingActive = false;
				hideLoadingTimer = null;
				$('.wc-pao-addons-container').removeClass('loading-active');
				$('.addon-loading-overlay').fadeOut(200);
			}, remainingTime);
		} else {
			// Minimum duration has passed, hide immediately
			isLoadingActive = false;
			$('.wc-pao-addons-container').removeClass('loading-active');
			$('.addon-loading-overlay').fadeOut(200);
		}
	}

	function recalcFinalPrice() {
		// Don't show loading immediately - wait until we know the addon section is visible
		
		var fieldsAreValid = true; 

		if ($cartForm.hasClass('dimensions-required')) {
			if (isRollProduct) {
				// ROLL PRODUCT: #prod_coverage is required.
				if (!$prodCoverage.length) { 
					fieldsAreValid = false;
					console.warn("Roll Product Warning: Input field with ID 'prod_coverage' is missing.");
				} else {
					var coverageVal = $prodCoverage.val();
					if (!coverageVal || coverageVal.trim() === '' || isNaN(parseFloat(coverageVal)) || parseFloat(coverageVal) <= 0) {
						fieldsAreValid = false;
					}
				}
			} else {
				// NON-ROLL PRODUCT: Both #prod_width AND #prod_height are required.
				var widthVal = $prodWidth.length ? $prodWidth.val() : "";
				var heightVal = $prodHeight.length ? $prodHeight.val() : "";

				var isWidthInvalid = !$prodWidth.length || !widthVal || widthVal.trim() === '' || isNaN(parseFloat(widthVal)) || parseFloat(widthVal) <= 0;
				var isHeightInvalid = !$prodHeight.length || !heightVal || heightVal.trim() === '' || isNaN(parseFloat(heightVal)) || parseFloat(heightVal) <= 0;

				if (isWidthInvalid || isHeightInvalid) {
					fieldsAreValid = false;
				}
			}

			if (!fieldsAreValid) {
				$cartForm.addClass('price-invalid');
				$('.single_add_to_cart_button, .add_to_cart_trigger_btn, .buy_now_trigger_btn, .buy_now').addClass('disabled');
				updateAddonDisplay(0, [], 0); // Clear price display visuals
				hideAddonLoadingOverlay(); // Hide loading when fields are invalid
				return; 
			}
		} else {
			$cartForm.removeClass('price-invalid');
			$('.single_add_to_cart_button, .add_to_cart_trigger_btn, .buy_now_trigger_btn, .buy_now').removeClass('disabled');
		}

		// Get variation price
		var variationPrice = 0;
		var $variationPriceEl = $cartForm.find(".woocommerce-variation-price .price .amount").first();
		if (!$variationPriceEl.length) $variationPriceEl = $cartForm.find(".woocommerce-variation-price .amount").first();
		if (!$variationPriceEl.length) $variationPriceEl = $cartForm.find(".single_variation .price .amount").first();

		if ($variationPriceEl.length > 0) {
			variationPrice = parseFloat($variationPriceEl.text().replace(/[^\d\.-]/g, ""));
			if (isNaN(variationPrice)) variationPrice = 0;
		} else {
			variationPrice = parseFloat($("#base_price").data("base-price")) || 0;
		}
		if (isNaN(variationPrice)) variationPrice = 0;

		// Get actual entered dimensions
		var width = parseFloat($prodWidth.val()) || 0;
		var height = parseFloat($prodHeight.val()) || 0;

		// Get minimum dimensions from input attributes
		var minWidth = parseFloat($prodWidth.attr("min")) || 0;
		var minHeight = parseFloat($prodHeight.attr("min")) || 0;

		// Calculate base product price based on dimensions or coverage
		var productPriceAdjusted = variationPrice;
		var coverage = parseFloat($prodCoverage.val()) || 0;
		var rollWidth = parseFloat($("#roll_width").val()) || 0; 
		var rollLength = parseFloat($("#roll_length").val()) || 0; 
		var coverageUsed = false;

		// Calculate the actual product price
		if (isRollProduct && $prodCoverage.length > 0 && coverage > 0) {
			if (rollWidth > 0 && rollLength > 0) {
				var rollAreaPerUnit = (rollWidth / 100) * (rollLength / 100); 
				if (rollAreaPerUnit <= 0) rollAreaPerUnit = 1; 
				var coverage_with_margin = coverage * 1.05;
				var rollsNeeded = Math.ceil(coverage_with_margin / rollAreaPerUnit);
				if (rollsNeeded < 1) rollsNeeded = 1;
				productPriceAdjusted = variationPrice * rollsNeeded;
				$("#prod_rolls_needed").val(rollsNeeded);
			} else {
				productPriceAdjusted = variationPrice * coverage;
				$("#prod_rolls_needed").val(0);
			}
			coverageUsed = true;
		} else if (!isRollProduct) {
			$("#prod_rolls_needed").val("0");
			var minArea = parseFloat($cartForm.data('min-sqm') || 0);

			if ($(".wrap_height").hasClass("wrap_height_rm") && width > 0) {
				var runMeter = width / 100;
				if (minArea > 0 && runMeter > 0 && runMeter < minArea) runMeter = minArea;
				productPriceAdjusted = variationPrice * runMeter;
			} else if (width > 0 && height > 0) {
				var area = (width / 100) * (height / 100);
				if (minArea > 0 && area > 0 && area < minArea) area = minArea;
				productPriceAdjusted = variationPrice * area;
			} else if (width > 0 && !$(".wrap_height").hasClass("wrap_height_rm")) {
				var runMeter = width / 100;
				if (minArea > 0 && runMeter > 0 && runMeter < minArea) runMeter = minArea;
				productPriceAdjusted = variationPrice * runMeter;
			}
		}

		if (isNaN(productPriceAdjusted) || productPriceAdjusted < 0) productPriceAdjusted = 0;

		// Calculate the theoretical minimum price based on min dimensions
		var minPriceRequired = 0;
		if (!isRollProduct) {
			if ($(".wrap_height").hasClass("wrap_height_rm")) {
				// For running meter products, use minimum width
				var minRunMeter = minWidth / 100;
				minPriceRequired = variationPrice * minRunMeter;
			} else {
				// For area-based products, use minimum area
				var minArea = (minWidth / 100) * (minHeight / 100);
				minPriceRequired = variationPrice * minArea;
			}
		}

		// Check if the actual price is below the required minimum
		if (!isRollProduct && productPriceAdjusted < minPriceRequired) {
			$cartForm.addClass('price-invalid');
			$('.single_add_to_cart_button, .add_to_cart_trigger_btn, .buy_now_trigger_btn, .buy_now').addClass('disabled');

			// Update addon display to show minimum required price
			updateAddonDisplay(minPriceRequired, [], minPriceRequired);
			hideAddonLoadingOverlay(); // Hide loading when price is too low
			return;
		}

		// If we got this far, fields are valid and price is high enough
		$cartForm.removeClass('price-invalid');
		$('.single_add_to_cart_button, .add_to_cart_trigger_btn, .buy_now_trigger_btn, .buy_now').removeClass('disabled');
		
		// Show loading overlay only if addon section is visible
		showAddonLoadingOverlay();

		// Calculate addon costs
		var addonBreakdown = [];
		var meter_addon_total = 0;
		var non_meter_addon_total = 0;

		var currentWidthForAddons = parseFloat($prodWidth.val()) || 0;
		var currentHeightForAddons = parseFloat($prodHeight.val()) || 0;
		var calculatedUnitForAddons = 0;

		if (!isRollProduct) {
			if ($(".wrap_height").hasClass("wrap_height_rm") && currentWidthForAddons > 0) {
				calculatedUnitForAddons = currentWidthForAddons / 100;
			} else if (currentWidthForAddons > 0 && currentHeightForAddons > 0) {
				calculatedUnitForAddons = (currentWidthForAddons / 100) * (currentHeightForAddons / 100);
			} else if (currentWidthForAddons > 0) {
				calculatedUnitForAddons = currentWidthForAddons / 100;
			}
		} else if (isRollProduct && coverage > 0) {
			calculatedUnitForAddons = coverage;
		}

		$cartForm.find(".wc-pao-addon input:checked, .wc-pao-addon select, .wc-pao-addon textarea:not(:placeholder-shown)").each(function () {
			var $addonElement = $(this);
			var $addonWrapper = $addonElement.closest('.wc-pao-addon');
			if ($addonElement.is('select') && !$addonElement.val()) return;
			if ($addonElement.is('textarea') && !$addonElement.val().trim()) return;

			var addonLabel = $addonWrapper.find(".wc-pao-addon-name label, .wc-pao-addon-name").first().text().trim();
			var priceType = $addonElement.data("price-type") || $addonWrapper.data("price-type");
			var priceVal = 0;
			var optionFee = 0;

			if ($addonElement.is('select')) {
				var $selectedOption = $addonElement.find("option:selected");
				addonLabel = $selectedOption.data("label") || $selectedOption.text().replace(/\s*\(.*?\)\s*$/, '').trim() || addonLabel;
				priceType = $selectedOption.data("price-type") || priceType;
				optionFee = parseFloat($selectedOption.data("price")) || 0;
				priceVal = optionFee;
			} else {
				addonLabel = $addonElement.data("label") || $addonWrapper.find('label[for="' + $addonElement.attr('id') + '"]').text().replace(/\s*\(.*?\)\s*$/, '').trim() || addonLabel;
				priceVal = parseFloat($addonElement.data("price")) || 0;
			}

			if(priceType === 'per_character') {
				priceVal = (parseFloat($addonElement.data("price")) || 0) * $addonElement.val().length;
				priceType = 'flat_fee';
			}

			var isLameter = addonLabel.includes("למטר");
			var isLamchir = addonLabel.includes("למחיר");
			var fee = 0;

			if (priceType === "percentage_based") {
				fee = productPriceAdjusted * (priceVal / 100);
			} else if (priceType === "flat_fee" || priceType === "quantity_based") {
				if (isLameter && calculatedUnitForAddons > 0) {
					fee = priceVal * calculatedUnitForAddons;
				} else {
					fee = priceVal;
				}
			}

			if (!isNaN(fee) && fee !== 0) {
				addonBreakdown.push({ label: addonLabel, fee: fee });
				if (isLameter) {
					meter_addon_total += fee;
				} else {
					non_meter_addon_total += fee;
				}
			}
		});

		var finalPrice = productPriceAdjusted;
		if (!coverageUsed) {
			finalPrice += non_meter_addon_total + meter_addon_total;
		} else {
			finalPrice += non_meter_addon_total;
		}

		finalPrice = parseFloat(finalPrice.toFixed(2));
		if (isNaN(finalPrice) || finalPrice < 0) finalPrice = 0;

		let productBaseDisplay = parseFloat(productPriceAdjusted.toFixed(2));
		if (isNaN(productBaseDisplay) || productBaseDisplay < 0) productBaseDisplay = 0;

		updateAddonDisplay(productBaseDisplay, addonBreakdown, finalPrice);
		
		// Store the correct calculated price globally for the addon container observer
		if (window.lastCalculatedFinalPrice !== finalPrice) {
			window.lastCalculatedFinalPrice = finalPrice;
			window.lastCalculatedBasePrice = productBaseDisplay;
			window.lastCalculatedAddons = addonBreakdown;
			
			// Trigger a custom event that other scripts can listen to
			$(document).trigger('limes_price_calculated', {
				basePrice: productBaseDisplay,
				addons: addonBreakdown,
				totalPrice: finalPrice
			});
		}
		
		// Hide loading overlay after calculation is complete
		setTimeout(function() {
			hideAddonLoadingOverlay();
		}, 200);
		
		console.log("Price calculated:", finalPrice);
	}

	// Store original recalcFinalPrice globally before any modifications
	window.originalRecalcFinalPrice = recalcFinalPrice;

	recalcFinalPrice(); 

	// If variation is already selected on page load (e.g. default variation or from URL)
	if ($(".variations_form").data("product_variations") && $(".variations_form .variation_id").val() > 0) {
		setTimeout(function() { // Allow WC some time to init fully
			recalcFinalPrice();
		}, 250);
	}
	
	// Failsafe: Periodically check for and remove duplicate addon lines
	// This ensures any lines added after our updates are caught
	setInterval(function() {
		const $list = jQuery(".product-addon-totals ul");
		if ($list.length && !isUpdatingDisplay) {
			// Find and remove any addon lines that have both .wc-pao-addon-name and .wc-pao-addon-value
			// but are NOT our custom lines (don't have .limes-addon-line class)
			const $duplicateLines = $list.find("li:has(.wc-pao-addon-name):has(.wc-pao-addon-value)").not('.limes-addon-line');
			if ($duplicateLines.length > 0) {
				console.log('Removing duplicate addon lines:', $duplicateLines.length);
				$duplicateLines.remove();
			}
			
			// Also check for any lines that don't belong (not first child, not subtotal, not our custom lines)
			$list.find("li").each(function() {
				const $li = jQuery(this);
				// Keep only: first child (base product), subtotal line, and our custom addon lines
				const isBaseProduct = $li.is(':first-child');
				const isSubtotal = $li.hasClass('wc-pao-subtotal-line');
				const isOurLine = $li.hasClass('limes-addon-line');
				
				// If it's none of the above, it's likely a duplicate addon line from the plugin
				if (!isBaseProduct && !isSubtotal && !isOurLine) {
					// Check if it looks like an addon line (has addon structure)
					if ($li.find('.wc-pao-col1').length > 0 && $li.find('.wc-pao-col2').length > 0) {
						console.log('Removing unidentified addon line:', $li.html());
						$li.remove();
					}
				}
			});
		}
	}, 500); // Check every 500ms
}); // END second doc.ready