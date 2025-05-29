<?php
    // Template Name: Gallery Page
?>
<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>
<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>

<section class="gallery">
	<div class="section-inner">
		<?php 
			$items = get_field('galleries');
			$i=0;
			foreach($items as $item):
			$i++;
			if($i>1) :
		?>
			<br><br><br>
		<?php endif; ?>
			<div class="section-title centered">
				<p class="subtitle"><?=$item["title"]['subtitle'];?></p>
				<p class="title"><?=$item["title"]['title'];?></p>
			</div>
			
			<div class="content">
				<?=$item["title"]["text"];?>
			</div>
			<div class="boxes">
				<?php 
					$images = $item['gallery'];
					foreach($images as $f):
				?>
					<a class="box" href="<?=$f['url']?>" data-fancybox="gallery-<?=$i?>">
						<div class="inner">
							<img src="<?=$f["sizes"]['thumb-gal']?>" alt="<?=$f["alt"]?>" title="<?=$f["title"]?>">
						</div>
					</a>
				<?php endforeach; ?>

			</div>
			
		<?php endforeach; ?>
		
	</div>
</section>

<?php get_footer(); ?>