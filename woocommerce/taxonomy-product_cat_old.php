<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>

<?php 
	$cur_term = get_queried_object();
?>


<?php 
	$child_terms = get_terms( array(
		'taxonomy' => 'product_cat',
		'hide_empty' => false,
		'exclude' => 15,
		'parent' => $cur_term->term_id
	));;
	
	if($child_terms):
?>


<section class="categories-inner">
	<div class="section-inner wide">
		<?php if(term_description($cur_term->term_id)) : ?>
			<div class="section-subtitle">
				<?=term_description($cur_term->term_id);?>
			</div>
			<br><br>
		<?php endif; ?>
		<div class="boxes">
			<?php 
				$terms = $child_terms;
	
				foreach($terms as $term):

				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true ); 
				$image = wp_get_attachment_image_src( $thumbnail_id, 'thumb-product')[0];
				if(!$image) $image = get_field("general", 'options')['image-placeholder']['sizes']['thumb-product'];
			?>
				<a class="box box-category" href="<?=get_term_link($term)?>">
					<div class="inner">
						<div class="image"><img src="<?=$image?>" alt="<?=$term->name?>" title="<?=$term->name?>"></div>
						<div class="info">
							<h2 class="title"><?=$term->name?></h2>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php endif; ?>
<?php 
	
	
	$args = Array(
		'post_type' => 'product',
		'numberposts' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'tax_query' => array(
			array(
				'taxonomy' => 'product_cat',
				'field' => 'term_id', 
				'terms' => $cur_term->term_id
			)
		)
	);
	
	$products = get_posts($args);
?>

<section class="leading-products">
	<div class="section-inner wide">
		<div class="section-title centered">
			<p class="subtitle">בואו להתרשם</p>
			<p class="title">מוצרים בקטגוריה</p>
		</div>
		<?php if(term_description($cur_term->term_id) && !$child_terms) : ?>
			<div class="section-subtitle">
				<?=term_description($cur_term->term_id);?>
			</div>
			<br><br>
		<?php endif; ?>
		
		
		<div class="boxes">
			<?php
				foreach($products as $product) {	
					template_product_box($product, "h2");
				}
			?>
		</div>
	</div>
</section>



<?php if(get_field("more-products", $cur_term)['products']) : ?>
<section class="leading-products">
	<div class="section-inner wide">
		<div class="section-title centered">
			<p class="subtitle">בואו להתרשם</p>
			<p class="title">מוצרים מובילים</p>
		</div>

		<div class="boxes">
			<?php
				$products = get_field("more-products", $cur_term)['products'];
				foreach($products as $product) {	
					template_product_box($product, "h2");
				}
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if(get_field("content-bottom", $cur_term)) : ?>
<section class="info">
	<div class="section-inner">
		<div class="content">
			<?=get_field("content-bottom", $cur_term);?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>