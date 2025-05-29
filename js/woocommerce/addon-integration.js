/**
 * WooCommerce Product Addons Integration
 * Handles addon price calculations and display updates
 * 
 * @package Limes
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        selectors: {
            addonFields: '.wc-pao-addon-field',
            addonTotals: '.universal-addon-section, #product-addons-total',
            priceDisplay: '.price .woocommerce-Price-amount',
            form: 'form.cart',
            variationPrice: 'input[name="variation_id"]'
        },
        classes: {
            calculating: 'addon-calculating',
            updated: 'addon-updated',
            loading: 'addon-loading'
        }
    };

    let basePrice = 0;
    let currentVariationPrice = 0;
    let isCalculating = false;
    let calculationQueue = [];

    /**
     * Initialize addon integration
     */
    function init() {
        bindEvents();
        getBasePrice();
        
        console.log('✅ Addon Integration initialized');
    }

    /**
     * Bind events
     */
    function bindEvents() {
        // Listen for addon changes
        $(document).on('change', config.selectors.addonFields, handleAddonChange);
        
        // Listen for variation changes to update base price
        $(config.selectors.form).on('found_variation', handleVariationFound);
        $(config.selectors.form).on('reset_data', handleVariationReset);
        
        // Listen for price calculator updates
        $(document).on('limes_price_calculated', handlePriceCalculated);
    }

    /**
     * Handle addon field changes
     */
    function handleAddonChange(e) {
        const $field = $(e.target);
        console.log('Addon changed:', $field.attr('name'), $field.val());
        
        // Queue the calculation to prevent race conditions
        queueCalculation('addon_change', {
            field: $field.attr('name'),
            value: $field.val()
        });
    }

    /**
     * Handle variation found
     */
    function handleVariationFound(e, variation) {
        if (variation && variation.display_price) {
            currentVariationPrice = parseFloat(variation.display_price);
            basePrice = currentVariationPrice;
            console.log('Variation price updated:', basePrice);
            
            // Queue calculation with new base price
            queueCalculation('variation_change', {
                newPrice: basePrice
            });
        }
    }

    /**
     * Handle variation reset
     */
    function handleVariationReset() {
        currentVariationPrice = 0;
        basePrice = getBasePrice();
        queueCalculation('variation_reset');
    }

    /**
     * Handle price calculator updates
     */
    function handlePriceCalculated(e, calculation) {
        if (calculation && calculation.totalPrice) {
            basePrice = calculation.totalPrice;
            queueCalculation('price_calculated', {
                newPrice: calculation.totalPrice
            });
        }
    }

    /**
     * Queue calculation to prevent race conditions and wrong price display
     */
    function queueCalculation(type, data = {}) {
        calculationQueue.push({
            type: type,
            data: data,
            timestamp: Date.now()
        });
        
        // Process queue if not already calculating
        if (!isCalculating) {
            processCalculationQueue();
        }
    }

    /**
     * Process calculation queue
     */
    function processCalculationQueue() {
        if (calculationQueue.length === 0 || isCalculating) {
            return;
        }
        
        isCalculating = true;
        
        // Show loading state immediately
        showLoadingState();
        
        // Get the latest calculation request (ignore older ones)
        const latestCalculation = calculationQueue[calculationQueue.length - 1];
        calculationQueue = []; // Clear queue
        
        console.log('Processing calculation:', latestCalculation.type);
        
        // Small delay to ensure DOM is updated and prevent flickering
        setTimeout(() => {
            calculateAddonTotals();
            hideLoadingState();
            isCalculating = false;
            
            // Process any new calculations that came in
            if (calculationQueue.length > 0) {
                setTimeout(processCalculationQueue, 50);
            }
        }, 50);
    }

    /**
     * Show loading state
     */
    function showLoadingState() {
        const $form = $(config.selectors.form);
        const $addonSection = $('.universal-addon-section');
        
        $form.addClass(config.classes.calculating).addClass(config.classes.loading);
        
        // Add loading indicator to addon section
        if ($addonSection.length > 0) {
            $addonSection.addClass('loading');
            
            // Show loading message instead of potentially wrong prices
            const loadingHtml = `
                <div class="addon-section-header"><h4>פירוט מחיר</h4></div>
                <div class="addon-loading-message">
                    <div class="loading-spinner"></div>
                    <span>מחשב מחיר...</span>
                </div>
            `;
            $addonSection.html(loadingHtml);
        }
    }

    /**
     * Hide loading state
     */
    function hideLoadingState() {
        const $form = $(config.selectors.form);
        const $addonSection = $('.universal-addon-section');
        
        $form.removeClass(config.classes.calculating).removeClass(config.classes.loading).addClass(config.classes.updated);
        $addonSection.removeClass('loading');
    }

    /**
     * Get base price from various sources
     */
    function getBasePrice() {
        // Try to get from variation first
        if (currentVariationPrice > 0) {
            return currentVariationPrice;
        }
        
        // Try to get from price calculator
        if (window.LimesPriceCalculator) {
            const calculation = window.LimesPriceCalculator.calculate();
            if (calculation && calculation.totalPrice) {
                return calculation.totalPrice;
            }
        }
        
        // Try to get from DOM
        const $priceElement = $(config.selectors.priceDisplay).first();
        if ($priceElement.length) {
            const priceText = $priceElement.text().replace(/[^\d.,]/g, '');
            const price = parseFloat(priceText.replace(',', '.'));
            if (!isNaN(price)) {
                return price;
            }
        }
        
        return 0;
    }

    /**
     * Calculate addon totals
     */
    function calculateAddonTotals() {
        const currentBase = getBasePrice();
        if (currentBase <= 0) {
            console.log('No base price available for addon calculation');
            hideLoadingState();
            return;
        }

        const selectedAddons = [];
        let totalAddonPrice = 0;
        let totalPercentage = 0;

        // Process each addon field
        $(config.selectors.addonFields).each(function() {
            const $field = $(this);
            const value = $field.val();
            
            if (value && value !== '') {
                const $selectedOption = $field.find('option:selected');
                const rawPrice = parseFloat($selectedOption.data('raw-price')) || 0;
                const priceType = $selectedOption.data('price-type') || 'flat_fee';
                const label = $selectedOption.data('label') || $selectedOption.text();
                
                let addonPrice = 0;
                
                if (priceType === 'percentage_based') {
                    // Calculate percentage of base price
                    addonPrice = (currentBase * rawPrice) / 100;
                    totalPercentage += rawPrice;
                } else {
                    // Flat fee
                    addonPrice = rawPrice;
                }
                
                if (addonPrice > 0) {
                    selectedAddons.push({
                        label: label,
                        price: addonPrice,
                        percentage: priceType === 'percentage_based' ? rawPrice : 0,
                        type: priceType
                    });
                    
                    totalAddonPrice += addonPrice;
                }
            }
        });

        const finalPrice = currentBase + totalAddonPrice;

        console.log('Addon calculation:', {
            basePrice: currentBase,
            selectedAddons: selectedAddons,
            totalAddonPrice: totalAddonPrice,
            totalPercentage: totalPercentage,
            finalPrice: finalPrice
        });

        // Update displays atomically (all at once to prevent inconsistencies)
        updateAllDisplays(currentBase, selectedAddons, totalAddonPrice, finalPrice);
        
        // Trigger custom event
        $(document).trigger('limes_addon_calculated', {
            basePrice: currentBase,
            addons: selectedAddons,
            addonTotal: totalAddonPrice,
            finalPrice: finalPrice
        });
    }

    /**
     * Update all displays atomically to prevent inconsistencies
     */
    function updateAllDisplays(basePrice, addons, addonTotal, finalPrice) {
        // Update universal addon section
        updateUniversalAddonSection(basePrice, addons, addonTotal, finalPrice);
        
        // Update WooCommerce addon totals
        updateWooCommerceAddonTotals(finalPrice);
        
        // Trigger WooCommerce's own addon calculation if available
        if (typeof wc_pao_update_totals === 'function') {
            wc_pao_update_totals();
        }
    }

    /**
     * Update universal addon section
     */
    function updateUniversalAddonSection(basePrice, addons, addonTotal, finalPrice) {
        const $section = $('.universal-addon-section');
        if ($section.length === 0) return;

        let html = '<div class="addon-section-header"><h4>פירוט מחיר</h4></div>';
        html += '<ul class="addon-totals-list">';
        
        // Base price line
        html += `<li class="base-price-line">
            <div class="wc-pao-col1">מחיר בסיס</div>
            <div class="wc-pao-col2"><span class="amount">${formatPrice(basePrice)}</span></div>
        </li>`;
        
        // Addon lines
        addons.forEach(addon => {
            const description = addon.percentage > 0 ? 
                `${addon.label} (+${addon.percentage}%)` : 
                addon.label;
            
            html += `<li class="wc-pao-addon-total-line">
                <div class="wc-pao-col1">${description}</div>
                <div class="wc-pao-col2"><span class="amount">${formatPrice(addon.price)}</span></div>
            </li>`;
        });
        
        // Final price line
        html += `<li class="final-price-line">
            <div class="wc-pao-col1">סה״כ</div>
            <div class="wc-pao-col2"><span class="amount">${formatPrice(finalPrice)}</span></div>
        </li>`;
        
        html += '</ul>';
        
        $section.html(html);
    }

    /**
     * Update WooCommerce addon totals
     */
    function updateWooCommerceAddonTotals(finalPrice) {
        const $addonTotal = $('#product-addons-total');
        if ($addonTotal.length > 0) {
            $addonTotal.find('.price, .amount').html(formatPrice(finalPrice));
        }
        
        // Update main price display
        $(config.selectors.priceDisplay).html(formatPrice(finalPrice));
    }

    /**
     * Format price for display
     */
    function formatPrice(price) {
        if (!price || isNaN(price)) {
            price = 0;
        }
        return price.toFixed(2) + ' ₪';
    }

    /**
     * Get selected addons info
     */
    function getSelectedAddons() {
        const addons = [];
        
        $(config.selectors.addonFields).each(function() {
            const $field = $(this);
            const value = $field.val();
            
            if (value && value !== '') {
                const $selectedOption = $field.find('option:selected');
                addons.push({
                    name: $field.attr('name'),
                    value: value,
                    label: $selectedOption.data('label') || $selectedOption.text(),
                    price: parseFloat($selectedOption.data('raw-price')) || 0,
                    type: $selectedOption.data('price-type') || 'flat_fee'
                });
            }
        });
        
        return addons;
    }

    /**
     * Public API
     */
    window.LimesAddonIntegration = {
        init: init,
        calculateTotals: calculateAddonTotals,
        getSelectedAddons: getSelectedAddons,
        isCalculating: () => isCalculating
    };

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        // Delay to ensure other scripts are loaded
        setTimeout(init, 200);
    });

})(jQuery);
