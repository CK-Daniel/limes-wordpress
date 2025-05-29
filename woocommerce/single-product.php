<?php 
/**
 * Custom Single Product Template
 *
 * This template includes product details, the add‑to‑cart form (which handles both variable and simple products),
 * product gallery, cross‑sell products, and (via hooks) our custom dimensions fields.
 *
 * Note:
 * - If custom dimensions are enabled, they will be output via WooCommerce hooks.
 * - The default WooCommerce variation form is used for variable products.
 */

$td = get_template_directory_uri();
get_header();

global $page_title;
$page_title = "קטלוג מוצרים";
get_template_part( 'template-parts/top-inner' );

global $product;
$id  = $product->get_id();
$sku = $product->get_sku();
?>
<section class="product <?php echo ( $product->is_type( 'simple' ) ) ? 'simple_product_sec' : ''; ?>">
    <div class="section-inner narrow">
        <?php do_action( 'woocommerce_before_single_product' ); ?>
        <div class="parts">
            <?php get_template_part( 'inc/woo-product/product', 'summery-content' ); ?>
            <?php get_template_part( 'inc/woo-product/product', 'gallery' ); ?>
        </div>
    </div>
</section>

<?php //get_template_part( 'inc/woo-product/related', 'products' ); ?>

<?php get_template_part( 'inc/woo-product/product', 'bottom-content' ); ?>

<?php get_template_part( 'inc/woo-product/product', 'tabs' ); ?>

<script>
  jQuery(document).ready(function ($) {

    // jQuery(".wpulike").on("click", function(e){
    //   e.stopPropagation();
    // });
    // jQuery(".like").on("click", function(e){
    //   e.preventDefault();
    //   jQuery(this).find(".wpulike button").click();
    // });


  }); // end ready
</script>

<?php get_footer(); ?>