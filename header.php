<?php $td = get_template_directory_uri(); ?>

<!DOCTYPE html>
<html dir="rtl" lang="he-IL">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel='shortcut icon' type='image/x-icon' href='<?=$td?>/images/index/logo/favicon.ico' />


	
	
	<?php wp_head(); ?> 
	
	
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-W4V3F6T');</script>
<!-- End Google Tag Manager -->
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '3176939992634304');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=3176939992634304&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
</head>
<body <?php body_class(); ?>>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W4V3F6T"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.css" />

<?php  
	$cart_url = wc_get_cart_url();
	$logo_mob = get_field('header', 'options')['logo']['mobile']; 
?>
<!-- End Google Tag Manager (noscript) -->
<header>
	<div class="header-inner">
		<div class="cont-flex">
			<div class="logo-wrapper desktop_only">
				<a class="logo" href="<?=get_home_url();?>">
					<?php 
						if(wp_is_mobile()) $f = get_field('header', 'options')['logo']['mobile']; 
						else $f = get_field('header', 'options')['logo']['destop']; 
					?>
					<img src="<?=$f["url"]?>" alt="<?=$f["alt"]?>" title="<?=$f["title"]?>" class="main">
					<img  src="<?=$td?>/images/index/logo/logo-text.png" alt="לימס פינות ישיבה, וילונות ופופים מעוצבים" title="לימס פינות ישיבה, וילונות ופופים מעוצבים" class="text">
				</a>
			</div> 
			<div class="wrap_logo_mob mobile_only">
				<a class="logo_mob" href="<?=get_home_url();?>">
					<img src="<?=$logo_mob["url"]?>" alt="<?=$logo_mob["alt"]?>" title="<?=$logo_mob["title"]?>">
				</a>
				<div class="wrap_icons">
					<a href="<?=get_permalink(68)?>" class="icon like">
						<img src="<?=$td?>/images/icons/heart.svg" alt="מוצרים שאהבתי בסטדיו לימס וילונות ופינות ישיבה">
					</a>
					<a class="cart-contents" href="<?php echo $cart_url; ?>" title="<?php _e( 'צפה בעגלת הקניות' ); ?>" data-count="" rel="nofollow">
						<span><?php echo sprintf ( _n( '%d', '%d', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ); ?></span>
						<img src="<?php echo $td; ?>/images/icons/cart_icon.svg" alt="cart_icon">
					</a>
				</div>
			</div>
			
			<?php
				if(!wp_is_mobile()) {
					$args = array(
						'theme_location'  => 'top-menu',
						'container_class' => 'menu-cont',
						'menu_class'      => 'main-menu',
					);
					wp_nav_menu( $args );
				}
			?>

			<div class="search-and-icons desktop_only">
				<div class="icons">
					<?php 
						$items = get_field("header", "options")['icons'];
						foreach($items as $item):
					?>
						<a href="<?=$item["url"];?>" class="icon" target="_blank">
							<?=$item["icon"];?>
						</a>
					<?php endforeach; ?>
					<a href="<?=get_permalink(68)?>" class="icon like">
						<img src="<?=$td?>/images/icons/heart.svg" alt="מוצרים שאהבתי בסטדיו לימס וילונות ופינות ישיבה">
					</a>
					<a class="cart-contents" href="<?php echo $cart_url; ?>" title="<?php _e( 'צפה בעגלת הקניות' ); ?>" data-count="" rel="nofollow">
						<span><?php echo sprintf ( _n( '%d', '%d', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ); ?></span>
						<img src="<?php echo $td; ?>/images/icons/cart_icon.svg" alt="cart_icon">
					</a>
				</div>
				<a href="tel:<?=get_field('header', 'options')['tel']?>" class="tel"><?=get_field('header', 'options')['tel']?></a>
			</div>

			<div class="ham_socials_wrap mobile_only">
				<?php 
					$items = get_field("header", "options")['icons'];
					foreach($items as $item):
				?>
					<a href="<?=$item["url"];?>" class="icon" target="_blank">
						<?=$item["icon"];?>
					</a>
				<?php endforeach; ?>
				<a href="tel:<?=get_field('header', 'options')['tel']?>" class="tel">
					<img src="<?=$td?>/images/phone_header_icon.svg" alt="מוצרים שאהבתי בסטדיו לימס וילונות ופינות ישיבה">
				</a>
				<div class="ham-button"></div>
			</div>

		</div>
	</div>

	<?php
		if(wp_is_mobile()) {
			$args = array(
				'theme_location'  => 'mobile-menu',
				'container_class' => 'mobile-menu-cont',
				'menu_class'      => 'mobile_menu',
			);
			wp_nav_menu( $args );
		}
	?>
</header>