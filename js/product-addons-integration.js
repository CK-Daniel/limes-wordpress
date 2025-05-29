/**
 * WooCommerce Product Addons Integration
 * 
 * This script enhances the integration between our custom dimensional pricing
 * and the official WooCommerce Product Addons plugin.
 */
jQuery(document).ready(function($) {
    // Wait for the page to fully load
    $(window).on('load', function() {
        // Only run on product pages
        if (!$('body').hasClass('single-product')) return;
        
        // Cache selectors
        var $cartForm = $('form.cart');
        var $prodWidth = $('#prod_width');
        var $prodHeight = $('#prod_height');
        var $prodCoverage = $('#prod_coverage');
        var $basePrice = $('#base_price');
        var $officialAddonTotals = $('.wc-pao-addon-totals');
        var $customAddonTotals = $('.product-addon-totals');
        
        // Check if we have dimension fields
        var hasDimensions = $prodWidth.length > 0 || $prodHeight.length > 0 || $prodCoverage.length > 0;
        
        // Add class to form if dimensions are required
        if (hasDimensions) {
            $cartForm.addClass('dimensions-required');
            
            // Initial validation check
            validateDimensionFields();
        }
        
        // Remove any duplicate addon totals containers
        // The official WooCommerce Product Addons plugin adds its own container with ID 'product-addons-total'
        // Our custom container has class 'product-addon-totals'
        // If both exist, remove our custom one
        if ($('#product-addons-total').length > 0 && $('.product-addon-totals').length > 0) {
            console.log('Found both official and custom addon totals containers, removing custom one');
            $('.product-addon-totals').remove();
        }
        
        /**
         * Validate dimension fields and update UI accordingly
         */
        function validateDimensionFields() {
            var fieldsAreValid = true;
            
            if ($prodCoverage.length > 0) { // Roll type
                var coverageVal = $prodCoverage.val();
                if (!coverageVal || coverageVal.trim() === '') {
                    fieldsAreValid = false;
                }
            } else if ($prodWidth.length > 0) { // SQM or RM type
                var widthVal = $prodWidth.val();
                if (!widthVal || widthVal.trim() === '') {
                    fieldsAreValid = false;
                }
                
                // For SQM, also check height
                if ($prodHeight.length > 0 && !$('.wrap_height').hasClass('wrap_height_rm')) {
                    var heightVal = $prodHeight.val();
                    if (!heightVal || heightVal.trim() === '') {
                        fieldsAreValid = false;
                    }
                }
            }
            
            // Toggle the 'price-invalid' class based on validation result
            if (fieldsAreValid) {
                $cartForm.removeClass('price-invalid');
            } else {
                $cartForm.addClass('price-invalid');
            }
            
            return fieldsAreValid;
        }
        
        /**
         * Calculate the base price based on dimensions
         */
        function calculateDimensionalPrice() {
            // Get base price from variation or product
            var basePrice = 0;
            if ($(".woocommerce-variation-price .amount").length > 0) {
                basePrice = parseFloat(
                    $(".woocommerce-variation-price .amount")
                        .first()
                        .text()
                        .replace(/[^\d\.]/g, "")
                );
            } else {
                basePrice = parseFloat($basePrice.data("base-price")) || 0;
            }
            
            // Default to base price
            var adjustedPrice = basePrice;
            
            // Calculate based on dimensions
            if ($prodCoverage.length > 0) { // Roll type
                var coverage = parseFloat($prodCoverage.val()) || 0;
                var rollWidth = parseFloat($("#roll_width").val()) || 0;
                var rollLength = parseFloat($("#roll_length").val()) || 0;
                
                if (coverage > 0 && rollWidth > 0 && rollLength > 0) {
                    var rollArea = (rollWidth / 100) * (rollLength / 100);
                    if (rollArea < 1) rollArea = 1;
                    
                    // Add 5% margin
                    var coverage_with_margin = coverage * 1.05;
                    
                    var rollsNeeded = Math.ceil(coverage_with_margin / rollArea);
                    if (rollsNeeded < 1) rollsNeeded = 1;
                    
                    adjustedPrice = basePrice * rollsNeeded;
                    $("#prod_rolls_needed").val(rollsNeeded);
                }
            } else { // SQM or RM
                var width = parseFloat($prodWidth.val()) || 0;
                var height = parseFloat($prodHeight.val()) || 0;
                
                // For RM products, use fixed height
                if ($(".wrap_height").hasClass("wrap_height_rm")) {
                    height = 100;
                }
                
                if (width > 0 && height > 0) {
                    var area = (width / 100) * (height / 100);
                    if (area < 1) area = 1;
                    adjustedPrice = basePrice * area;
                } else if (width > 0) {
                    var runMeter = width / 100;
                    adjustedPrice = basePrice * runMeter;
                }
            }
            
            // Check for minimum price
            var minPrice = parseFloat($('.from_price .woocommerce-Price-amount').text().replace(/[^\d\.]/g, "")) || 0;
            if (minPrice > 0 && adjustedPrice < minPrice) {
                adjustedPrice = minPrice;
            }
            
            return adjustedPrice;
        }
        
        // Configuration for loading overlay
        const LOADING_MIN_DURATION = window.LIMES_ADDON_LOADING_DURATION || 1500; // Use global config or default to 1.5 seconds
        let loadingStartTime = null;
        let isLoadingActive = false;
        let hideLoadingTimer = null;
        
        /**
         * Show loading overlay on addon section
         */
        function showAddonLoading() {
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
            
            // Create overlay if it doesn't exist
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
            
            // Show the overlay
            $container.addClass('loading-active');
            $('.addon-loading-overlay').stop(true, true).fadeIn(100);
        }
        
        /**
         * Hide loading overlay
         */
        function hideAddonLoading() {
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
        
        /**
         * Update the product price for addons calculations
         */
        let updateTimer = null;
        function updateProductPriceForAddons() {
            // Clear any pending updates
            if (updateTimer) {
                clearTimeout(updateTimer);
            }
            
            // Small delay to ensure smooth animation
            updateTimer = setTimeout(function() {
                // Only proceed if validation passes
                if (!validateDimensionFields()) {
                    return;
                }
                
                // Only show loading if addon section is visible
                const $addonContainer = $('.wc-pao-addons-container');
                if ($addonContainer.length && $addonContainer.is(':visible')) {
                    showAddonLoading();
                }
                
                // Calculate the dimensional price
                var dimensionalPrice = calculateDimensionalPrice();
                
                // Store the calculated price for addons to use
                $cartForm.attr('data-price', dimensionalPrice);
                
                // Force create/show the product-addons-total container
                // This is important to show the totals even if no addon is selected
                ensureAddonTotalsVisible(dimensionalPrice);
                
                // If using the official WooCommerce Product Addons plugin
                if ($officialAddonTotals.length > 0) {
                    // Update the base price display
                    $officialAddonTotals.find('.wc-pao-subtotal-line .amount').html(
                        dimensionalPrice.toFixed(2) + ' ₪'
                    );
                    
                    // Trigger the official addon totals update
                    $(document.body).trigger('update_addon_totals');
                }
                // If using our custom addon totals
                else if ($customAddonTotals.length > 0) {
                    // Update the base price display
                    $customAddonTotals.find('li:first-child .wc-pao-col2 .amount').html(
                        dimensionalPrice.toFixed(2) + ' ₪'
                    );
                    
                    // If we have a recalcFinalPrice function, call it
                    if (typeof window.recalcFinalPrice === 'function') {
                        window.recalcFinalPrice();
                    }
                }
                
                // Store the calculated price in a hidden input for form submission
                if (!$('input[name="calculated_price"]').length) {
                    $cartForm.append('<input type="hidden" name="calculated_price" value="' + dimensionalPrice + '">');
                } else {
                    $('input[name="calculated_price"]').val(dimensionalPrice);
                }
                
                // Hide loading after a brief moment to ensure calculations are complete
                setTimeout(function() {
                    hideAddonLoading();
                }, 300);
            }, 100);
        }
        
        // Event listeners for dimension fields
        if ($prodWidth.length > 0) {
            $prodWidth.on('input change blur', function() {
                updateProductPriceForAddons();
            });
        }
        
        if ($prodHeight.length > 0) {
            $prodHeight.on('input change blur', function() {
                updateProductPriceForAddons();
            });
        }
        
        if ($prodCoverage.length > 0) {
            $prodCoverage.on('input change blur', function() {
                updateProductPriceForAddons();
            });
        }
        
        // Listen for variation changes
        $cartForm.on('found_variation reset_data', function() {
            // Don't show loading here - let updateProductPriceForAddons decide
            setTimeout(updateProductPriceForAddons, 100);
        });
        
        // Listen for addon changes
        $(document).on('change', '.wc-pao-addon-field', function() {
            updateProductPriceForAddons();
        });
        
        // Remove these loading triggers - they fire before all fields are complete
        // The loading will be handled by updateProductPriceForAddons when appropriate
        
        // Override WooCommerce Product Addons update function to prevent flickering
        if (typeof window.wc_pao_update_totals === 'function') {
            const originalUpdateTotals = window.wc_pao_update_totals;
            let updateDebounceTimer = null;
            
            window.wc_pao_update_totals = function() {
                // Clear any pending updates
                if (updateDebounceTimer) {
                    clearTimeout(updateDebounceTimer);
                }
                
                // Debounce the update to prevent multiple rapid calls
                updateDebounceTimer = setTimeout(() => {
                    // Add a class to indicate updating
                    $('.product-addon-totals, #product-addons-total').addClass('wc-pao-updating');
                    
                    // Call original function
                    const result = originalUpdateTotals.apply(this, arguments);
                    
                    // After WC updates, force our calculation
                    setTimeout(() => {
                        $('.product-addon-totals, #product-addons-total').removeClass('wc-pao-updating');
                        updateProductPriceForAddons();
                    }, 50);
                    
                    return result;
                }, 30); // Small debounce delay
            };
        }
        
        // Fix the add to cart button functionality
        $(document).off('click', '.single_add_to_cart_button').on('click', '.single_add_to_cart_button', function(e) {
            // Don't prevent default yet - we'll only do that if validation fails
            
            // Prevent multiple clicks or alert loops
            if ($(this).data('processing')) {
                return;
            }
            
            // Set processing flag to prevent multiple clicks
            $(this).data('processing', true);
            
            var $form = $(this).closest('form.cart');
            var allFieldsFilled = true;
            var missingFields = [];
            
            // Check width field
            if ($prodWidth.length > 0 && $prodWidth.prop('required') && !$prodWidth.val()) {
                missingFields.push('רוחב');
                $prodWidth.addClass('not-valid');
                allFieldsFilled = false;
            }
            
            // Check height field
            if ($prodHeight.length > 0 && $prodHeight.prop('required') && !$prodHeight.val()) {
                missingFields.push('גובה');
                $prodHeight.addClass('not-valid');
                allFieldsFilled = false;
            }
            
            // Check coverage field
            if ($prodCoverage.length > 0 && $prodCoverage.prop('required') && !$prodCoverage.val()) {
                missingFields.push('כיסוי');
                $prodCoverage.addClass('not-valid');
                allFieldsFilled = false;
            }
            
            // Check mechanism fields
            var $mechRadios = $('input[name="prod_radio-gr2"]');
            if ($mechRadios.length > 0 && $mechRadios.first().prop('required') && !$mechRadios.is(':checked')) {
                missingFields.push('צד מנגנון');
                allFieldsFilled = false;
            }
            
            // Check installation fields
            var $installRadios = $('input[name="prod_radio-gr1"]');
            if ($installRadios.length > 0 && $installRadios.first().prop('required') && !$installRadios.is(':checked')) {
                missingFields.push('סוג התקנה');
                allFieldsFilled = false;
            }
            
            // Show error message if fields are missing
            if (!allFieldsFilled) {
                e.preventDefault();
                alert('אנא מלא את השדות הבאים: ' + missingFields.join(', '));
                // Reset processing flag after a short delay to prevent alert loop
                setTimeout(function() {
                    $('.single_add_to_cart_button').data('processing', false);
                }, 500);
                return false;
            }
            
            // Make sure variation is selected for variable products
            if ($form.hasClass('variations_form')) {
                var variationId = $form.find('input[name="variation_id"]').val();
                if (!variationId || variationId === '0') {
                    e.preventDefault();
                    alert('אנא בחר וריאציה');
                    // Reset processing flag after a short delay to prevent alert loop
                    setTimeout(function() {
                        $('.single_add_to_cart_button').data('processing', false);
                    }, 500);
                    return false;
                }
            }
            
            // Force recalculation of price before submission
            updateProductPriceForAddons();
            
            // Let the form submit naturally
            return true;
        });
        
        /**
         * Ensure the addon totals container is visible and populated
         * This function creates the container if it doesn't exist
         */
        function ensureAddonTotalsVisible(dimensionalPrice) {
            // First check for the official WooCommerce Product Addons container
            if ($('#product-addons-total').length > 0) {
                // Make sure it's visible
                $('#product-addons-total').show();
                
                // If it doesn't have the product-addon-totals div, create it
                if ($('#product-addons-total .product-addon-totals').length === 0) {
                    var productName = $('h1.product-title').text().trim() || $('.product_title').text().trim();
                    var html = '<div class="product-addon-totals"><ul>' +
                               '<li><div class="wc-pao-col1"><strong><span>x 1</span> ' + productName + '</strong></div>' +
                               '<div class="wc-pao-col2"><strong><span class="amount">' + dimensionalPrice.toFixed(2) + ' ₪</span></strong></div></li>' +
                               '<li class="wc-pao-subtotal-line"><p class="price">' + dimensionalPrice.toFixed(2) + ' ₪</p></li>' +
                               '</ul></div>';
                    $('#product-addons-total').html(html);
                }
                
                // Update the price in the container
                $('#product-addons-total .product-addon-totals .wc-pao-subtotal-line .price').html(
                    '<span class="woocommerce-Price-amount amount"><bdi>' + 
                    dimensionalPrice.toFixed(2) + '&nbsp;<span class="woocommerce-Price-currencySymbol">₪</span></bdi></span>'
                );
                
                // Trigger the official addon totals update
                $(document.body).trigger('update_addon_totals');
            } 
            // If the official container doesn't exist, check for our custom one
            else if ($('.product-addon-totals').length > 0) {
                // Update the price in our custom container
                $('.product-addon-totals .wc-pao-subtotal-line .price').html(
                    '<span class="woocommerce-Price-amount amount"><bdi>' + 
                    dimensionalPrice.toFixed(2) + '&nbsp;<span class="woocommerce-Price-currencySymbol">₪</span></bdi></span>'
                );
            }
            // If neither container exists, create our custom one
            else if ($('.wc-pao-addons-container').length > 0) {
                var productName = $('h1.product-title').text().trim() || $('.product_title').text().trim();
                var html = '<div class="product-addon-totals">' +
                           '<ul>' +
                           '<li>' +
                           '<div class="wc-pao-col1"><strong><span>x 1</span> ' + productName + '</strong></div>' +
                           '<div class="wc-pao-col2"><strong><span class="amount">' + dimensionalPrice.toFixed(2) + ' ₪</span></strong></div>' +
                           '</li>' +
                           '<li class="wc-pao-subtotal-line"><p class="price">' + dimensionalPrice.toFixed(2) + ' ₪</p></li>' +
                           '</ul>' +
                           '</div>';
                
                // Append after the addons container
                $('.wc-pao-addons-container').after(html);
            }
        }
        
        // Create and show the addon totals container immediately on page load
        function createInitialAddonTotals() {
            // Get the base price
            var basePrice = 0;
            if ($(".woocommerce-variation-price .amount").length > 0) {
                basePrice = parseFloat(
                    $(".woocommerce-variation-price .amount")
                        .first()
                        .text()
                        .replace(/[^\d\.]/g, "")
                );
            } else {
                basePrice = parseFloat($basePrice.data("base-price")) || 0;
            }
            
            // If no base price is found, use the product price
            if (basePrice === 0) {
                basePrice = parseFloat($('.from_price .woocommerce-Price-amount').text().replace(/[^\d\.]/g, "")) || 0;
            }
            
            // First check for the official WooCommerce Product Addons container
            if ($('#product-addons-total').length > 0) {
                // Make sure it's visible
                $('#product-addons-total').show();
                
                // If it doesn't have the product-addon-totals div, create it
                if ($('#product-addons-total .product-addon-totals').length === 0) {
                    var productName = $('h1.product-title').text().trim() || $('.product_title').text().trim();
                    var html = '<div class="product-addon-totals"><ul>' +
                               '<li><div class="wc-pao-col1"><strong><span>x 1</span> ' + productName + '</strong></div>' +
                               '<div class="wc-pao-col2"><strong><span class="amount">' + basePrice.toFixed(2) + ' ₪</span></strong></div></li>' +
                               '<li class="wc-pao-subtotal-line"><p class="price">' + basePrice.toFixed(2) + ' ₪</p></li>' +
                               '</ul></div>';
                    $('#product-addons-total').html(html);
                }
            } 
            // If the official container doesn't exist, check for our custom one
            else if ($('.product-addon-totals').length === 0 && $('.wc-pao-addons-container').length > 0) {
                var productName = $('h1.product-title').text().trim() || $('.product_title').text().trim();
                var html = '<div class="product-addon-totals">' +
                           '<ul>' +
                           '<li>' +
                           '<div class="wc-pao-col1"><strong><span>x 1</span> ' + productName + '</strong></div>' +
                           '<div class="wc-pao-col2"><strong><span class="amount">' + basePrice.toFixed(2) + ' ₪</span></strong></div>' +
                           '</li>' +
                           '<li class="wc-pao-subtotal-line"><p class="price">' + basePrice.toFixed(2) + ' ₪</p></li>' +
                           '</ul>' +
                           '</div>';
                
                // Append after the addons container
                $('.wc-pao-addons-container').after(html);
            }
            
            // The "מחיר סופי:" label should be inside the addon container
            // It's already included in the container HTML, so we don't need to create it separately
        }
        
        /**
         * Ensure addon container exists for all products
         */
        function ensureAddonContainerForAllProducts() {
            // Check if the addon container exists
            if ($('.wc-pao-addons-container').length === 0) {
                console.log('📦 Creating addon container for product without addons');
                
                // Get product information
                const productName = $('h1.product-title').text().trim() || $('.product_title').text().trim();
                const productPrice = calculateDimensionalPrice();
                
                // Check if this product has actual addons
                const hasAddons = $('.wc-pao-addon-field').length > 0;
                
                // Create the container structure - hide it initially for products without addons
                const containerHtml = `
                    <div class="wc-pao-addons-container" ${!hasAddons ? 'style="display: none;"' : ''}>
                        <div id="product-addons-total" data-show-incomplete-sub-total="" data-show-sub-total="1" data-type="variable" data-tax-mode="excl" data-tax-display-mode="excl">
                            <div class="product-addon-totals">
                                <ul>
                                    <li>
                                        <div class="wc-pao-col1"><strong><span>x 1</span> ${productName}</strong></div>
                                        <div class="wc-pao-col2"><strong><span class="amount">${productPrice.toFixed(2)} <span class="woocommerce-Price-currencySymbol">₪</span></span></strong></div>
                                    </li>
                                    <li class="wc-pao-subtotal-line">
                                        <p class="price"><span class="woocommerce-Price-amount amount">${productPrice.toFixed(2)}</span> <span class="woocommerce-Price-currencySymbol">₪</span></p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                `;
                
                // Find the best place to insert it
                let inserted = false;
                
                // Insert after the variations button container
                const $variationsButton = $('.woocommerce-variation-add-to-cart').first();
                if ($variationsButton.length) {
                    $variationsButton.after(containerHtml);
                    inserted = true;
                }
                
                // If not found, try after the single_variation_wrap
                if (!inserted) {
                    const $variationWrap = $('.single_variation_wrap').first();
                    if ($variationWrap.length) {
                        // Insert inside the variation wrap, after the variation content
                        $variationWrap.append(containerHtml);
                        inserted = true;
                    }
                }
                
                // If still not inserted, append to form
                if (!inserted) {
                    $cartForm.append(containerHtml);
                }
            }
        }
        
        // Create the addon totals container immediately
        createInitialAddonTotals();
        
        // For products without addons, ensure the container structure exists
        ensureAddonContainerForAllProducts();
        
        /**
         * Clean up duplicate final price labels
         */
        function cleanupDuplicateLabels() {
            // Find all final price labels
            const $labels = $('.always-visible-final-price-label');
            
            // Keep only the one inside the addon container
            $labels.each(function() {
                const $label = $(this);
                if (!$label.closest('.wc-pao-addons-container').length) {
                    $label.remove();
                }
            });
        }
        
        // Clean up any duplicate final price labels
        cleanupDuplicateLabels();
        
        // Watch for addon container visibility changes to clean up loading state
        const addonContainerObserver = new MutationObserver(function(mutations) {
            const $container = $('.wc-pao-addons-container');
            if ($container.length && !$container.is(':visible') && isLoadingActive) {
                // Container was hidden while loading was active, clean up
                console.log('🧹 Cleaning up loading state - container was hidden');
                isLoadingActive = false;
                if (hideLoadingTimer) {
                    clearTimeout(hideLoadingTimer);
                    hideLoadingTimer = null;
                }
                $('.addon-loading-overlay').hide();
            }
        });
        
        // Observe the form for class changes that affect visibility
        const $form = $('form.cart');
        if ($form.length) {
            addonContainerObserver.observe($form[0], {
                attributes: true,
                attributeFilter: ['class']
            });
        }
        
        // Run initial calculation
        setTimeout(updateProductPriceForAddons, 300);
        
        // Also run it whenever the page is fully loaded
        $(window).on('load', function() {
            // Create the addon totals container again to ensure it exists
            createInitialAddonTotals();
            
            // Then update it with the calculated price
            setTimeout(updateProductPriceForAddons, 500);
        });
    });
});
