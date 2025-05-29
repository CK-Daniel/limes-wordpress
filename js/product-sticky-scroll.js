jQuery(document).ready(function($) {
    // IMPORTANT: Make sure your CSS for .is-sticky class doesn't set a fixed top value
    // The JavaScript needs to control the top position dynamically
    
    // Configuration
    const config = {
        stickyClass: 'is-sticky',
        animateClass: 'content-shifted',
        offsetTop: 90, // Initial distance from top when sticky
        animationDuration: 300,
        scrollMovementFactor: 0.5, // How closely element follows scroll (0.5 = 50% of scroll speed)
        minDistanceFromPageBottom: 664, // Minimum distance from page bottom in pixels
        minScreenWidth: 975 // Minimum screen width for sticky behavior
    };

    // Cache DOM elements
    const $window = $(window);
    const $body = $('body');
    const $footer = $('footer');
    // Only target the first .part child within .parts
    const $stickySection = $('.single-product .parts > .part:first-child');
    
    // Exit if sticky section doesn't exist
    if (!$stickySection.length) return;

    // Get content that needs to shift
    const $mainContent = $('.single-product .product-main-content, .single-product .woocommerce-tabs, .single-product .related-products, .single-product .upsells');
    
    // Create wrapper for sticky behavior
    $stickySection.wrap('<div class="sticky-wrapper"></div>');
    const $stickyWrapper = $('.sticky-wrapper');
    
    // Store initial positions and dimensions
    let stickyData = {
        initialTop: 0,
        height: 0,
        wrapperHeight: 0,
        footerTop: 0,
        isSticky: false,
        documentHeight: 0,
        isEnabled: false // Track if sticky behavior is enabled
    };

    // Check if screen size allows sticky behavior
    function shouldEnableSticky() {
        return $window.width() >= config.minScreenWidth;
    }

    // Calculate positions
    function calculatePositions() {
        stickyData.initialTop = $stickyWrapper.offset().top;
        stickyData.height = $stickySection.outerHeight();
        stickyData.wrapperHeight = $stickyWrapper.height();
        stickyData.footerTop = $footer.offset().top;
        stickyData.documentHeight = $(document).height();
    }

    // Handle scroll behavior
    function handleScroll() {
        // Exit early if sticky behavior is disabled
        if (!stickyData.isEnabled) return;
        
        const scrollTop = $window.scrollTop();
        const windowHeight = $window.height();
        const triggerPoint = stickyData.initialTop - config.offsetTop;
        const stopPoint = stickyData.footerTop - stickyData.height - config.offsetTop;

        // Check if we should make it sticky
        if (scrollTop >= triggerPoint && scrollTop < stopPoint) {
            if (!stickyData.isSticky) {
                makeSticky();
            }
            
            // For "stuck in place" feel, keep the element at a fixed position from viewport top
            let desiredTopPosition = config.offsetTop;
            
            // NEW: Calculate maximum allowed top position based on page bottom constraint
            // Element's bottom position from page top = scrollTop + desiredTopPosition + elementHeight
            // Distance from page bottom = documentHeight - (scrollTop + desiredTopPosition + elementHeight)
            // We need: documentHeight - (scrollTop + desiredTopPosition + elementHeight) >= minDistanceFromPageBottom
            // Solving for desiredTopPosition: desiredTopPosition <= documentHeight - scrollTop - elementHeight - minDistanceFromPageBottom
            const maxAllowedTop = stickyData.documentHeight - scrollTop - stickyData.height - config.minDistanceFromPageBottom;
            
            // Apply the page bottom constraint
            desiredTopPosition = Math.min(desiredTopPosition, maxAllowedTop);
            
            // Check if we're approaching the footer
            const elementBottom = scrollTop + desiredTopPosition + stickyData.height;
            const availableSpace = stickyData.footerTop - elementBottom;
            
            // If we're getting close to footer, start slowing down
            if (availableSpace < 100) {
                const adjustment = Math.max(0, 100 - availableSpace);
                desiredTopPosition = Math.max(config.offsetTop, desiredTopPosition - adjustment);
            }
            
            // Ensure element is fixed and apply the calculated top position
            $stickySection.css({
                'position': 'fixed',
                'top': desiredTopPosition + 'px'
            });
            
            // Debug: Log the top position and distance from bottom (uncomment to debug)
            // const distanceFromBottom = stickyData.documentHeight - (scrollTop + desiredTopPosition + stickyData.height);
            // console.log('Scroll:', scrollTop, 'Top:', desiredTopPosition, 'Distance from bottom:', distanceFromBottom);
            
        } else if (scrollTop >= stopPoint) {
            // Near footer - stop the element from overlapping footer
            if (!stickyData.isSticky) {
                makeSticky();
            }
            
            // Keep the element just above the footer
            const spaceFromFooter = stickyData.footerTop - scrollTop;
            let topPosition = Math.max(config.offsetTop, spaceFromFooter - stickyData.height);
            
            // NEW: Also apply the page bottom constraint here
            const maxAllowedTop = stickyData.documentHeight - scrollTop - stickyData.height - config.minDistanceFromPageBottom;
            topPosition = Math.min(topPosition, maxAllowedTop);
            
            $stickySection.css({
                'position': 'fixed',
                'top': topPosition + 'px'
            });
            
        } else {
            // Remove sticky
            if (stickyData.isSticky) {
                removeSticky();
            }
        }
    }

    // Make element sticky
    function makeSticky() {
        stickyData.isSticky = true;
        
        // Set wrapper height to prevent jump
        $stickyWrapper.height(stickyData.wrapperHeight);
        
        // Add sticky class with animation
        $stickySection.addClass(config.stickyClass);
        $body.addClass('has-sticky-product');
        
        // Force fixed positioning and width
        $stickySection.css({
            'position': 'fixed',
            'width': $stickyWrapper.width() + 'px',
            'z-index': '1000'
        });
        
        // Animate content shift
        $mainContent.addClass(config.animateClass);
    }

    // Remove sticky behavior
    function removeSticky() {
        stickyData.isSticky = false;
        
        // Reset wrapper height
        $stickyWrapper.css('height', '');
        
        // Remove classes
        $stickySection.removeClass(config.stickyClass + ' is-absolute');
        $body.removeClass('has-sticky-product');
        $mainContent.removeClass(config.animateClass);
        
        // Reset inline styles
        $stickySection.css({
            'position': '',
            'top': '',
            'width': '',
            'z-index': ''
        });
    }

    // Enable sticky functionality
    function enableSticky() {
        stickyData.isEnabled = true;
        calculatePositions();
        handleScroll();
    }

    // Disable sticky functionality
    function disableSticky() {
        stickyData.isEnabled = false;
        if (stickyData.isSticky) {
            removeSticky();
        }
    }

    // Handle responsive behavior
    function handleResponsive() {
        if (shouldEnableSticky()) {
            if (!stickyData.isEnabled) {
                enableSticky();
            }
        } else {
            if (stickyData.isEnabled) {
                disableSticky();
            }
        }
    }

    // Throttle function for performance
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }

    // Initialize
    function init() {
        handleResponsive();
    }

    // Event listeners
    $window.on('scroll', throttle(handleScroll, 10));
    $window.on('resize', throttle(function() {
        handleResponsive();
        if (stickyData.isEnabled) {
            calculatePositions();
            handleScroll();
        }
    }, 250));

    // Initialize on load
    $window.on('load', init);
    
    // Also initialize immediately
    init();
});