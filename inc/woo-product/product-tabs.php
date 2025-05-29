<?php  
    global $product;
    $prod_tabs = get_field( 'prod_tabs', $product->get_id() );
?>
<?php if ( $prod_tabs ) : ?>
<section class="product_tabs_sec">
	<div class="section-inner">
        <div class="wrap_product_tabs">
            <div class="tabs">
                <ul id="tabs-nav">
                    <?php foreach ( $prod_tabs as $key => $tab ) : $key++; ?>
                        <li><a href="#tab<?php echo $key; ?>"><?php echo $tab['tab_title'] ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <div id="tabs-content">
                    <?php foreach ( $prod_tabs as $key => $tab ) : $key++; 
                        $acc_rep = $tab['acc_rep'];
                    ?>
                        <div id="tab<?php echo $key; ?>" class="tab-content">
                            <div class="product_acc_tab">
                                <div class="accordion-container">
                                    <?php foreach ( $acc_rep as $item ) : ?>
                                        <div class="set">
                                            <a href="#"><?php echo $item['acc_title'] ?> 
                                                <div class="wrap_icon">
                                                    <img class="plus_icon" src="<?php echo get_template_directory_uri() ?>/images/plus_icon.png" alt="plus_icon">   
                                                    <img class="minus_icon" src="<?php echo get_template_directory_uri() ?>/images/minus_icon.png" alt="minus_icon">   
                                                </div>
                                            </a>
                                            <div class="content">
                                                <?php echo $item['acc_con'] ?> 
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
 </section>
<?php endif; ?>