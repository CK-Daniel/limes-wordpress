<?php  
    global $product;
?>



<div class="part gallery_col">
    <?php /* if ( wp_is_mobile() ) : ?>
    <div class="part-top">
        <div class="panel-top">
            <h1 class="product-title"><?php the_title(); ?></h1>
            <div class="like">
                <?php //echo do_shortcode("[wp_ulike id='$id']"); ?>
                <span class="caption">הוספה למועדפים</span>
            </div>
        </div>
        <div class="price"><?php echo $product->get_price_html(); ?></div>
    </div>
    <?php endif; */ ?>

    <?php 
        $attachment_ids = $product->get_gallery_image_ids(); 
        $attachment_ids[] = get_post_thumbnail_id( $post );
    ?>
    <div class="sliders">
        <div class="swiper-container gallery-top">
            <div class="swiper-wrapper">
                <?php foreach ( $attachment_ids as $attachment_id ) :
                    $image_thumb_data = wp_get_attachment_image_src( $attachment_id, 'full'  );
                ?>
                <a class="swiper-slide" href="<?php echo $image_thumb_data[0] ?>" data-fancybox="gallery"
                    image-id="<?php echo esc_attr( $attachment_id ); ?>">
                    <div class="image-wrapper">
                        <img src="<?php echo esc_url( $image_thumb_data[0] ); ?>"
                            alt="<?php echo esc_attr( $post->post_title ); ?>">
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    <?php if ( sizeof( $attachment_ids ) > 1 ) : ?>
    <?php endif; ?>
    var galleryTop = new Swiper('.gallery-top', {
        spaceBetween: 0,
        slidesPerView: 1,
        loop: true,
        direction: 'horizontal',
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true
        },
        <?php if ( sizeof( $attachment_ids ) > 1 ) : ?>
        // thumbs: { swiper: galleryThumbs },
        <?php endif; ?>
        breakpoints: {
            0: {
                direction: 'horizontal'
            },
            950: {
                direction: 'horizontal'
            }
        }
    });
});
</script>