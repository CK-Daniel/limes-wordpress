<?php 
// die('ssss');
	$td = get_template_directory_uri(); 
	$obg_id = get_queried_object_id();
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 

	$tapet_cat_type = get_field( 'tapet_cat_type', 'product_cat_' . $obg_id );


	$tapetim_cats = get_field( 'tapetim_cats', 'option' );
	$topics_cats = get_field( 'topics_cats', 'option' );

	$tapetim_terms = isset($_GET['tapetim_terms']) ? $_GET['tapetim_terms'] : [];
	$topics_terms = isset($_GET['topics_terms']) ? $_GET['topics_terms'] : [];

	$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
	$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';
	$min_price = (int)$min_price;
	$max_price = (int)$max_price;

	$tapetim_terms_ids= array();
	$topics_terms_ids= array();
	
	if ( $tapetim_cats) {
		foreach( $tapetim_cats as $term_id ) {
			$tapetim_terms_ids[] = $term_id;
		}
	}

	if ( $topics_cats) {
		foreach( $topics_cats as $term_id ) {
			$topics_terms_ids[] = $term_id;
		}
	}
	
	 // Query arguments
	 $args = array(
		 'post_type' => 'product',
		 'posts_per_page' => -1,
		 'post_status' => 'publish',
		 'tax_query' => array(
			array(
				'taxonomy' => 'product_cat', 
				'field'    => 'id', 
				'terms'    => $tapetim_terms_ids,
			),
		),
	 );
 
	 // Build the taxonomy queries for both groups
	 if (!empty($tapetim_terms)) {
		 $args['tax_query'][] = array(
			 'taxonomy' => 'product_cat',
			 'field' => 'term_id',
			 'terms' => $tapetim_terms,
			 'operator' => 'IN',
		 );
	 }
 
	 if (!empty($topics_terms)) {
		 $args['tax_query'][] = array(
			 'taxonomy' => 'product_topcics_cat',
			 'field' => 'term_id',
			 'terms' => $topics_terms,
			 'operator' => 'IN',
		 );
	 }

	 if ( $min_price ) {
		$args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => $min_price,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}

	if ( $max_price ) {
		$args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => $max_price,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}
 
	$cat_query = new WP_Query($args);

	$price_range = get_option( 'min_max_product_price' );

	get_header(); 
?>

<?php 
	$cur_term = get_queried_object();
	$child_terms = get_terms( array(
		'taxonomy' => 'product_cat',
		'hide_empty' => false,
		'exclude' => 15,
		'parent' => $cur_term->term_id
	));
	
