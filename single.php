<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title;
	$page_title = "מידע מקצועי";
	get_template_part( 'template-parts/top-inner' ); 
?>

<section class="post">
	<div class="section-inner">
		<div class="img-main">
			<?php $image = get_the_post_thumbnail_url($post, 'big-post'); ?>
			
			<?php if(!wp_is_mobile()) : ?>
				<img src="<?=$image?>" alt="<?=$post -> post_title?>" title="<?=$post -> post_title?>" class="main">
			<?php endif; ?>
		</div>
		
		<div class="content">
			<h1 class="title"><?php the_title();?></h1>
			<?php if(wp_is_mobile()) : ?>
				<div class="img-main mobile">
					<img src="<?=$image?>" alt="<?=$post -> post_title?>" title="<?=$post -> post_title?>" class="main">
				</div>
			<?php endif; ?>
			<?php the_content(); ?>
		</div>
	</div>
</section>


<section class="blog more">
	<div class="section-inner">
		<div class="section-title centered">
			<p class="subtitle">מחפשים עוד?</p>
			<h3 class="title">מאמרים נוספים</h3>
		</div>
		<div class="boxes">
			<?php 
				$args = array(
					'post_type'             => 'post',
					'posts_per_page'        => 3,
					'post_status'           => 'publish',
					'ignore_sticky_posts'   => 1,
					'orderby'   => 'rand',
					'post__not_in'   => array( get_the_ID() ),
					'fields' => 'ids',
					'no_found_rows' => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				);

				$posts = get_posts($args);
				foreach($posts as $mpost) {
					template_post_box($mpost);
				}
			?>
		</div>
	</div>
</section>
	
<?php get_footer(); ?>