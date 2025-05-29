/**
 * WooCommerce BlockUI Fix
 * 
 * This script ensures that the jQuery BlockUI plugin functions are available
 * for WooCommerce cart operations, even if there are jQuery conflicts.
 */
jQuery(document).ready(function($) {
    // Check if block function exists
    if (typeof $.fn.block === 'undefined') {
        console.warn('jQuery BlockUI not loaded. Implementing fallback...');
        
        // Simple fallback implementation
        $.fn.block = function(options) {
            var opts = $.extend({}, $.blockUI.defaults, options || {});
            return this.each(function() {
                var $el = $(this);
                
                // Add loading overlay
                var overlay = $('<div class="blockUI blockOverlay"></div>').css({
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    width: '100%',
                    height: '100%',
                    background: 'rgba(255,255,255,0.6)',
                    zIndex: 1000
                });
                
                var message = $('<div class="blockUI blockMsg blockElement"></div>').css({
                    position: 'absolute',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    zIndex: 1001
                });
                
                if (opts.message) {
                    message.html(opts.message);
                } else {
                    // Default loading spinner
                    message.html('<div class="woocommerce-loading"></div>');
                }
                
                // Make element relative if static
                if ($el.css('position') === 'static') {
                    $el.css('position', 'relative');
                }
                
                $el.addClass('processing').append(overlay).append(message);
            });
        };
        
        $.fn.unblock = function() {
            return this.each(function() {
                $(this).removeClass('processing')
                    .find('.blockUI').remove();
            });
        };
        
        // Also add to jQuery object for global blocking
        $.blockUI = function(options) {
            $('body').block(options);
        };
        
        $.unblockUI = function() {
            $('body').unblock();
        };
        
        // Set defaults
        $.blockUI.defaults = {
            message: '<div class="woocommerce-loading"></div>',
            css: {},
            overlayCSS: {}
        };
    }
    
    // Additional fix for cart update triggers
    $(document.body).on('updated_cart_totals', function() {
        // Re-initialize quantity buttons after cart update
        if (typeof plus_minns_listeners === 'function') {
            plus_minns_listeners();
        }
    });
    
    // Monitor for cart AJAX requests
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Check if this is a cart-related request
        if (settings.url && settings.url.includes('wc-ajax=')) {
            // Ensure BlockUI is available after AJAX
            if (typeof $.fn.block === 'undefined' && typeof $.fn.blockUI !== 'undefined') {
                $.fn.block = $.fn.blockUI;
                $.fn.unblock = $.fn.unblockUI;
            }
        }
    });
});

// Add basic CSS for the loading overlay
jQuery(document).ready(function() {
    if (!jQuery('#wc-blockui-css').length) {
        jQuery('head').append(`
            <style id="wc-blockui-css">
                .woocommerce-loading {
                    width: 60px;
                    height: 60px;
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #3498db;
                    border-radius: 50%;
                    animation: wc-spin 1s linear infinite;
                }
                
                @keyframes wc-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .processing .blockOverlay {
                    cursor: wait !important;
                }
                
                .processing .blockMsg {
                    border: none !important;
                    background: transparent !important;
                    color: #555 !important;
                }
            </style>
        `);
    }
});