<?php
    // Template Name: Wishlist Page
?>

<?php $td = get_template_directory_uri(); ?>
<?php get_header(); ?>
<?php 
	global $page_title_h1;
	$page_title_h1 = true;
	get_template_part( 'template-parts/top-inner' ); 
?>
<section class="leading-products">
	<div class="section-inner">
		<div class="wishlist-top">
			<div class="text">
				<?=the_content();?>
			</div>

			<div class="buttons">

				<?=do_shortcode('[contact-form-7 id="248" title="שליחת מועדפים למייל"]');?>

			</div>
		</div>
		<br><br>
		
		<div class="boxes">
			<?php
				$args = array(
					'post_type'             => 'product',
					'posts_per_page'		=> -1,
					'post_status'           => 'publish',
					'ignore_sticky_posts'   => 1,
				);
				
				$query = new WP_Query($args);

				global $wp_ulike_class;
				
				while ( $query -> have_posts() ) {
					$query -> the_post();
					global $post;
				
					$post_ID       = $post->ID;
					$defaults      = array(
						"id"            => $post_ID,
						"table"         => 'ulike',	
						"column"        => 'post_id',			
					);
					$liked = $wp_ulike_class->wp_get_ulike_ex($defaults);
										
					if($liked != "like") continue;
					$not_empty = true;
					
					template_product_box($post->ID);
				}	
				wp_reset_postdata();
				
				if(!isset($not_empty)) echo "<p class='no-products'>לא נמצאו מוצרים</p>"; 
			?>
		</div>
	</div>
</section>

<script>
	$(document).ready(function ($) {
		buildList();
		
		$(".wp_ulike_btn").on("click", function(){
			console.log("clciked");
			var box = $(this).closest(".box-product");
			console.log(box);
			box.fadeOut();
		});
		
	});
	
	function buildList() {
		var list = "";
		var ids = "";
		$(".box-product").each(function(){
			
			//var mark = $(this).find(".check-mark");
			//if($(mark).hasClass("active")) {
			if(true) {
				var link = $(this).find("a").attr("href");
				list += $(this).find(".title").text() + " - " + link;
				list += "\n";
				
				ids += $(this).attr("product-id") + ",";
			}
			else {
				//console.log("no")
			}
		});
		
		console.log(ids);
		$("input[name='add-more-to-cart']").val(ids);
		$("input[name='list']").val(list);
	}
</script>
<?php get_footer(); ?>