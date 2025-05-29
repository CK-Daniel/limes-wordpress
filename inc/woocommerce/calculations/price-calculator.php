<?php
/**
 * Main Price Calculator
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main price calculator class
 */
class Limes_Price_Calculator {

    /**
     * Calculate price based on product type and dimensions
     *
     * @param array $data Calculation data
     * @return array Calculation result
     */
    public static function calculate_price($data) {
        $defaults = array(
            'product_id' => 0,
            'variation_id' => 0,
            'base_price' => 0,
            'product_type' => '',
            'width' => 0,
            'height' => 0,
            'coverage' => 0,
            'addons' => array(),
            'quantity' => 1
        );

        $data = wp_parse_args($data, $defaults);

        // Get product type if not provided
        if (empty($data['product_type'])) {
            $product_id = $data['variation_id'] ? $data['product_id'] : $data['product_id'];
            $data['product_type'] = get_field('product_type_dimensions', $product_id);
        }

        // Get base price if not provided
        if (empty($data['base_price'])) {
            $product_id = $data['variation_id'] ? $data['variation_id'] : $data['product_id'];
            $product = wc_get_product($product_id);
            if ($product) {
                $data['base_price'] = $product->get_price('edit');
            }
        }

        $result = array(
            'base_price' => $data['base_price'],
            'dimensional_price' => $data['base_price'],
            'addon_price' => 0,
            'total_price' => $data['base_price'],
            'calculations' => array(),
            'errors' => array()
        );

        try {
            // Calculate dimensional price
            $dimensional_result = self::calculate_dimensional_price($data);
            $result['dimensional_price'] = $dimensional_result['price'];
            $result['calculations'] = array_merge($result['calculations'], $dimensional_result['calculations']);

            // Calculate addon price
            if (!empty($data['addons'])) {
                $addon_result = self::calculate_addon_price($result['dimensional_price'], $data['addons'], $data);
                $result['addon_price'] = $addon_result['price'];
                $result['calculations'] = array_merge($result['calculations'], $addon_result['calculations']);
            }

            // Calculate total
            $result['total_price'] = $result['dimensional_price'] + $result['addon_price'];

            // Apply minimum price
            $min_price_result = self::apply_minimum_price($result['total_price'], $data);
            $result['total_price'] = $min_price_result['price'];
            if ($min_price_result['applied']) {
                $result['calculations'][] = array(
                    'type' => 'minimum_price',
                    'description' => 'Minimum price applied',
                    'value' => $min_price_result['price']
                );
            }

        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Calculate dimensional price based on product type
     *
     * @param array $data Calculation data
     * @return array Dimensional calculation result
     */
    private static function calculate_dimensional_price($data) {
        $result = array(
            'price' => $data['base_price'],
            'calculations' => array()
        );

        switch ($data['product_type']) {
            case 'sqm':
                $result = self::calculate_sqm_price($data);
                break;
            case 'rm':
                $result = self::calculate_rm_price($data);
                break;
            case 'roll':
                $result = self::calculate_roll_price($data);
                break;
            default:
                $result['calculations'][] = array(
                    'type' => 'base_price',
                    'description' => 'No dimensional calculation (standard product)',
                    'value' => $data['base_price']
                );
        }

        return $result;
    }

    /**
     * Calculate square meter price
     *
     * @param array $data Calculation data
     * @return array SQM calculation result
     */
    private static function calculate_sqm_price($data) {
        $width_cm = (float) $data['width'];
        $height_cm = (float) $data['height'];

        if ($width_cm <= 0 || $height_cm <= 0) {
            throw new Exception('Invalid dimensions for SQM calculation');
        }

        $width_m = $width_cm / 100;
        $height_m = $height_cm / 100;
        $area_m2 = $width_m * $height_m;

        // Apply minimum area
        $settings = limes_get_product_type_settings('sqm');
        $min_area = $settings['min_area'];
        if ($area_m2 < $min_area) {
            $area_m2 = $min_area;
        }

        $price = $data['base_price'] * $area_m2;

        return array(
            'price' => $price,
            'calculations' => array(
                array(
                    'type' => 'dimensions',
                    'description' => 'Width x Height',
                    'value' => $width_cm . 'cm x ' . $height_cm . 'cm'
                ),
                array(
                    'type' => 'area',
                    'description' => 'Calculated area',
                    'value' => number_format($area_m2, 2) . ' m²'
                ),
                array(
                    'type' => 'price_calculation',
                    'description' => 'Base price x Area',
                    'value' => wc_price($data['base_price']) . ' x ' . number_format($area_m2, 2) . ' = ' . wc_price($price)
                )
            )
        );
    }

    /**
     * Calculate running meter price
     *
     * @param array $data Calculation data
     * @return array RM calculation result
     */
    private static function calculate_rm_price($data) {
        $width_cm = (float) $data['width'];

        if ($width_cm <= 0) {
            throw new Exception('Invalid width for RM calculation');
        }

        $width_m = $width_cm / 100;
        $price = $data['base_price'] * $width_m;

        return array(
            'price' => $price,
            'calculations' => array(
                array(
                    'type' => 'dimensions',
                    'description' => 'Width',
                    'value' => $width_cm . 'cm'
                ),
                array(
                    'type' => 'length',
                    'description' => 'Running meters',
                    'value' => number_format($width_m, 2) . ' m'
                ),
                array(
                    'type' => 'price_calculation',
                    'description' => 'Base price x Length',
                    'value' => wc_price($data['base_price']) . ' x ' . number_format($width_m, 2) . ' = ' . wc_price($price)
                )
            )
        );
    }

    /**
     * Calculate roll price
     *
     * @param array $data Calculation data
     * @return array Roll calculation result
     */
    private static function calculate_roll_price($data) {
        $coverage_needed = (float) $data['coverage'];

        if ($coverage_needed <= 0) {
            throw new Exception('Invalid coverage for Roll calculation');
        }

        $product_id = $data['variation_id'] ? $data['product_id'] : $data['product_id'];
        // Roll dimensions are stored in centimeters in ACF
        $roll_width_cm = (float) get_field('roll_width', $product_id);
        $roll_length_cm = (float) get_field('roll_length', $product_id);

        if ($roll_width_cm <= 0 || $roll_length_cm <= 0) {
            throw new Exception('Invalid roll dimensions');
        }

        // Add margin
        $settings = limes_get_product_type_settings('roll');
        $margin_percentage = $settings['margin_percentage'];
        $coverage_with_margin = $coverage_needed * (1 + $margin_percentage / 100);

        // Calculate roll area (convert cm to meters)
        $roll_area_m2 = ($roll_width_cm / 100) * ($roll_length_cm / 100);

        // Calculate rolls needed
        $rolls_needed = ceil($coverage_with_margin / $roll_area_m2);
        if ($rolls_needed < 1) {
            $rolls_needed = 1;
        }

        $price = $data['base_price'] * $rolls_needed;

        return array(
            'price' => $price,
            'calculations' => array(
                array(
                    'type' => 'coverage',
                    'description' => 'Coverage needed',
                    'value' => number_format($coverage_needed, 2) . ' m²'
                ),
                array(
                    'type' => 'coverage_with_margin',
                    'description' => 'Coverage with ' . $margin_percentage . '% margin',
                    'value' => number_format($coverage_with_margin, 2) . ' m²'
                ),
                array(
                    'type' => 'roll_dimensions',
                    'description' => 'Roll size',
                    'value' => $roll_width_cm . 'cm x ' . $roll_length_cm . 'cm (' . number_format($roll_area_m2, 2) . ' m²)'
                ),
                array(
                    'type' => 'rolls_needed',
                    'description' => 'Rolls needed',
                    'value' => $rolls_needed
                ),
                array(
                    'type' => 'price_calculation',
                    'description' => 'Base price x Rolls',
                    'value' => wc_price($data['base_price']) . ' x ' . $rolls_needed . ' = ' . wc_price($price)
                )
            )
        );
    }

    /**
     * Calculate addon price
     *
     * @param float $base_price Base price for addon calculations
     * @param array $addons Addon data
     * @param array $data Full calculation data
     * @return array Addon calculation result
     */
    private static function calculate_addon_price($base_price, $addons, $data) {
        $total_addon_price = 0;
        $calculations = array();

        foreach ($addons as $addon) {
            if (!isset($addon['price']) || !isset($addon['price_type'])) {
                continue;
            }

            $addon_price = (float) $addon['price'];
            $addon_name = isset($addon['name']) ? $addon['name'] : 'Unknown Addon';
            $calculated_price = 0;

            switch ($addon['price_type']) {
                case 'flat_fee':
                    $calculated_price = $addon_price;
                    break;

                case 'percentage_based':
                    $calculated_price = $base_price * ($addon_price / 100);
                    break;

                case 'quantity_based':
                    $calculated_price = $addon_price * $data['quantity'];
                    break;

                default:
                    $calculated_price = $addon_price;
            }

            // Handle meter-based addons for SQM and RM
            if (isset($addon['name']) && mb_strpos($addon['name'], 'למטר') !== false) {
                if ($data['product_type'] === 'sqm') {
                    $width_m = $data['width'] / 100;
                    $height_m = $data['height'] / 100;
                    $area = $width_m * $height_m;
                    $calculated_price = $calculated_price * $area;
                } elseif ($data['product_type'] === 'rm') {
                    $width_m = $data['width'] / 100;
                    $calculated_price = $calculated_price * $width_m;
                }
            }

            $total_addon_price += $calculated_price;

            $calculations[] = array(
                'type' => 'addon',
                'description' => $addon_name,
                'value' => wc_price($calculated_price)
            );
        }

        return array(
            'price' => $total_addon_price,
            'calculations' => $calculations
        );
    }

    /**
     * Apply minimum price if needed
     *
     * @param float $price Current price
     * @param array $data Calculation data
     * @return array Minimum price result
     */
    private static function apply_minimum_price($price, $data) {
        $product_id = $data['variation_id'] ? $data['product_id'] : $data['product_id'];
        $min_price = (float) get_field('pro_order_min_price', $product_id);

        if ($min_price > 0 && $price < $min_price) {
            return array(
                'price' => $min_price,
                'applied' => true
            );
        }

        return array(
            'price' => $price,
            'applied' => false
        );
    }
}
