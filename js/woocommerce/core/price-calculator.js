/**
 * Limes Price Calculator - Core Module
 * 
 * @package Limes
 */

window.LimesPriceCalculator = (function($) {
    'use strict';

    // Private variables
    let config = {};
    let isInitialized = false;
    let calculationCache = new Map();

    // Public API
    const api = {
        init: init,
        calculate: calculate,
        updateDisplay: updateDisplay,
        validateFields: validateFields,
        getConfig: getConfig,
        clearCache: clearCache
    };

    /**
     * Initialize the price calculator
     * @param {Object} userConfig Configuration object
     */
    function init(userConfig) {
        if (isInitialized) {
            console.warn('LimesPriceCalculator already initialized');
            return;
        }

        config = $.extend(true, {
            basePrice: 0,
            minPrice: 0,
            productType: '',
            currencySymbol: '₪',
            settings: {},
            selectors: {
                widthField: '#prod_width',
                heightField: '#prod_height',
                coverageField: '#prod_coverage',
                priceDisplay: '.price',
                addToCartButton: '.single_add_to_cart_button',
                form: 'form.cart'
            },
            events: {
                onPriceCalculated: null,
                onValidationFailed: null,
                onFieldChanged: null
            }
        }, userConfig);

        bindEvents();
        isInitialized = true;

        console.log('✅ LimesPriceCalculator initialized', config);
        
        // Initial calculation with delay to ensure DOM is ready
        setTimeout(() => {
            calculate();
        }, 500);
    }

    /**
     * Bind events to form fields
     */
    function bindEvents() {
        const $form = $(config.selectors.form);
        
        // Dimension field changes
        $form.on('input change blur', config.selectors.widthField, handleFieldChange);
        $form.on('input change blur', config.selectors.heightField, handleFieldChange);
        $form.on('input change blur', config.selectors.coverageField, handleFieldChange);
        
        // Variation changes
        $form.on('found_variation reset_data', handleVariationChange);
        
        // Add to cart validation
        $form.on('submit', handleFormSubmit);
        
        // Addon changes (if WooCommerce Product Addons is active)
        $(document).on('change', '.wc-pao-addon-field', handleAddonChange);
    }

    /**
     * Handle field changes
     * @param {Event} e Event object
     */
    function handleFieldChange(e) {
        const $field = $(e.target);
        const fieldName = $field.attr('name') || $field.attr('id');
        
        // Trigger custom event
        if (config.events.onFieldChanged) {
            config.events.onFieldChanged(fieldName, $field.val());
        }
        
        // Debounced calculation
        clearTimeout(window.limesCalculationTimeout);
        window.limesCalculationTimeout = setTimeout(() => {
            calculate();
        }, 300);
    }

    /**
     * Handle variation changes
     * @param {Event} e Event object
     * @param {Object} variation Variation data
     */
    function handleVariationChange(e, variation) {
        if (variation && variation.display_price) {
            config.basePrice = parseFloat(variation.display_price);
        }
        
        setTimeout(() => {
            calculate();
        }, 100);
    }

    /**
     * Handle addon changes
     * @param {Event} e Event object
     */
    function handleAddonChange(e) {
        setTimeout(() => {
            calculate();
        }, 100);
    }

    /**
     * Handle form submission
     * @param {Event} e Event object
     */
    function handleFormSubmit(e) {
        const validation = validateFields();
        
        if (!validation.isValid) {
            e.preventDefault();
            
            if (config.events.onValidationFailed) {
                config.events.onValidationFailed(validation.errors);
            } else {
                alert('אנא מלא את השדות הנדרשים: ' + validation.errors.join(', '));
            }
            
            return false;
        }
        
        // Add calculated price to form data
        const calculation = calculate();
        if (calculation && calculation.totalPrice) {
            $('<input>').attr({
                type: 'hidden',
                name: 'calculated_price',
                value: calculation.totalPrice
            }).appendTo(config.selectors.form);
        }
    }

    /**
     * Calculate price based on current form values
     * @returns {Object|null} Calculation result
     */
    function calculate() {
        try {
            const dimensions = getDimensions();
            
            // Skip calculation if no base price is set
            if (!dimensions.basePrice || dimensions.basePrice <= 0) {
                console.log('No base price available, skipping calculation');
                return null;
            }
            
            const cacheKey = generateCacheKey(dimensions);
            
            // Check cache first
            if (calculationCache.has(cacheKey)) {
                const cached = calculationCache.get(cacheKey);
                updateDisplay(cached);
                return cached;
            }
            
            const calculation = performCalculation(dimensions);
            
            // Cache the result
            calculationCache.set(cacheKey, calculation);
            
            // Update display
            updateDisplay(calculation);
            
            // Trigger custom event
            if (config.events.onPriceCalculated) {
                config.events.onPriceCalculated(calculation);
            }
            
            return calculation;
            
        } catch (error) {
            console.error('Price calculation error:', error);
            
            // Return a fallback calculation with base price
            const fallbackCalculation = {
                basePrice: config.basePrice,
                dimensionalPrice: config.basePrice,
                totalPrice: config.basePrice,
                calculations: [{
                    type: 'base_price',
                    description: 'Base price (calculation error)',
                    value: config.basePrice
                }],
                dimensions: getDimensions()
            };
            
            updateDisplay(fallbackCalculation);
            return fallbackCalculation;
        }
    }

    /**
     * Get current dimensions from form
     * @returns {Object} Dimensions object
     */
    function getDimensions() {
        return {
            width: parseFloat($(config.selectors.widthField).val()) || 0,
            height: parseFloat($(config.selectors.heightField).val()) || 0,
            coverage: parseFloat($(config.selectors.coverageField).val()) || 0,
            productType: config.productType,
            basePrice: config.basePrice
        };
    }

    /**
     * Perform the actual price calculation
     * @param {Object} dimensions Dimensions object
     * @returns {Object} Calculation result
     */
    function performCalculation(dimensions) {
        let dimensionalPrice = config.basePrice;
        let calculations = [];
        
        // Only perform dimensional calculations if we have the required data
        switch (config.productType) {
            case 'sqm':
                if (dimensions.width > 0 && dimensions.height > 0) {
                    const sqmResult = calculateSQM(dimensions);
                    dimensionalPrice = sqmResult.price;
                    calculations = sqmResult.calculations;
                } else {
                    calculations.push({
                        type: 'base_price',
                        description: 'Base price (dimensions not set)',
                        value: config.basePrice
                    });
                }
                break;
                
            case 'rm':
                if (dimensions.width > 0) {
                    const rmResult = calculateRM(dimensions);
                    dimensionalPrice = rmResult.price;
                    calculations = rmResult.calculations;
                } else {
                    calculations.push({
                        type: 'base_price',
                        description: 'Base price (width not set)',
                        value: config.basePrice
                    });
                }
                break;
                
            case 'roll':
                if (dimensions.coverage > 0) {
                    const rollResult = calculateRoll(dimensions);
                    dimensionalPrice = rollResult.price;
                    calculations = rollResult.calculations;
                } else {
                    calculations.push({
                        type: 'base_price',
                        description: 'Base price (coverage not set)',
                        value: config.basePrice
                    });
                }
                break;
                
            default:
                calculations.push({
                    type: 'base_price',
                    description: 'Base price (no dimensional calculation)',
                    value: config.basePrice
                });
        }
        
        // Apply minimum price
        let finalPrice = dimensionalPrice;
        if (config.minPrice > 0 && finalPrice < config.minPrice) {
            finalPrice = config.minPrice;
            calculations.push({
                type: 'minimum_price',
                description: 'Minimum price applied',
                value: config.minPrice
            });
        }
        
        return {
            basePrice: config.basePrice,
            dimensionalPrice: dimensionalPrice,
            totalPrice: finalPrice,
            calculations: calculations,
            dimensions: dimensions
        };
    }

    /**
     * Calculate SQM price
     * @param {Object} dimensions Dimensions object
     * @returns {Object} Calculation result
     */
    function calculateSQM(dimensions) {
        // Validate dimensions before calculation
        if (!dimensions.width || dimensions.width <= 0 || !dimensions.height || dimensions.height <= 0) {
            return {
                price: dimensions.basePrice,
                calculations: [{
                    type: 'base_price',
                    description: 'Base price (invalid dimensions)',
                    value: dimensions.basePrice
                }]
            };
        }
        
        const widthM = dimensions.width / 100;
        const heightM = dimensions.height / 100;
        let areaM2 = widthM * heightM;
        
        // Apply minimum area
        const minArea = config.settings.product_types?.sqm?.min_area || 1;
        if (areaM2 < minArea) {
            areaM2 = minArea;
        }
        
        const price = dimensions.basePrice * areaM2;
        
        return {
            price: price,
            calculations: [
                {
                    type: 'dimensions',
                    description: 'רוחב × גובה',
                    value: dimensions.width + 'ס״מ × ' + dimensions.height + 'ס״מ'
                },
                {
                    type: 'area',
                    description: 'שטח מחושב',
                    value: areaM2.toFixed(2) + ' מ״ר'
                },
                {
                    type: 'price_calculation',
                    description: 'מחיר בסיס × שטח',
                    value: formatPrice(dimensions.basePrice) + ' × ' + areaM2.toFixed(2) + ' = ' + formatPrice(price)
                }
            ]
        };
    }

    /**
     * Calculate RM price
     * @param {Object} dimensions Dimensions object
     * @returns {Object} Calculation result
     */
    function calculateRM(dimensions) {
        // Validate width before calculation
        if (!dimensions.width || dimensions.width <= 0) {
            return {
                price: dimensions.basePrice,
                calculations: [{
                    type: 'base_price',
                    description: 'Base price (invalid width)',
                    value: dimensions.basePrice
                }]
            };
        }
        
        const widthM = dimensions.width / 100;
        const price = dimensions.basePrice * widthM;
        
        return {
            price: price,
            calculations: [
                {
                    type: 'dimensions',
                    description: 'רוחב',
                    value: dimensions.width + 'ס״מ'
                },
                {
                    type: 'length',
                    description: 'מטר רץ',
                    value: widthM.toFixed(2) + ' מ׳'
                },
                {
                    type: 'price_calculation',
                    description: 'מחיר בסיס × אורך',
                    value: formatPrice(dimensions.basePrice) + ' × ' + widthM.toFixed(2) + ' = ' + formatPrice(price)
                }
            ]
        };
    }

    /**
     * Calculate Roll price
     * @param {Object} dimensions Dimensions object
     * @returns {Object} Calculation result
     */
    function calculateRoll(dimensions) {
        // Validate coverage before calculation
        if (!dimensions.coverage || dimensions.coverage <= 0) {
            return {
                price: dimensions.basePrice,
                calculations: [{
                    type: 'base_price',
                    description: 'Base price (invalid coverage)',
                    value: dimensions.basePrice
                }]
            };
        }
        
        // This is a simplified version - in real implementation,
        // you'd need to get roll dimensions from the product
        const marginPercentage = config.settings.product_types?.roll?.margin_percentage || 5;
        const coverageWithMargin = dimensions.coverage * (1 + marginPercentage / 100);
        
        // Simplified calculation - assume 1 roll per m²
        const rollsNeeded = Math.ceil(coverageWithMargin);
        const price = dimensions.basePrice * rollsNeeded;
        
        return {
            price: price,
            calculations: [
                {
                    type: 'coverage',
                    description: 'כיסוי נדרש',
                    value: dimensions.coverage.toFixed(2) + ' מ״ר'
                },
                {
                    type: 'coverage_with_margin',
                    description: 'כיסוי עם ' + marginPercentage + '% מרווח',
                    value: coverageWithMargin.toFixed(2) + ' מ״ר'
                },
                {
                    type: 'rolls_needed',
                    description: 'גלילים נדרשים',
                    value: rollsNeeded
                },
                {
                    type: 'price_calculation',
                    description: 'מחיר בסיס × גלילים',
                    value: formatPrice(dimensions.basePrice) + ' × ' + rollsNeeded + ' = ' + formatPrice(price)
                }
            ]
        };
    }

    /**
     * Update price display in the UI
     * @param {Object} calculation Calculation result
     */
    function updateDisplay(calculation) {
        if (!calculation) return;
        
        // Update main price display
        const formattedPrice = formatPrice(calculation.totalPrice);
        $(config.selectors.priceDisplay).html(formattedPrice);
        
        // Update any addon totals containers
        updateAddonTotals(calculation);
        
        // Add visual feedback
        $(config.selectors.form).removeClass('price-invalid').addClass('price-calculated');
    }

    /**
     * Update addon totals display
     * @param {Object} calculation Calculation result
     */
    function updateAddonTotals(calculation) {
        // Update WooCommerce Product Addons total if present
        const $addonTotal = $('#product-addons-total, .product-addon-totals');
        if ($addonTotal.length > 0) {
            $addonTotal.find('.price, .amount').html(formatPrice(calculation.totalPrice));
        }
    }

    /**
     * Validate form fields
     * @returns {Object} Validation result
     */
    function validateFields() {
        const errors = [];
        const dimensions = getDimensions();
        
        // Validate based on product type
        switch (config.productType) {
            case 'sqm':
                if (dimensions.width <= 0) errors.push('רוחב');
                if (dimensions.height <= 0) errors.push('גובה');
                break;
                
            case 'rm':
                if (dimensions.width <= 0) errors.push('רוחב');
                break;
                
            case 'roll':
                if (dimensions.coverage <= 0) errors.push('כיסוי');
                break;
        }
        
        // Validate variation selection for variable products
        const $variationInput = $('input[name="variation_id"]');
        if ($variationInput.length && (!$variationInput.val() || $variationInput.val() === '0')) {
            errors.push('בחירת וריאציה');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    /**
     * Format price for display
     * @param {number} price Price value
     * @returns {string} Formatted price
     */
    function formatPrice(price) {
        if (!price || isNaN(price)) {
            price = 0;
        }
        const formatted = price.toFixed(2);
        return formatted + ' ' + config.currencySymbol;
    }

    /**
     * Generate cache key for calculation
     * @param {Object} dimensions Dimensions object
     * @returns {string} Cache key
     */
    function generateCacheKey(dimensions) {
        return JSON.stringify({
            width: dimensions.width,
            height: dimensions.height,
            coverage: dimensions.coverage,
            productType: dimensions.productType,
            basePrice: dimensions.basePrice
        });
    }

    /**
     * Get current configuration
     * @returns {Object} Configuration object
     */
    function getConfig() {
        return $.extend(true, {}, config);
    }

    /**
     * Clear calculation cache
     */
    function clearCache() {
        calculationCache.clear();
    }

    return api;

})(jQuery);
