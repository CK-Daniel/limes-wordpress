<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>
<?php
	$id = wc_get_page_id( 'shop' );
	setup_postdata($id);
	global $post;
	$post = get_post($id);
?>
<?php 
	global $page_title;
	$page_title = 'קטלוג מוצרים';
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>


<section class="categories index">
	<div class="decor decor-1">
		<img src="<?=$td?>/images/inner/decor/d1.png" alt="">
	</div>
	<div class="decor decor-2">
		<img src="<?=$td?>/images/inner/decor/d2.png" alt="">
	</div>
	<div class="section-inner">
		<div class="section-subtitle">
			<?php the_content();?>
		</div>
		<br><br>
		<div class="container">
			<?php 
				$terms = get_field('categories');
				$i = 0;
				foreach($terms as $term):
				$i++;
				$class= "";
				if($i == 1) $class .= " full";
				elseif($term->term_id == 19) $class .= " sale";
				else $class .= " cat";
				
				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true ); 
				$image = wp_get_attachment_image_src( $thumbnail_id, 'full')[0];
				if(!$image) $image = get_field("general", 'options')['image-placeholder']['url'];
			?>
				<a class="box <?=$class?>" href="<?=get_term_link($term)?>">
					<div class="inner">
						<div class="image"><img src="<?=$image?>" alt="<?=$term->name?>" title="<?=$term->name?>"></div>
						<div class="info">
							<h2 class="text"><?=$term->name?></h2>
							<?php if($term->term_id == 19) : ?>
								<div class="sale-text">
									<span><?= get_field("text-short", $term); ?></span>
								</div>
							<?php else : ?>
								<div class="button button-underline-hover">
									<span>לצפייה
									ב<?=$term->name?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if(get_field("leading-products")) : ?>
<section class="leading-products">
	<div class="section-inner wide">
		<div class="section-title centered">
			<p class="subtitle">בואו להתרשם</p>
			<p class="title">מוצרים מובילים</p>
		</div>
		
		<div class="boxes">
			<?php 
				$products = get_field("leading-products");
				foreach($products as $product) {
					template_product_box($product);
				}
			?>
		</div>
	</div>
</section>
<?php endif; ?>
<?php get_footer(); ?>