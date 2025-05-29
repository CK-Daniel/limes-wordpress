<?php  
    global $product;
    $pro_bottom_con_title = get_field( 'pro_bottom_con_title', $product->get_id() );
    $pro_bottom_con = get_field( 'pro_bottom_con', $product->get_id() );
?>
<section class="product_bottom_content">
	<div class="section-inner">
        <?php if ( $pro_bottom_con_title  ) : ?>
            <div class="wrap_title">
                <h3><?php echo $pro_bottom_con_title; ?></h3>
            </div>
        <?php endif; ?>
        <?php if ( $pro_bottom_con  ) : ?>
            <div class="wrap_content">
                <?php echo $pro_bottom_con; ?>
            </div>
        <?php endif; ?>
    </div>
 </section>