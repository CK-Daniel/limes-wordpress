<?php
/**
 * Breadcrumb Customizations
 *
 * @package Limes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customize Yoast SEO breadcrumbs
 */
function limes_breadcrumb_single_link($link_output, $link) {
    if (!strpos($link_output, "breadcrumb_last")) {
        if (strpos($link['url'], "/category/")) {
            $link['url'] = get_permalink(18);
            $link['text'] = get_post(18)->post_title;
        }

        if (strpos($link['url'], "/shop/")) {
            return "";
        }
    }

    if (isset($link['text']) && (is_string($link['text']) && $link['text'] !== '')) {
        $link['text'] = trim($link['text']);
        
        if (!isset($link['allow_html']) || $link['allow_html'] !== true) {
            $link['text'] = esc_html($link['text']);
        }
        
        if ((strpos($link_output, "breadcrumb_last") == false && (isset($link['url']) && (is_string($link['url']) && $link['url'] !== '')))) {
            $link_output = '';
            $link_output .= '<span>';
            $title_attr = isset($link['title']) ? ' title="' . esc_attr($link['title']) . '"' : '';
            $link_output .= '<a href="' . esc_url($link['url']) . '" ' . $title_attr . '>' . $link['text'] . '</a>';
        } else {
            $inner_elm = 'span';
            if (strpos($link_output, "breadcrumb_last") && WPSEO_Options::get('breadcrumbs-boldlast') === true) {
                $inner_elm = 'strong';
            }

            $link_output = '';
            $link_output .= '<' . $inner_elm . ' class="breadcrumb_last" aria-current="page">' . $link['text'] . '</' . $inner_elm . '>';
        }
    }
    
    return $link_output;
}
add_filter('wpseo_breadcrumb_single_link', 'limes_breadcrumb_single_link', 10, 2);
