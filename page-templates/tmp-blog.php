<?php
    // Template Name: Blog Page
?>
<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>
<section class="blog">
	<div class="section-inner">
		<div class="section-subtitle">
			<?php the_content();?>
		</div>

		<div class="boxes">
			<?php
				$tax_query = array();
				$cur_term_id = get_field("cat");
				if($cur_term_id) {
					$tax_query = array(
						array(
							'taxonomy' => 'category',
							'field' => 'term_id', 
							'terms' => $cur_term_id
							)
					);
				}
				
				$args = array(
					'post_type'             => 'post',
					'posts_per_page'        => -1,
					'post_status'           => 'publish',
					'ignore_sticky_posts'   => 1,
					'tax_query' => $tax_query,
					'fields' => 'ids',
					'no_found_rows' => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				);
				$items = get_posts($args);
				foreach($items as $item) {
					template_post_box($item, 'h2');
				}
			?>
		</div>
	</div>
</section>
<?php get_footer(); ?>