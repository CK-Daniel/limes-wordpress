<?php
    // Template Name: Contact Page
?>
<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>
 <section class="contact">
	<div class="sections">
		<div class="section text">
			<div class="decor-contact">
				<img src="<?=$td?>/images/inner/decor/decor-contact.png" alt="">
			</div>
			<div class="wrapper">
				<p class="title">פרטי התקשרות</p>
				<div class="content">
					<?=get_field('contact-details')?>
				</div>
				<br>
				<p class="title">שעות פעילות</p>
				<div class="content">
					<?=get_field('hours')?>
				</div>
			</div>
		</div>
		<div class="section map">
			<div class="cover">
				<img src="<?=$td?>/images/inner/cover.png" alt="">
			</div>
		   <?=get_field('google-map-code')?>
		</div>
	</div>
</section>
<?php get_footer(); ?>