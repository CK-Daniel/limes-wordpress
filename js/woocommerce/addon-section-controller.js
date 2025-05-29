/**
 * Addon Section Controller
 * Controls visibility of addon totals section based on required field validation
 * 
 * @package Limes
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        selectors: {
            addonSection: '.universal-addon-section, #product-addons-total, .wc-pao-addons-container',
            colorAttribute: 'input[name="attribute_pa_color"]',
            mechanismRadio: 'input[name="prod_radio-gr2"]',
            installationRadio: 'input[name="prod_radio-gr1"]',
            variationForm: '.single_variation_wrap',
            form: 'form.cart',
            // Add dimension field selectors
            widthField: '#prod_width',
            heightField: '#prod_height',
            coverageField: '#prod_coverage'
        },
        classes: {
            sectionVisible: 'addon-section-visible',
            fieldsComplete: 'required-fields-complete'
        }
    };

    /**
     * Initialize the addon section controller
     */
    function init() {
        bindEvents();
        checkFieldsAndToggleSection();
        
        console.log('✅ Addon Section Controller initialized');
    }

    /**
     * Bind events to form fields
     */
    function bindEvents() {
        const $form = $(config.selectors.form);
        
        // Listen for changes on required fields
        $form.on('change', config.selectors.colorAttribute, handleFieldChange);
        $form.on('change', 'select[name="attribute_pa_color"]', handleFieldChange); // Add select dropdown listener
        $form.on('change', config.selectors.mechanismRadio, handleFieldChange);
        $form.on('change', config.selectors.installationRadio, handleFieldChange);
        
        // Add dimension field listeners
        $form.on('input change blur', config.selectors.widthField, handleDimensionChange);
        $form.on('input change blur', config.selectors.heightField, handleDimensionChange);
        $form.on('input change blur', config.selectors.coverageField, handleDimensionChange);
        
        // Listen for variation changes
        $form.on('found_variation reset_data', handleVariationChange);
        
        // Listen for addon changes
        $(document).on('change', '.wc-pao-addon-field', handleAddonChange);
        
        // Initial check after DOM is ready
        $(document).ready(function() {
            setTimeout(checkFieldsAndToggleSection, 500);
        });
    }

    /**
     * Handle field changes
     */
    function handleFieldChange() {
        // Small delay to ensure DOM is updated
        setTimeout(checkFieldsAndToggleSection, 100);
    }

    /**
     * Handle variation changes
     */
    function handleVariationChange() {
        // Delay to ensure variation data is processed
        setTimeout(checkFieldsAndToggleSection, 200);
    }

    /**
     * Handle addon changes
     */
    function handleAddonChange() {
        // Only update if section is already visible
        if (areRequiredFieldsComplete()) {
            setTimeout(updateAddonTotals, 100);
        }
    }

    /**
     * Handle dimension field changes
     */
    function handleDimensionChange() {
        // Small delay to match product-addons-integration.js behavior
        setTimeout(checkFieldsAndToggleSection, 100);
    }

    /**
     * Check if dimension fields are valid
     * @returns {boolean} True if dimensions are properly filled
     */
    function areDimensionsValid() {
        const $widthField = $(config.selectors.widthField);
        const $heightField = $(config.selectors.heightField);
        const $coverageField = $(config.selectors.coverageField);
        
        // Roll type product
        if ($coverageField.length > 0) {
            const coverageVal = $coverageField.val();
            return coverageVal && coverageVal.trim() !== '';
        }
        
        // SQM or RM type product
        if ($widthField.length > 0) {
            const widthVal = $widthField.val();
            if (!widthVal || widthVal.trim() === '') {
                return false;
            }
            
            // For SQM, also check height (if not RM type)
            if ($heightField.length > 0 && !$('.wrap_height').hasClass('wrap_height_rm')) {
                const heightVal = $heightField.val();
                return heightVal && heightVal.trim() !== '';
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Check if all required fields are complete
     * @returns {boolean} True if all required fields are selected
     */
    function areRequiredFieldsComplete() {
        // Check what type of fields this product has
        const hasDimensions = $(config.selectors.widthField).length > 0 || 
                             $(config.selectors.heightField).length > 0 || 
                             $(config.selectors.coverageField).length > 0;
        
        const hasColorField = $(config.selectors.colorAttribute).length > 0 || $('select[name="attribute_pa_color"]').length > 0;
        const hasMechanismField = $(config.selectors.mechanismRadio).length > 0;
        const hasInstallationField = $(config.selectors.installationRadio).length > 0;
        
        // Determine product type
        const hasSelectionFields = hasColorField || hasMechanismField || hasInstallationField;
        
        let dimensionsComplete = true;
        let selectionsComplete = true;
        
        // Check dimensions if product has them
        if (hasDimensions) {
            dimensionsComplete = areDimensionsValid();
        }
        
        // Check selections if product has them
        if (hasSelectionFields) {
            // For color field, check both radio buttons and select dropdown
            let colorSelected = true;
            if (hasColorField) {
                // Check if it's a radio button or select dropdown
                const colorRadio = $(config.selectors.colorAttribute + ':checked').length > 0;
                const colorSelect = $('select[name="attribute_pa_color"]').val() !== '';
                colorSelected = colorRadio || colorSelect;
            }
            
            const mechanismSelected = !hasMechanismField || $(config.selectors.mechanismRadio + ':checked').length > 0;
            const installationSelected = !hasInstallationField || $(config.selectors.installationRadio + ':checked').length > 0;
            const variationId = $('input[name="variation_id"]').val();
            const variationSelected = !variationId || variationId !== '0';
            
            selectionsComplete = colorSelected && mechanismSelected && installationSelected && variationSelected;
        }
        
        // For products with ONLY dimensions (no selection fields), only check dimensions
        // For products with ONLY selections (no dimensions), only check selections  
        // For products with BOTH, require BOTH to be complete
        const allComplete = dimensionsComplete && selectionsComplete;
        
        // Debug logging in existing style
        console.log('Field validation:', {
            hasDimensions,
            hasSelectionFields,
            dimensionsComplete,
            selectionsComplete,
            allComplete,
            fieldsExist: {
                colorField: hasColorField,
                mechanismField: hasMechanismField,
                installationField: hasInstallationField,
                widthField: $(config.selectors.widthField).length > 0,
                heightField: $(config.selectors.heightField).length > 0,
                coverageField: $(config.selectors.coverageField).length > 0
            }
        });
        
        return allComplete;
    }

    /**
     * Check fields and toggle addon section visibility
     */
    function checkFieldsAndToggleSection() {
        const fieldsComplete = areRequiredFieldsComplete();
        const $form = $(config.selectors.form);
        const $addonSection = $(config.selectors.addonSection);
        
        if (fieldsComplete) {
            // Show addon section
            $addonSection.show();
            $form.addClass(config.classes.sectionVisible);
            $form.addClass(config.classes.fieldsComplete);
            
            // Update addon totals
            updateAddonTotals();
            
            console.log('✅ All required fields complete - showing addon section');
        } else {
            // Hide addon section
            $addonSection.hide();
            $form.removeClass(config.classes.sectionVisible);
            $form.removeClass(config.classes.fieldsComplete);
            
            console.log('❌ Required fields incomplete - hiding addon section');
        }
    }

    /**
     * Update addon totals display
     */
    function updateAddonTotals() {
        // Trigger WooCommerce addon calculation if available
        if (typeof wc_pao_update_totals === 'function') {
            wc_pao_update_totals();
        }
        
        // Trigger custom price calculation if available
        if (window.LimesPriceCalculator && typeof window.LimesPriceCalculator.calculate === 'function') {
            window.LimesPriceCalculator.calculate();
        }
        
        // Update any custom addon displays
        updateCustomAddonDisplay();
    }

    /**
     * Update custom addon display
     */
    function updateCustomAddonDisplay() {
        const $addonTotal = $('#product-addons-total');
        if ($addonTotal.length === 0) return;
        
        // Get selected addon values
        const selectedAddons = [];
        let addonTotal = 0;
        
        $('.wc-pao-addon-field').each(function() {
            const $field = $(this);
            const value = $field.val();
            
            if (value && value !== '') {
                const price = parseFloat($field.find('option:selected').data('price')) || 0;
                const label = $field.find('option:selected').data('label') || value;
                
                selectedAddons.push({
                    label: label,
                    price: price
                });
                
                addonTotal += price;
            }
        });
        
        // Update display if we have custom logic
        if (selectedAddons.length > 0) {
            console.log('Selected addons:', selectedAddons, 'Total:', addonTotal);
        }
    }

    /**
     * Get selected field values for debugging
     * @returns {Object} Selected values
     */
    function getSelectedValues() {
        return {
            color: $(config.selectors.colorAttribute + ':checked').val() || null,
            mechanism: $(config.selectors.mechanismRadio + ':checked').val() || null,
            installation: $(config.selectors.installationRadio + ':checked').val() || null,
            variationId: $('input[name="variation_id"]').val() || null
        };
    }

    /**
     * Public API
     */
    window.LimesAddonController = {
        init: init,
        checkFields: checkFieldsAndToggleSection,
        areFieldsComplete: areRequiredFieldsComplete,
        getSelectedValues: getSelectedValues
    };

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        // Small delay to ensure other scripts are loaded
        setTimeout(init, 100);
    });

})(jQuery);
