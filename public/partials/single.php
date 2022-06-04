<?php global $post; global $new_content; Dynamic_Packages_Public::event_date_update($post->ID); ?>

<div class="pure-g gutters">


	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
	
		
		<div class="small"><?php do_action('dy_show_coupons'); ?></div>
		
		<?php Dynamic_Packages_Public::children_package(); ?>
		
		<?php if(!dy_validators::has_children()):?>
			<?php do_action('dy_check_prices_form'); ?>
		<?php endif; ?>
		
		<?php do_action('dy_price_table'); ?>
		
		<?php Dynamic_Packages_Public::return_parent();?>
	</div>

	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3 height-100">
		<div class="bottom-20"><?php echo apply_filters('dy_details', null); ?></div>
	
	<?php  echo  $new_content; ?>
	
	<?php if(dy_validators::is_child()): ?><p><?php Dynamic_Packages_Public::return_parent();?></p><?php endif; ?>
	<hr />


		<div class="pure-g gutters">
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">		
				<?php
					if(Dynamic_Packages_Public::get_included_list($post))
					{
						echo Dynamic_Packages_Public::get_included_list($post);
					}
				?>					
			</div>
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
				<?php
					if(Dynamic_Packages_Public::get_not_included_list($post))
					{
						echo Dynamic_Packages_Public::get_not_included_list($post);
					}
				?>	
				<?php
					if(Dynamic_Packages_Public::get_terms_conditions_list($post))
					{
						echo Dynamic_Packages_Public::get_terms_conditions_list($post);
					}
				?>			
			</div>
		</div>
		
<hr/>

	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php 
				if(Dynamic_Packages_Public::get_location_list_ul($post))
				{
					echo Dynamic_Packages_Public::get_location_list_ul($post);
				}
			?>			
		</div>
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php 
				if(Dynamic_Packages_Public::get_category_list_ul($post))
				{
					echo Dynamic_Packages_Public::get_category_list_ul($post);
				}
			?>			
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