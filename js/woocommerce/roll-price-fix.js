/**
 * Roll Product Price Fix
 * Ensures roll products show correct prices in addon containers
 * 
 * @package Limes
 */

(function($) {
    'use strict';

    // Run after all other scripts
    $(window).on('load', function() {
        // Give other scripts time to run
        setTimeout(fixRollPrices, 1000);
        
        // Also fix prices when coverage changes
        $('#prod_coverage').on('input change blur', function() {
            setTimeout(fixRollPrices, 500);
        });
    });

    function fixRollPrices() {
        // Only run on roll products
        if ($('#prod_coverage').length === 0) return;
        
        const coverage = parseFloat($('#prod_coverage').val()) || 0;
        if (coverage <= 0) return;
        
        // Get roll dimensions
        const rollWidth = parseFloat($('#roll_width').val()) || 0;
        const rollLength = parseFloat($('#roll_length').val()) || 0;
        
        if (rollWidth <= 0 || rollLength <= 0) return;
        
        // Calculate correct price
        const rollArea = (rollWidth / 100) * (rollLength / 100);
        const coverageWithMargin = coverage * 1.05;
        const rollsNeeded = Math.ceil(coverageWithMargin / rollArea);
        
        // Get base price
        let basePrice = 0;
        if ($('.woocommerce-variation-price .amount').length > 0) {
            basePrice = parseFloat($('.woocommerce-variation-price .amount').first().text().replace(/[^\d\.]/g, ''));
        } else {
            basePrice = parseFloat($('#base_price').data('base-price')) || 0;
        }
        
        const correctPrice = basePrice * rollsNeeded;
        
        console.log('Roll Price Fix:', {
            coverage: coverage,
            rollArea: rollArea,
            rollsNeeded: rollsNeeded,
            basePrice: basePrice,
            correctPrice: correctPrice
        });
        
        // Fix all price displays
        fixPriceDisplays(correctPrice);
    }
    
    function fixPriceDisplays(correctPrice) {
        // Fix addon container prices
        $('.product-addon-totals').each(function() {
            const $container = $(this);
            
            // Check all amount elements
            $container.find('.amount').each(function() {
                const $amount = $(this);
                const currentText = $amount.text();
                const currentPrice = parseFloat(currentText.replace(/[^\d\.]/g, '')) || 0;
                
                // If price is wrong (too high or doesn't match our calculation)
                if (currentPrice > 10000 || Math.abs(currentPrice - correctPrice) > 1) {
                    console.log('Fixing wrong price:', currentPrice, '->', correctPrice);
                    
                    // Update the price
                    $amount.html(correctPrice.toFixed(2) + ' <span class="woocommerce-Price-currencySymbol">₪</span>');
                }
            });
            
            // Also update the subtotal line
            $container.find('.wc-pao-subtotal-line .price').html(
                '<span class="woocommerce-Price-amount amount"><bdi>' + 
                correctPrice.toFixed(2) + '&nbsp;<span class="woocommerce-Price-currencySymbol">₪</span></bdi></span>'
            );
        });
        
        // Update calculated price hidden input
        $('input[name="calculated_price"]').val(correctPrice);
        
        // Trigger custom event
        $(document).trigger('roll_price_fixed', {
            price: correctPrice
        });
    }
    
    // Also monitor for dynamic changes
    const observer = new MutationObserver(function(mutations) {
        let shouldFix = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                // Check if addon containers were added or modified
                const $target = $(mutation.target);
                if ($target.hasClass('product-addon-totals') || 
                    $target.closest('.product-addon-totals').length > 0 ||
                    $target.find('.product-addon-totals').length > 0) {
                    shouldFix = true;
                }
            }
        });
        
        if (shouldFix && $('#prod_coverage').val()) {
            setTimeout(fixRollPrices, 100);
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true,
        characterDataOldValue: true
    });

})(jQuery);