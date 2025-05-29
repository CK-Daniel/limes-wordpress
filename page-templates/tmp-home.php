<?php
    // Template Name: Home Page
?>
<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>
<section class="top-index">
	<div class="swiper-container slider-top">
		<div class="swiper-wrapper">
			<?php 
				$items = get_field('section-top')['slider'];
				foreach($items as $item):
			?>
				<div class="swiper-slide">
					<?php 
						$f = $item['image']; 
						if(wp_is_mobile() && $item['image-mobile']) $f = $item['image-mobile'];
					?> 
					<img src="<?=$f["url"]?>" alt="<?=$f["alt"]?>" title="<?=$f["title"]?>">
				</div>
			<?php endforeach; ?>
		</div>

	</div>
	<div class="wrapper-text">
		<h1 class="title"><?=get_field('section-top')['content']['line-1']?></h1>
		<a href="<?=get_field('section-top')['button']['url']?>" class="button">
			<span><?=get_field('section-top')['button']['text']?></span>
			<img src="<?=$td?>/images/icons/arrow-left.svg" alt="לימס פינות ישיבה, וילונות ופופים מעוצבים">
		</a>
	</div>


</section>

<section class="categories index">
	<div class="section-inner">
		<div class="container">
			<?php 
				$terms = get_field('section-cats')['cats'];
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

<section class="leading-products">
	<div class="section-inner wide">
		<div class="section-title centered">
			<p class="subtitle"><?=get_field('section-products')['title']['subtitle']?></p>
			<h2 class="title"><?=get_field('section-products')['title']['title']?></h2>
		</div>
		
		<div class="cats-menu">
			<div class="items">
				<?php 
					$items = get_field('section-products')['groups'];
					$i = 0;
					foreach($items as $item):
					$i++;
				?>
					<div class="item <?php if($i==1) echo 'active' ?>">
						<span><?=$item["title"];?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="tabs">
			<?php 
				$items = get_field('section-products')['groups'];
				$i = 0;
				foreach($items as $item):
				$i++;
			?>
			<div class="boxes tab" <?php if($i!=1) echo 'style="display:none"' ?>>
				<?php 
					$products = $item['products'];
					foreach($products as $product) {
						template_product_box($product, "h3");
					}
				?>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="centered">
			<a href="<?=get_field('section-products')['button']['url']?>" class="button-underline-hover"><span><?=get_field('section-products')['button']['text']?></span></a>
		</div>
	</div>
</section>

<section class="about">
	<div class="section-inner">
		<div class="parts ai-center">
			<div class="part">
				<div class="section-title">
					<p class="subtitle"><?=get_field('section-about')['title']['subtitle']?></p>
					<h2 class="title"><?=get_field('section-about')['title']['title']?></h2>
					<div class="decor">
						<img src="<?=$td?>/images/index/limes.png" alt="חנות וילונות, פינות ישיבה ופופים - לימס">
					</div>
				</div>
				
				<div class="content">
					<?=get_field('section-about')['text'];?>
				</div>

				<a href="<?=get_field('section-about')['button']['url']?>" class="button button-underline-hover"><span><?=get_field('section-about')['button']['text']?></span></a>
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

<section class="testimonials">
	<div class="section-inner">
		<div class="section-title centered">
			<p class="subtitle"><?=get_field('section-testimonials')['title']['subtitle']?></p>
			<h2 class="title"><?=get_field('section-testimonials')['title']['title']?></h2>
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
									<img src="<?=$td?>/images/icons/quote.svg" alt="וילונות ופינות ישיבה">
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			

		</div>

		<div class="centered">
			<a href="<?=get_field('section-testimonials')['button']['url']?>" class="button button-underline-hover"><span><?=get_field('section-testimonials')['button']['text']?></span></a>
		</div>
	</div>
</section>
<script>
	$(document).ready(function ($) {
		var mySwiper = new Swiper('.slider-top', {
			slidesPerView: 1,
			spaceBetween: 0,
			loop: true,
			effect: 'fade',

			fadeEffect: {
				crossFade: true
			},
			autoplay: {
				delay: 3000,
				disableOnInteraction: false
			},
			speed: 1000,
		});

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
		
		$(".leading-products .cats-menu .item").on("click", function(){
			$(".leading-products .cats-menu .item").removeClass("active");
			$(this).addClass('active');
			var index = $(".leading-products .cats-menu .item").index(this);
			
			$(".leading-products .tabs").animate({opacity:0}, 500, function(){
				$(".leading-products .tabs .tab").hide();
				$(".leading-products .tabs .tab").eq(index).show();
				$(".leading-products .tabs").animate({opacity:1}, 500);
			});
		});
	});
</script>
<?php get_footer(); ?>