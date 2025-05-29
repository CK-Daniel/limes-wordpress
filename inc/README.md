# Limes Theme - Modular Structure

This directory contains the modular organization of the Limes WordPress theme. The theme has been refactored to improve maintainability, organization, and performance while preserving all existing functionality.

## Directory Structure

### `/core/`
Core theme functionality and setup files:

- **`theme-setup.php`** - Theme setup, support features, and basic configuration
- **`enqueue-scripts.php`** - All script and style enqueuing logic
- **`image-sizes.php`** - Custom image size definitions and media upload settings
- **`menus.php`** - Menu registration and menu-related functions
- **`post-types.php`** - Custom post type definitions (Designer, etc.)
- **`taxonomies.php`** - Custom taxonomy definitions
- **`utilities.php`** - Helper functions and utilities
- **`admin.php`** - Admin-specific functionality (ACF options, etc.)

### `/templates/`
Template-related functions:

- **`product-templates.php`** - Product display templates and functions
- **`post-templates.php`** - Post display templates and functions

### `/woocommerce/`
WooCommerce integration files:

- **`setup.php`** - WooCommerce theme setup and basic integration
- **`product-page/`** - Product page specific functionality (to be created)
- **`cart/`** - Cart and checkout functionality (to be created)

### `/features/`
Additional theme features:

- **`breadcrumbs.php`** - Breadcrumb customizations (Yoast SEO integration)

## Benefits of This Structure

### 1. **Improved Organization**
- Each file has a single, clear responsibility
- Related functions are grouped together
- Easy to locate specific functionality

### 2. **Better Maintainability**
- Shorter, more focused files
- Clear dependencies between components
- Easier to modify specific features without affecting others

### 3. **Enhanced Performance**
- Conditional loading of WooCommerce files only when needed
- Better separation of concerns
- Optimized hook management

### 4. **Developer Experience**
- Clear file naming conventions
- Consistent code organization
- Easy to extend and modify

## Backward Compatibility

The refactoring maintains full backward compatibility:

- All existing function names continue to work
- Template functions are available globally
- Legacy files are preserved but cleaned up
- No breaking changes to existing functionality

## Migration Notes

### What Was Moved:
- Theme setup functions → `core/theme-setup.php`
- Script enqueuing → `core/enqueue-scripts.php`
- Image sizes → `core/image-sizes.php`
- Menu registration → `core/menus.php`
- Custom post types → `core/post-types.php`
- Custom taxonomies → `core/taxonomies.php`
- Utility functions → `core/utilities.php`
- Template functions → `templates/`
- WooCommerce setup → `woocommerce/setup.php`
- Breadcrumb customizations → `features/breadcrumbs.php`

### What Remains:
- WooCommerce product page functions (to be further modularized)
- WooCommerce cart calculations (to be further modularized)
- Legacy compatibility functions

## Future Improvements

The following areas are planned for further modularization:

1. **WooCommerce Product Page Functions**
   - Split into dimensions, installation, price display modules
   - Separate validation logic
   - Improve JavaScript organization

2. **WooCommerce Cart Functions**
   - Separate calculation logic from display logic
   - Modularize price validation
   - Improve cart display functions

3. **Additional Features**
   - SEO optimizations
   - Performance enhancements
   - Security improvements

## Usage

All files are automatically loaded through the main `functions.php` file. No changes are needed to existing code - everything continues to work as before, but with better organization.

To add new functionality:

1. **Core features** → Add to appropriate file in `/core/`
2. **Template functions** → Add to appropriate file in `/templates/`
3. **WooCommerce features** → Add to appropriate file in `/woocommerce/`
4. **New features** → Create new file in `/features/`

## File Loading Order

1. Core files (theme setup, scripts, etc.)
2. Template functions
3. Feature files
4. WooCommerce integration (if WooCommerce is active)
5. Legacy files (for backward compatibility)

This ensures proper dependency management and optimal loading performance.
