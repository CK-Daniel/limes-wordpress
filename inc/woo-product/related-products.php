<?php 
global $product;
// Cross‑Sell / Related Products Section
$cross_sell_ids = $product->get_upsell_ids();
if ( ! $cross_sell_ids ) {
  $cross_sell_ids = $product->get_cross_sell_ids();
}
if ( ! $cross_sell_ids ) {
  $cross_sell_ids = wc_get_related_products( $product->get_id() );
}
if ( sizeof( $cross_sell_ids ) < 3 ) {
  // get_posts returns an array of WP_Post objects
  $cross_sell_ids = get_posts( array(
    'post_type'              => 'product',
    'orderby'                => 'rand',
    'order'                  => 'ASC',
    'posts_per_page'         => 3,
    'no_found_rows'          => true,
    'update_post_term_cache' => false,
    'update_post_meta_cache' => false,
    'post__not_in'           => array( get_the_ID() )
  ) );
}
if ( sizeof( $cross_sell_ids ) > 0 ):
?>

<section class="leading-products">
  <div class="section-inner wide">
    <div class="section-title centered">
      <p class="subtitle">מחפשים עוד?</p>
      <h2 class="title">מוצרים נוספים</h2>
    </div>
    <div class="boxes">
      <?php 
      foreach ( $cross_sell_ids as $item ) {
        // If $item is a WP_Post object, extract the ID; otherwise assume it's already an ID.
        $product_id = is_object( $item ) ? $item->ID : $item;
        template_product_box( $product_id );
      }
      ?>
    </div>
  </div>
</section>

<?php endif; ?>
