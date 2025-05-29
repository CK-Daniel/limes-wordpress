<?php $td = get_template_directory_uri(); ?>

<?php if(get_cur_template() != "tmp-about.php") : ?>
<section class="contact-bottom">
	<div class="section-inner">
		<p class="title">
			<span>לקבלת ייעוץ</span>
			<strong>נשמח שתצרו עמנו קשר</strong>
		</p>
		<?=do_shortcode('[contact-form-7 id="70" title="טופס footer"]');?>
	</div>
</section>
<?php endif; ?>
<footer>
	<div class="part-top">
		<div class="section-inner">
			<div class="cols">
				<?php
					$items = get_field("footer", "options")['cols_footer'];
					$i = 0;
					$numItems = count($items);
					foreach($items as $item):
				?>
					<div class="col">
						<p class="title"><?=$item['title']?></p>
						<div class="content">
							<?=$item['content']?>
						</div>
						
						<?php if(++$i === $numItems) : ?>
							<div class="wrapper-social">
								
								<div class="social">
								<p class="title">עקבו אחרינו</p>
									<?php 
										$links = get_field("footer", 'options')['social'];
										foreach($links as $link):
									?>
										<a href="<?=$link["link"];?>" target="_blank">
											<?=$link["icon"];?>
										</a>
									<?php endforeach; ?>
								</div>
							</div>	
							
							<div class="made-in-israel">
								<p>כל המוצרים מיוצרים, נתפרים ומרופדים אצלנו בסטודיו לפי דרישת הלקוח.</p>
								<img src="<?=$td?>/images/index/made-in-israel.png" alt="פינות ישיבה, וילונות ופופים בתפירה אישית" title="פינות ישיבה, וילונות ופופים בתפירה אישית">
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
		
			</div>
		</div>
	</div>

	<div class="part-bottom">
		<div class="section-inner">
			<p class="copy">© כל הזכויות שמורות ללימס סטודיו לעיצוב פינות ישיבה, וילונות ופופים ייחודיים</p>
	
			<a class="credit" href="https://www.extra.co.il/" target="_blank">
				<span>בניית אתרים קידום ושיווק דיגיטלי
				<img src="<?=$td?>/images/logoextra.png" alt="אקסטרה דיגיטל">
				<span>דיגיטל</span>
			</a>
		</div>
	</div>
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

<!-- <script src="//code.jquery.com/mobile/1.5.0-alpha.1/jquery.mobile-1.5.0-alpha.1.min.js"></script> -->
<?php wp_footer(); ?>

</body>
</html>