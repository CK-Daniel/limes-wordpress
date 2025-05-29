<?php  
    global $product;
?>



<div class="part gallery_col">
    <?php if ( wp_is_mobile() ) : ?>
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
    <?php endif; ?>

    <?php 
    $attachment_ids = $product->get_gallery_image_ids(); 
    if ( $product->is_type( 'variable' ) ) {
        $variations = $product->get_available_variations();    
        $default_attributes = $product->get_default_attributes();
        $attachment_ids = $product->get_gallery_image_ids(); 
        if ( isset($variations) ) {
            foreach ( $variations as $var ) {
                if ( $var['image_id'] && ! in_array( $var['image_id'], $attachment_ids ) ) {
                $attachment_ids[] = $var['image_id']; 
                }
            }
        }
    }

    $attachment_ids[] = get_post_thumbnail_id( $post );
    ?>
    <div class="sliders">
        <div class="swiper-container gallery-top">
        <div class="swiper-wrapper">
            <?php foreach ( $attachment_ids as $attachment_id ) :
                $image_thumb_data = wp_get_attachment_image_src( $attachment_id, 'big-product-gal' );
                $image_thumb = ($image_thumb_data && isset($image_thumb_data[0])) ? $image_thumb_data[0] : '';
                $image_full_data  = wp_get_attachment_image_src( $attachment_id, '' );
                $image_full = ($image_full_data && isset($image_full_data[0])) ? $image_full_data[0] : '';
            ?>
            <a class="swiper-slide" href="<?php echo esc_url( $image_full ); ?>" data-fancybox="gallery" image-id="<?php echo esc_attr( $attachment_id ); ?>">
                <div class="image-wrapper">
                    <img src="<?php echo esc_url( $image_thumb ); ?>" alt="<?php echo esc_attr( $post->post_title ); ?>">
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        </div>
        <?php if ( sizeof( $attachment_ids ) > 1 ) : ?>
        <div class="swiper-container gallery-thumbs">
            <div class="swiper-wrapper">
                <?php foreach ( $attachment_ids as $attachment_id ) :
                    $image_thumb_data = wp_get_attachment_image_src( $attachment_id, 'thumb-product-gal' );
                    $image_thumb = ($image_thumb_data && isset($image_thumb_data[0])) ? $image_thumb_data[0] : '';
                ?>
                <div class="swiper-slide" image-id="<?php echo esc_attr( $attachment_id ); ?>">
                    <div class="image-wrapper">
                        <img src="<?php echo esc_url( $image_thumb ); ?>" alt="<?php echo esc_attr( $post->post_title ); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
    <?php if ( sizeof( $attachment_ids ) > 1 ) : ?>
        var galleryThumbs = new Swiper('.gallery-thumbs', {
        spaceBetween: 10,
        slidesPerView: 4,
        direction: 'horizontal',
        slideToClickedSlide: true,
            breakpoints: {
                0: { slidesPerView: 3 },
                950: { slidesPerView: 4 },
                1200: { slidesPerView: 4 }
            }
        });
        <?php endif; ?>
        var galleryTop = new Swiper('.gallery-top', {
        spaceBetween: 0,
        slidesPerView: 1,
        direction: 'horizontal',
        <?php if ( sizeof( $attachment_ids ) > 1 ) : ?>
        thumbs: { swiper: galleryThumbs },
        <?php endif; ?>
            breakpoints: {
                0: { direction: 'horizontal' },
                950: { direction: 'horizontal' }
            }
        });
    });
</script>


