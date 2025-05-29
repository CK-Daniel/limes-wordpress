<?php
    // Template Name: Institutions Page
?>
<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>

<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>
<section class="institutions">
	<div class="section-inner">
		<?php 
			$items = get_field('section-solutions')['boxes'];
			foreach($items as $item):
		?>
			<div class="parts">
				<div class="part">
					<div class="section-title">
						<p class="subtitle"><?=$item["title"]['subtitle'];?></p>
						<p class="title"><?=$item["title"]['title'];?></p>
					</div>
					<div class="content">
						<?=$item["content"];?>
					</div>
				</div>
				<div class="part">
					<div class="image">
						<?php $f = $item['image']; ?> 
						<img src="<?=$f["url"]?>" alt="<?=$f["alt"]?>" title="<?=$f["title"]?>">
					</div>
				</div>
			</div>
		<?php endforeach; ?>

	</div>
</section>

<section class="customers">
	<div class="section-inner">
		<div class="section-title">
			<p class="subtitle"><?=get_field('section-tabs')['title']['subtitle']?></p>
			<p class="title"><?=get_field('section-tabs')['title']['title']?></p>
		</div>
		<div class="content">
			<?=get_field('section-tabs')['text']?>
		</div>
		<?php $items = get_field('section-tabs')['tabs']; ?>
		<div class="tabs-menu <?php if(wp_is_mobile()) echo "mobile" ?>">
			<div class="title-mobile has-arrow">
				<span><?=$items[0]['title']?></span>
			</div>
			<div class="items">
				<?php 
					$i=0;
					foreach($items as $item):
					$i++;
				?>
					<div class="item <?php if($i==1) echo 'active' ?>">
						<span><?=$item['title']?></span>
					</div>
				<?php endforeach; ?>
			 </div>
		</div>

		<div class="tabs">
			<?php 
				$i=0;
				foreach($items as $item):
				$logos = $item['logos'];
				$i++;
			?>
				<div class="tab <?php if($i==1) echo 'active'; ?>">
					<div class="content">
						<?php if ( $logos ) : ?>
							<div class="wrap_logos">
								<?php foreach ( $logos as $logo ) :  
									$logo_item = reset($logo);
									$logo_title = $logo['title'];
									?>
									<div class="wrap_logo">
										<div class="inner">
											<img src="<?php echo $logo_item['sizes']['insu_logo']; ?>" alt="<?php echo $logo_item['alt']; ?>">
											<?php if ( $logo_title ) : ?>
												<span><?php echo $logo_title; ?></span>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?//=$item['content'] ?>
					</div>
			</div>
			<?php endforeach; ?>
			
		</div>
	</div>
</section>   

<section class="gallery">
	<div class="section-inner">
		
		<?php 
			$items = get_field('section-galleries')['galleries'];
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


<section class="questions">
	<div class="section-inner">
		<div class="section-title centered">
			<p class="subtitle"><?=get_field('section-questions')['title']['subtitle']?></p>
			<p class="title"><?=get_field('section-questions')['title']['title']?></p>
		</div>
		<div class="parts">
			<div class="part">
				<div class="accordion">
					<?php
						$i = 0;
						$items = get_field("section-questions")["questions"];
						$half = round(sizeof($items)/2);
						foreach($items as $item):
						$i ++;
					?>
						<h2 class="h3"><?=$item["question"]?></h2>
						<div class="content">
							<?=$item["answer"]?>
						</div>
						
						<?php if($i == $half) :?>
							</div>
							</div>
							<div class="part">
								<div class="accordion">
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				
			</div>
		</div>
	</div>
</section>

<script>
	$(document).ready(function ($) {
		$( ".accordion" ).accordion({header: ".h3", collapsible: true, active: false, autoHeight: false});
		
		$(".tabs-menu .item").on("click", function(){
			$(".tabs-menu .item").removeClass("active");
			$(this).addClass('active');
			var index = $(".tabs-menu .item").index(this);
			var text = $(this).find('span').text();
			$(".tabs-menu .title-mobile span").text(text);
			$(".tabs-menu.mobile .items").slideUp();
			
			$(".customers .tabs").animate({opacity:0}, 500, function(){
				$(".customers .tabs .tab").hide();
				$(".customers .tabs .tab").eq(index).show();
				$(".customers .tabs").animate({opacity:1}, 500);
			});
			
			
		});
		
		$(".tabs-menu .title-mobile").on("click", function(){
			$(".tabs-menu .items").slideToggle();
		});
	});
</script>
<?php get_footer(); ?>