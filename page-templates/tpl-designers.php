<?php
    // Template Name: Designers

    $des_top_text = get_field( 'designers_top_text' );
    $des_main_img = get_field( 'designers_main_img' );
    $designers_ids = get_field( 'designers_ids' );
    $designers_loop = limes_get_all_designers( $designers_ids );
    $des_team_title = get_field( 'designers_team_title' ); 
    $des_main_title = get_field( 'designers_main_title' );

?>
<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>
<?php if ( $des_top_text ) : ?>
<div class="designers_top_sec">
    <div class="main_desc">
        <?php echo $des_top_text; ?>
    </div>
</div>
<?php endif; ?>
<?php if ( $des_main_img ) : ?>
<div class="designers_main_img">
    <img src="<?php echo $des_main_img['url']; ?>" alt="<?php echo $des_main_img['alt']; ?>">
</div>
<?php endif; ?>
<section class="designers_sec">
    <div class="designers_title">
        <div class="inner">
            <?php if ( $des_team_title ) : ?>
            <span><?php echo $des_team_title; ?></span>
            <?php endif; ?>
            <?php if ( $des_main_title ) : ?>
            <h2><?php echo $des_main_title; ?></h2>
            <?php endif; ?>
        </div>
    </div>
    <div class="section-inner">
        <div class="des_flex_wrap">
            <?php
                while ( $designers_loop->have_posts() ) :
                    $designers_loop->the_post();
                    $post_title    = get_the_title();
                    $designer_desc = get_field( 'designer_desc' );
                    $designer_role = get_field( 'designer_role' );
                    $designer_link = get_field( 'designer_link' );
                    $designer_insta = get_field( 'designer_insta' );
                    $designer_website = get_field( 'designer_website' );
                    $designer_face = get_field( 'designer_face' );
                    $img_url       = get_the_post_thumbnail_url( $post->ID, 'thumb-designer' );
                    $post_kind     = get_field( 'post_kind' );
                    ?>
            <div class="wrap_des_box">
                <div class="wrap_img">
                    <img src="<?php echo $img_url; ?>" alt="<?php echo $post_title['alt']; ?>">
                </div>
                <div class="content_item">
                    <?php if ( $designer_desc ) : ?>
                    <div class="wrap_desc">
                        <?php echo $designer_desc; ?>
                    </div>
                    <?php endif; ?>
                    <div class="des_title">
                        <?php echo esc_html( $post_title ); ?>
                    </div>
                    <div class="des_role">
                        <?php echo esc_html( $designer_role ); ?>
                    </div>
                    <div class="wrap_links">
                        <?php if ( $designer_insta ) : ?>
                        <div class="link">
                            <a href="<?php echo $designer_insta['url']; ?>"
                                title="<?php echo $designer_insta['title']; ?>"
                                aria-label="<?php echo $designer_insta['title']; ?>"
                                target="<?php echo $designer_insta['target']; ?>">
                                <img src="<?php echo $td; ?>/images/icons/insta_icon.png" alt="insta">
                                <span><?php echo $designer_insta['title']; ?></span>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if ( $designer_website ) : ?>
                        <div class="link">
                            <a href="<?php echo $designer_website['url']; ?>"
                                title="<?php echo $designer_website['title']; ?>"
                                aria-label="<?php echo $designer_website['title']; ?>"
                                target="<?php echo $designer_insta['target']; ?>">
                                <img src="<?php echo $td; ?>/images/icons/website_icon.png" alt="website_icon">
                                <span><?php echo $designer_website['title']; ?></span>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if ( $designer_face ) : ?>
                        <div class="link">
                            <a href="<?php echo $designer_face['url']; ?>"
                                title="<?php echo $designer_face['title']; ?>"
                                aria-label="<?php echo $designer_face['title']; ?>"
                                target="<?php echo $designer_face['target']; ?>">
                                <img src="<?php echo $td; ?>/images/icons/face_icon.png" alt="face_icon">
                                <span><?php echo $designer_face['title']; ?></span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ( $designer_link ) : ?>
                    <div class="link_custom">
                        <a href="<?php echo $designer_link['url']; ?>" title="<?php echo $designer_link['title']; ?>"
                            aria-label="<?php echo $designer_link['title']; ?>"
                            target="<?php echo $designer_insta['target']; ?>">
                            <?php echo $designer_link['title']; ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
                endwhile;
                wp_reset_postdata();
            ?>
        </div>
    </div>
</section>