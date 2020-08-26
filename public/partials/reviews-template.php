<?php if(have_comments()): ?>
	<div id="dy_reviews">
		<h3>
			<?php
				echo dynamicpackages_Reviews::wp_star_rating(dynamicpackages_Reviews::get_rating(get_the_ID())).' '.__('Rated', 'dynamicpackages').' <span>'.esc_html(dynamicpackages_Reviews::get_rating(get_the_ID())).'</span>';
				echo ' '.__('Based on', 'dynamicpackages').' <span>'.esc_html(get_comments_number()).'</span> '.esc_html(__('Review(s)', 'dynamicpackages'));
			?>
		</h3>

		<div class="navigation">
			<div class="alignleft"><?php previous_comments_link(); ?></div>
			<div class="alignright"><?php next_comments_link(); ?></div>
		</div>

		<ul class="reviewlist small list-style-none">
		<?php wp_list_comments(); ?>
		</ul>

		<div class="navigation">
			<div class="alignleft"><?php previous_comments_link(); ?></div>
			<div class="alignright"><?php next_comments_link(); ?></div>
		</div>
	</div>
<?php else : // this is displayed if there are no reviews so far ?>

	<?php if ( comments_open() ) : ?>
		<!-- If reviews are open, but there are no reviews. -->

	<?php else : // reviews are closed ?>
		<!-- If reviews are closed. -->
		<p class="noreviews"><?php echo __( 'Reviews are closed.', 'dynamicpackages'); ?></p>

	<?php endif; ?>
<?php endif; ?>

<?php comment_form(); ?>
