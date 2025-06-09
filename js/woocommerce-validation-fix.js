/**
 * Fix for WooCommerce validation message loops
 * 
 * This script completely replaces WooCommerce's validation system to prevent alert loops
 * by stopping alerts from being triggered in the first place.
 */
jQuery(document).ready(function($) {
    // Wait for the page to fully load
    $(window).on('load', function() {
        // Only run on product pages
        if (!$('body').hasClass('single-product')) return;
        
        // Check if this product only has pricing variations
        var $form = $('form.variations_form');
        if ($form.length) {
            var onlyHasPricingVariations = true;
            $form.find('.variations select').each(function() {
                var attrName = $(this).attr('name') || '';
                var decodedName = decodeURIComponent(attrName);
                // Check both encoded and decoded versions
                if (!attrName.includes('תמחור') && !attrName.includes('pricing') &&
                    !attrName.includes('%d7%aa%d7%9e%d7%97%d7%95%d7%a8') && // encoded תמחור
                    !decodedName.includes('תמחור') && !decodedName.includes('pricing')) {
                    onlyHasPricingVariations = false;
                    return false; // break
                }
            });
            
            // If only pricing variations, remove validation classes and set a valid variation
            if (onlyHasPricingVariations) {
                console.log('Product only has pricing variations - disabling variation validation');
                
                // Remove validation classes
                $('.single_add_to_cart_button').removeClass('wc-variation-selection-needed disabled');
                $('.woocommerce-variation-add-to-cart').removeClass('woocommerce-variation-add-to-cart-disabled');
                
                // Set a default variation ID if available
                var variations = $form.data('product_variations');
                if (variations && variations.length > 0) {
                    // Use the first variation as default
                    var $variationIdInput = $form.find('input[name="variation_id"]');
                    $variationIdInput.val(variations[0].variation_id);
                    
                    // Trigger variation found event
                    $form.trigger('found_variation', [variations[0]]);
                    
                    // Make sure the variation price is shown
                    $('.woocommerce-variation-price').show();
                    $('.single_variation').show();
                    
                    // Update button state
                    $('.single_add_to_cart_button').prop('disabled', false);
                }
                
                // Completely disable validation for this form
                $form.data('disable-variation-validation', true);
                
                // Keep classes updated on any change
                $form.on('change', '.variations select', function() {
                    // Always keep button enabled for pricing-only products
                    setTimeout(function() {
                        $('.single_add_to_cart_button').removeClass('wc-variation-selection-needed disabled');
                        $('.woocommerce-variation-add-to-cart').removeClass('woocommerce-variation-add-to-cart-disabled');
                    }, 100);
                });
            }
        }
        
        // Global validation state
        window.validationState = {
            alertShown: false,
            lastAlertTime: 0,
            alertCooldown: 1000, // 1 second cooldown between alerts
            variationSelected: false,
            processing: false
        };
        
        // Completely override the alert function with a more efficient version
        var originalAlert = window.alert;
        window.alert = function(message) {
            // Only show alerts about variations once
            if (message && (
                message.indexOf('וריאציה') !== -1 || 
                message.indexOf('variation') !== -1 ||
                message.indexOf('אפשרות המוצר') !== -1
            )) {
                // Check if we've shown this alert recently
                var now = new Date().getTime();
                if (window.validationState.alertShown || 
                    (now - window.validationState.lastAlertTime < window.validationState.alertCooldown)) {
                    // Silently ignore without logging
                    return;
                }
                
                // Update state
                window.validationState.alertShown = true;
                window.validationState.lastAlertTime = now;
                
                // Show the alert
                originalAlert.call(window, message);
                
                // Reset state after cooldown
                setTimeout(function() {
                    window.validationState.alertShown = false;
                }, window.validationState.alertCooldown);
                
                return;
            }
            
            // For other alerts, just show them normally
            originalAlert.apply(this, arguments);
        };
        
        // Completely replace WooCommerce's validation system
        if (typeof $.fn.wc_variations_form !== 'undefined') {
            // Store original functions we'll need to call
            var originalFunctions = {
                onAddToCart: $.fn.wc_variations_form.prototype.onAddToCart,
                foundVariation: $.fn.wc_variations_form.prototype.found_variation,
                showVariation: $.fn.wc_variations_form.prototype.show_variation
            };
            
            // Replace the check_variations method to prevent it from showing alerts
            $.fn.wc_variations_form.prototype.check_variations = function(focus, changed) {
                var $form = this;
                var formValues = [];
                var allSet = true;
                
                // Get the attributes data
                if (!$form.data('product_variations')) {
                    return;
                }
                
                // Get the current values
                $form.find('.variations select').each(function() {
                    var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                    var value = $(this).val() || '';
                    
                    // Skip pricing attributes that don't require selection
                    var decodedAttrName = decodeURIComponent(attribute_name);
                    if (attribute_name && (attribute_name.includes('תמחור') || attribute_name.includes('pricing') ||
                        attribute_name.includes('%d7%aa%d7%9e%d7%97%d7%95%d7%a8') || // encoded תמחור
                        decodedAttrName.includes('תמחור') || decodedAttrName.includes('pricing'))) {
                        return true; // continue to next iteration
                    }
                    
                    formValues.push({
                        attribute_name: attribute_name,
                        value: value
                    });
                    
                    if (value.length === 0) {
                        allSet = false;
                    }
                });
                
                // Radio buttons support
                $form.find('.variations input[type=radio]:checked').each(function() {
                    var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                    var value = $(this).val() || '';
                    
                    formValues.push({
                        attribute_name: attribute_name,
                        value: value
                    });
                    
                    if (value.length === 0) {
                        allSet = false;
                    }
                });
                
                // Update validation state
                window.validationState.variationSelected = allSet;
                
                // If all attributes are set, find the matching variation
                if (allSet) {
                    var matchingVariations = $form.find_matching_variations($form.data('product_variations'), formValues);
                    
                    if (matchingVariations.length > 0) {
                        $form.trigger('found_variation', [matchingVariations[0]]);
                    } else {
                        // No matching variation - but don't show an alert
                        $form.find('.reset_variations').removeClass('hide');
                    }
                } else {
                    $form.trigger('reset_data');
                    
                    if (!focus) {
                        $form.find('.reset_variations').addClass('hide');
                    }
                }
            };
            
            // Replace the onAddToCart method
            $.fn.wc_variations_form.prototype.onAddToCart = function(e) {
                // Check if validation is disabled for this form
                if ($(this).data('disable-variation-validation')) {
                    // Call original function without any validation
                    return originalFunctions.onAddToCart.call(this, e);
                }
                
                // If already processing, prevent action
                if (window.validationState.processing) {
                    e.preventDefault();
                    return false;
                }
                
                // Set processing flag
                window.validationState.processing = true;
                
                // Check if this form has mechanism choices (radio buttons for side/installation)
                var hasMechanismChoices = false;
                var $mechanismRadios = $(this).find('input[name="prod_radio-gr1"], input[name="prod_radio-gr2"]');
                if ($mechanismRadios.length > 0) {
                    hasMechanismChoices = true;
                    
                    // Check if mechanism choices are selected
                    var mechanismSelected = true;
                    var radioGroups = {};
                    $mechanismRadios.each(function() {
                        var groupName = $(this).attr('name');
                        radioGroups[groupName] = true;
                    });
                    
                    // Check each radio group has a selection
                    for (var groupName in radioGroups) {
                        if (!$(this).find('input[name="' + groupName + '"]:checked').length) {
                            mechanismSelected = false;
                            break;
                        }
                    }
                    
                    // If mechanism choices exist but aren't selected, show alert
                    if (!mechanismSelected) {
                        if (!window.validationState.alertShown) {
                            alert('בחר את אפשרות המוצר המתאימה לפני ההוספה לסל הקניות');
                        }
                        
                        e.preventDefault();
                        
                        // Reset processing flag after delay
                        setTimeout(function() {
                            window.validationState.processing = false;
                        }, 500);
                        
                        return false;
                    }
                }
                
                // Check if this product has any non-pricing variations
                var hasNonPricingVariations = false;
                $(this).find('.variations select').each(function() {
                    var attrName = $(this).attr('name') || '';
                    var decodedName = decodeURIComponent(attrName);
                    // Check both encoded and decoded versions
                    if (!attrName.includes('תמחור') && !attrName.includes('pricing') &&
                        !attrName.includes('%d7%aa%d7%9e%d7%97%d7%95%d7%a8') && // encoded תמחור
                        !decodedName.includes('תמחור') && !decodedName.includes('pricing')) {
                        hasNonPricingVariations = true;
                        return false; // break
                    }
                });
                
                // Only validate variations if there are non-pricing variations and NO mechanism choices
                if (!hasMechanismChoices && hasNonPricingVariations && !window.validationState.variationSelected) {
                    // Show alert only if one hasn't been shown recently
                    if (!window.validationState.alertShown) {
                        alert('בחר את אפשרות המוצר המתאימה לפני ההוספה לסל הקניות');
                    }
                    
                    e.preventDefault();
                    
                    // Reset processing flag after delay
                    setTimeout(function() {
                        window.validationState.processing = false;
                    }, 500);
                    
                    return false;
                }
                
                // Call original function if validation passes
                var result = originalFunctions.onAddToCart.call(this, e);
                
                // Reset processing flag after delay
                setTimeout(function() {
                    window.validationState.processing = false;
                }, 500);
                
                return result;
            };
            
            // Replace the found_variation method
            $.fn.wc_variations_form.prototype.found_variation = function(variation) {
                // Update validation state
                window.validationState.variationSelected = true;
                window.validationState.alertShown = false;
                window.validationState.processing = false;
                
                // Call original function
                return originalFunctions.foundVariation.call(this, variation);
            };
            
            // Replace the show_variation method
            $.fn.wc_variations_form.prototype.show_variation = function(variation, purchasable) {
                // Update validation state
                window.validationState.variationSelected = true;
                window.validationState.alertShown = false;
                window.validationState.processing = false;
                
                // Call original function
                return originalFunctions.showVariation.call(this, variation, purchasable);
            };
        }
        
        // Handle reset variations button
        $(document).on('click', '.reset_variations', function() {
            // Reset validation state
            window.validationState.variationSelected = false;
            window.validationState.alertShown = false;
            window.validationState.processing = false;
        });
        
        // Handle attribute changes
        $(document).on('change', '.variations select, .variations input[type=radio]', function() {
            // Reset alert state when user is actively making selections
            window.validationState.alertShown = false;
        });
        
        // Handle add to cart button clicks
        $(document).on('click', '.single_add_to_cart_button, .add_to_cart_trigger_btn', function(e) {
            // If it's a variable product but no variation is selected
            var $form = $(this).closest('form.cart');
            
            if ($form.hasClass('variations_form')) {
                // Check if validation is disabled for this form
                if ($form.data('disable-variation-validation')) {
                    // Allow the click to proceed without validation
                    return true;
                }
                
                // Check if this form has mechanism choices (radio buttons for side/installation)
                var hasMechanismChoices = false;
                var $mechanismRadios = $form.find('input[name="prod_radio-gr1"], input[name="prod_radio-gr2"]');
                if ($mechanismRadios.length > 0) {
                    hasMechanismChoices = true;
                    
                    // Check if mechanism choices are selected
                    var mechanismSelected = true;
                    var radioGroups = {};
                    $mechanismRadios.each(function() {
                        var groupName = $(this).attr('name');
                        radioGroups[groupName] = true;
                    });
                    
                    // Check each radio group has a selection
                    for (var groupName in radioGroups) {
                        if (!$form.find('input[name="' + groupName + '"]:checked').length) {
                            mechanismSelected = false;
                            break;
                        }
                    }
                    
                    // If mechanism choices exist but aren't selected, show alert
                    if (!mechanismSelected) {
                        if (!window.validationState.alertShown) {
                            alert('בחר את אפשרות המוצר המתאימה לפני ההוספה לסל הקניות');
                        }
                        
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }
                
                // Check if this product has any non-pricing variations
                var hasNonPricingVariations = false;
                $form.find('.variations select').each(function() {
                    var attrName = $(this).attr('name') || '';
                    var decodedName = decodeURIComponent(attrName);
                    // Check both encoded and decoded versions
                    if (!attrName.includes('תמחור') && !attrName.includes('pricing') &&
                        !attrName.includes('%d7%aa%d7%9e%d7%97%d7%95%d7%a8') && // encoded תמחור
                        !decodedName.includes('תמחור') && !decodedName.includes('pricing')) {
                        hasNonPricingVariations = true;
                        return false; // break
                    }
                });
                
                // Only validate variations if there are non-pricing variations and NO mechanism choices
                if (!hasMechanismChoices && hasNonPricingVariations && !window.validationState.variationSelected) {
                    // Check if variation is selected
                    var $variationInput = $form.find('input[name="variation_id"]');
                    if ($variationInput.length && (!$variationInput.val() || $variationInput.val() === '0')) {
                        // Show alert only if one hasn't been shown recently
                        if (!window.validationState.alertShown) {
                            alert('בחר את אפשרות המוצר המתאימה לפני ההוספה לסל הקניות');
                        }
                        
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }
            }
        });
        
        // Additional fix for pricing-only products
        // Monitor and immediately remove validation classes
        if ($('form.variations_form').data('disable-variation-validation')) {
            // Use MutationObserver to catch any attempts to add validation classes
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        var $target = $(mutation.target);
                        if ($target.hasClass('wc-variation-selection-needed') || $target.hasClass('disabled')) {
                            $target.removeClass('wc-variation-selection-needed disabled');
                        }
                        if ($target.hasClass('woocommerce-variation-add-to-cart-disabled')) {
                            $target.removeClass('woocommerce-variation-add-to-cart-disabled');
                        }
                    }
                });
            });
            
            // Observe the add to cart button and its container
            var $button = $('.single_add_to_cart_button');
            var $container = $('.woocommerce-variation-add-to-cart');
            
            if ($button.length) {
                observer.observe($button[0], { attributes: true, attributeFilter: ['class'] });
            }
            if ($container.length) {
                observer.observe($container[0], { attributes: true, attributeFilter: ['class'] });
            }
        }
    });
});
