<?php global $post; global $new_content; dy_utilities::event_date_update($post->ID); ?>

<div class="pure-g gutters">


	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
	
		
		<div class="small"><?php do_action('dy_show_coupons'); ?></div>
		
		<?php do_action('dy_children_package'); ?>
		
		<?php if(!dy_validators::has_children()):?>
			<?php do_action('dy_check_prices_form'); ?>
		<?php endif; ?>
		
		<?php do_action('dy_price_table'); ?>
		
		<?php do_action('dy_similar_packages_link'); ?>
	</div>

	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3 height-100">
		<div class="bottom-20"><?php echo apply_filters('dy_details', null); ?></div>
	
	<?php  echo  $new_content; ?>
	
	<?php do_action('dy_similar_packages_link'); ?>

	<hr />


		<div class="pure-g gutters">
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">		
				<?php do_action('dy_get_included_list'); ?>				
			</div>
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
				<?php do_action('dy_get_not_included_list'); ?>		
				<?php do_action('dy_get_taxonomies_list'); ?>		
			</div>
		</div>
		
<hr/>

	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php do_action('dy_get_location_list'); ?>			
		</div>
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php do_action('dy_get_category_list'); ?>				
		</div>
	</div>

<hr/>

	<h4><?php echo (esc_html__('Booking Details', 'dynamicpackages')); ?>:</h4>
	<div class="bottom-20"><?php echo apply_filters('dy_details', null); ?></div>

<hr/>
	
	<?php do_action('dy_price_table'); ?>

	<?php if(!dy_validators::has_children()):?>
			<?php do_action('dy_check_prices_form'); ?>
	<?php endif; ?>
	
	<?php comments_template('', true); ?>
				
	</div>

</div>