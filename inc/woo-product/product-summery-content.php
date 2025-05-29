<?php  
    global $product;
    $id  = $product->get_id();
?>

<div class="part">
    <?php //if ( ! wp_is_mobile() ) : ?>
    <div class="part-top">
        <div class="panel-top">
            <h1 class="product-title"><?php the_title(); ?></h1>
        </div>
    </div>
    <?php //endif; ?>

    <div class="pro_short_desc ">
        <?php echo $product->get_short_description(); ?>
    </div>

    <?php if ( ! $product->is_type( 'variable' ) ) : ?>
    <!-- Show price for simple products -->
    <div class="price simple-product-price"><?php echo $product->get_price_html(); ?></div>
    <?php endif; ?>

    <span id="base_price" data-base-price="<?php echo esc_attr( $product->get_price() ); ?>"
        style="display:none;"></span>

    <?php 
    /**
     * Output the standard WooCommerce add‑to‑cart form.
     * This function automatically handles both variable (with standard variation selects)
     * and simple products.
     */
    woocommerce_template_single_add_to_cart(); 
    ?>

    <?php  
        $general_f = get_field( 'general' , 'option' );
        $whatsapp_num = $general_f['whatsapp_num'];
        $whatsapp_num_text = $general_f['whatsapp_num_text'];
        $product_type_dimensions = get_field( 'product_type_dimensions', $product->get_id() );
    ?>
    <?php if ( $product_type_dimensions === "tapet" ) : ?>
    <div class="tapet_price">
        <span>מחיר: </span>
        <div class="price"><?php echo $product->get_price_html(); ?></div>
    </div>
    <?php endif; ?>
    <div class="prod_whatsapp">
        <a aria-label="Chat on WhatsApp" href="https://wa.me/<?php echo $whatsapp_num; ?>" target="_blank">
            <img src="<?php echo get_template_directory_uri() ?>/images/whatsapp_icon.png" alt="whatsapp_icon">
            <span><?php echo $whatsapp_num_text; ?></span>
        </a>
    </div>

    <?php /*
    <div class="wrapper-form">
    <p class="title">לקבלת פרטים נוספים</p>
    <?php echo do_shortcode('[contact-form-7 id="13" title="טופס בעמוד מוצר"]'); ?>
</div>
*/ ?>

</div>