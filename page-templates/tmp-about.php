<?php
    // Template Name: About Page
?>
<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>

<section class="about inner">
	<div class="section-inner">
		<div class="parts ai-center">
			<div class="part">
				<div class="section-title">
					<p class="subtitle"><?=get_field('section-about')['title']['subtitle']?></p>
					<p class="title"><?=get_field('section-about')['title']['title']?></p>
					<div class="decor">
						<img src="<?=$td?>/images/index/limes.png" alt="">
					</div>
				</div>
				
				<div class="content">
					<?=get_field('section-about')['text'];?>
				</div>
			</div>
			<div class="part">
				<div class="swiper-container slider-about">
					<div class="swiper-wrapper">
						<?php 
							$items = get_field('section-about')['slider'];
							foreach($items as $f):
						?>
							<div class="swiper-slide">
								<img src="<?=$f["url"]?>" alt="<?=$f["alt"]?>" title="<?=$f["title"]?>">
							</div>
						<?php endforeach; ?>		
					</div>
					<div class="swiper-pagination pagination-about"></div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="advantages">
	<div class="section-inner">
		<div class="items">
			<?php 
				$items = get_field('section-advantages')['advantages'];
				foreach($items as $item):
			?>
				<div class="item">
					<div class="icon">
						<?php $f = $item['icon']?> 
						<img src="<?=$f["url"]?>" alt="<?=$f["alt"]?>" title="<?=$f["title"]?>">
					</div>
					<div class="text">
						<span><?=$item["text"];?></span>
					</div>
				</div>
			<?php endforeach; ?>
			
		</div>
	</div>
</section>

<section class="testimonials">
	<div class="section-inner">
		<div class="section-title centered">
			<p class="subtitle"><?=get_field('section-testimonials')['title']['subtitle']?></p>
			<p class="title"><?=get_field('section-testimonials')['title']['title']?></p>
		</div>

		<div class="boxes">
			<?php 
				$items = get_field('section-testimonials')['testimonials'];
				foreach($items as $item):
			?>
				<?php if($item['image']) : ?>
					<div class="box">
						<div class="inner">
							<div class="image">
								<?php $f = $item['image']; ?> 
								<img src="<?=$f["url"]?>" alt="<?=$f["alt"]?>" title="<?=$f["title"]?>">
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="box">
						<div class="inner">
							<div class="text">
								<p class="title"><?=$item["title"];?></p>
								<div class="content">
									<?=$item["text"];?>
								</div>
								<div class="quote">
									<img src="<?=$td?>/images/icons/quote.svg" alt="">
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			

		</div>

	</div>
</section>

<section class="contact-bottom extended">
	<div class="extended">
		<div class="section-inner">
			<div class="parts">
				<div class="part">
					<div class="cover"><img src="<?=$td?>/images/inner/cover-arch.png" alt=""></div>
					<p class="title">
						<span><?=get_field('section-designers')['title']['subtitle']?></span>
						<br>
						<strong><?=get_field('section-designers')['title']['title']?></strong>
					</p>

					<div class="content">
						<?=get_field('section-designers')['content']?>
					</div>
				</div>
				<div class="part">
				</div>
			</div>
		</div>
	</div>
	<div class="section-inner">
		<p class="title">
			<span>לקבלת ייעוץ</span>
			<strong>נשמח שתצרו עמנו קשר</strong>
		</p>
		<?=do_shortcode('[contact-form-7 id="70" title="טופס footer"]');?>
	</div>
</section>

<script>
	$(document).ready(function ($) {


		var mySwiper = new Swiper('.slider-about', {
			slidesPerView: 1,
			spaceBetween: 0,
			loop: true,

			autoplay: {
				delay: 3000,
				disableOnInteraction: false
			},
			speed: 1000,
			pagination: {
				el: '.pagination-about',
				type: 'bullets',
				clickable: true,
			},
		});
		

	});
</script>

<?php get_footer(); ?>