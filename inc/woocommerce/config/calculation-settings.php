<?php
/**
 * WooCommerce Calculation Settings
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get calculation settings for the theme
 */
function limes_get_calculation_settings() {
    return array(
        'product_types' => array(
            'sqm' => array(
                'name' => 'Square Meter',
                'label' => 'מ"ר',
                'min_area' => 1.0,
                'requires_width' => true,
                'requires_height' => true,
                'calculation_method' => 'area'
            ),
            'rm' => array(
                'name' => 'Running Meter',
                'label' => 'מטר רץ',
                'min_length' => 0.1,
                'requires_width' => true,
                'requires_height' => false,
                'calculation_method' => 'length'
            ),
            'roll' => array(
                'name' => 'Roll',
                'label' => 'גליל',
                'margin_percentage' => 5,
                'requires_coverage' => true,
                'calculation_method' => 'coverage'
            )
        ),
        'addon_types' => array(
            'flat_fee' => array(
                'name' => 'Flat Fee',
                'calculation' => 'fixed'
            ),
            'percentage_based' => array(
                'name' => 'Percentage Based',
                'calculation' => 'percentage'
            ),
            'quantity_based' => array(
                'name' => 'Quantity Based',
                'calculation' => 'quantity'
            )
        ),
        'validation' => array(
            'min_width' => 1,
            'max_width' => 10000,
            'min_height' => 1,
            'max_height' => 10000,
            'min_coverage' => 0.1,
            'max_coverage' => 10000
        ),
        'currency' => array(
            'symbol' => '₪',
            'position' => 'right',
            'decimals' => 2
        )
    );
}

/**
 * Get product type settings
 */
function limes_get_product_type_settings($type) {
    $settings = limes_get_calculation_settings();
    return isset($settings['product_types'][$type]) ? $settings['product_types'][$type] : null;
}

/**
 * Get validation settings
 */
function limes_get_validation_settings() {
    $settings = limes_get_calculation_settings();
    return $settings['validation'];
}

/**
 * Get currency settings
 */
function limes_get_currency_settings() {
    $settings = limes_get_calculation_settings();
    return $settings['currency'];
}