?>
<?php if ( $tapet_cat_type  ) : ?>
	<section class="tapetimi_sec">
		<div class="section-inner">
			<div class="flex_wrap">
				<div class="form_col">
					<div class="side_filter_title">
						<h3>סינון קטגוריות</h3>
					</div>
					<form id="category-filter" method="GET">
						<?php if ( $tapetim_cats ) : ?>
							<div class="wrap_cat_group">
								<div class="cat_title">
									<h4>לפי קטגוריה</h4>
								</div>
								<?php foreach ( $tapetim_cats as $key => $term_id ) : 
									$term = get_term_by( 'id', $term_id, 'product_cat' );
									$checked = '';
									if ( isset($_GET['tapetim_terms']) && in_array($term->term_id, $_GET['tapetim_terms'])) {
										$checked = ' checked';
									}
								?>
									<div class="wrap_input">
										<input type="checkbox" name="tapetim_terms[]" id="term_<?php echo $term->term_id; ?>" value="<?php echo $term->term_id; ?>" <?php echo $checked; ?> />
										<label for="term_<?php echo $term->term_id; ?>"><?php echo $term->name; ?></label>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if ( $topics_cats ) : ?>
							<div class="wrap_cat_group">
								<div class="cat_title">
									<h4>לפי נושאים</h4>
								</div>
								<?php foreach ( $topics_cats as $key => $term_id ) : 
									$term = get_term_by( 'id', $term_id, 'product_topcics_cat' );
									$checked = '';
									if ( isset($_GET['topics_terms']) && in_array($term->term_id, $_GET['topics_terms'])) {
										$checked = ' checked';
									}
								?>
									<div class="wrap_input">
										<input type="checkbox" name="topics_terms[]" id="term_<?php echo $term->term_id; ?>" value="<?php echo $term->term_id; ?>" <?php echo $checked; ?> />
										<label for="term_<?php echo $term->term_id; ?>"><?php echo $term->name; ?></label>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<div class="wrap_range_price wrap_cat_group">
							<div class="cat_title">
								<h4>סינון לפי מחיר</h4>
							</div>
							<div class="wrapper">
								<div class="slider-wrapper">
									<div id="slider"></div>
								</div> 
								<input type="text" name="min_price" class="from" value="<?php echo ( $min_price ) ? $min_price : floatval( $price_range['min'] ) ; ?>"/>
								<input type="text" name="max_price" class="to" value="<?php echo ( $max_price ) ? $max_price : floatval( $price_range['max'] ) ; ?>"/>

								<input type="hidden" name="min_price_def" class="from" value="<?php echo floatval( $price_range['min'] ) ; ?>"/>
								<input type="hidden" name="max_price_def" class="to" value="<?php echo floatval( $price_range['max'] ) ; ?>"/>

							</div>
						</div>
						<div class="wrap_submit">
							<input type="submit" value="החל סינון">
						</div>
					</form>			
				</div>
				<div class="products_col">
					<?php if ( $cat_query->have_posts() ) : ?>
						<div class="wrap_products boxes">
							<?php while ( $cat_query->have_posts() ) : $cat_query->the_post(); 
								$td = get_template_directory_uri();
								$product = wc_get_product( $id );
								$image = get_the_post_thumbnail_url($product->get_id(), 'thumb-product');
								if(!$image) $image = get_field("general", 'options')['image-placeholder']['sizes']['thumb-product'];	
							?>	
								<div class="box box-product">
									<div class="inner">
										<div class="like">
											<?php echo do_shortcode("[wp_ulike id='$id']"); ?>
										</div>
										<?php if ( $product->is_on_sale() ) : ?>
											<div class="sale">
												<span>במבצע</span>
											</div>
										<?php endif; ?>
										<a class="image" href="<?=get_permalink($product->get_id())?>">
											<img src="<?=$image?>" alt="<?=$product->get_name()?>" title="<?=$product->get_name()?>">
										</a>
										<div class="info">
											<<?=$tag?> class="title"><?=$product->get_name()?></<?=$tag?>>
											<div class="price">
												<?=$product->get_price_html(); ?>
											</div>
											<?php /*
											<a class="button" href="<?=get_permalink($product->get_id())?>">
												<span>צפה במוצר</span>
											</a>*/?>
										</div>
									</div>
									<div class="wrap_btns">
										<a class="button" href="<?=get_permalink($product->get_id())?>">
											<span>צפה במוצר</span>
										</a>
										<a href="#" class="buy-now-button" data-pid="<?php echo $product->get_id(); ?>">לביצוע הזמנה</a>
									</div>
								</div>
							<?php endwhile; wp_reset_query();?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>


<?php if( $child_terms ) : ?>
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
	
				foreach( $terms as $term ):

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

<?php if ( ! $tapet_cat_type  ) : ?>
	<section class="leading-products">
		<div class="section-inner wide">
			<div class="section-title centered">
				<p class="subtitle">בואו להתרשם</p>
				<p class="title">מוצרים בקטגוריה</p>
			</div>
			<?php if( term_description( $cur_term->term_id ) && !$child_terms ) : ?>
				<div class="section-subtitle">
					<?=term_description( $cur_term->term_id );?>
				</div>
				<br><br>
			<?php endif; ?>
			<div class="boxes">
				<?php
					foreach( $products as $product ) {	
						template_product_box($product, "h2");
					}
				?>
			</div>
		</div>
	</section>
<?php endif; ?>
<?php if ( ! $tapet_cat_type  ) : ?>
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