<?php $td = get_template_directory_uri(); ?>

<?php
	global $page_title;
	global $page_title_h1;
	
	if(!$page_title && isset(get_queried_object() -> name)) $page_title = get_queried_object() -> name;
	if(!$page_title) $page_title = get_the_title();
?>


<section class="top-inner">
	<div class="section-inner">
		<?php if($page_title_h1): ?>
			<h1 class="title"><span><?=$page_title?></span></h1>
		<?php else: ?>
			<p class="title"><span><?=$page_title?></span></p>
		<?php endif; ?>
	</div>
	<div class="wrapper-breadcrumbs">
		<div class="breadcrumbs section-inner">
			<?php
				if ( function_exists('yoast_breadcrumb')) {
					yoast_breadcrumb( '' );
				}
			?>
		</div>
	</div>
</section>