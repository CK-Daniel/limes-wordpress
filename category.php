<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>
<?php 
	$cur_term = get_queried_object();
	require get_template_directory() . '/inc/posts.php';
?>
<?php get_footer(); ?>