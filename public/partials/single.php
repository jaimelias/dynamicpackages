<?php global $post; global $new_content; dy_Public::event_date_update($post->ID); ?>

<div class="pure-g gutters">


	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
	
		
		<div class="small"><?php do_action('dy_show_coupons'); ?></div>
		
		<?php dy_Public::children_package(); ?>
		
		<?php if(!dy_validators::has_children()):?>
			<?php do_action('dy_check_prices_form'); ?>
		<?php endif; ?>
		
		<?php dynamicpackages_Tables::package_price_table(); ?>
		
		<?php dy_Public::return_parent();?>
	</div>

	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3 height-100">
		<div class="bottom-20"><?php echo apply_filters('dy_package_details', null); ?></div>
	
	<?php  echo  $new_content; ?>
	
	<?php if(dy_validators::is_child()): ?><p><?php dy_Public::return_parent();?></p><?php endif; ?>
	<hr />


		<div class="pure-g gutters">
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">		
				<?php
					if(dy_Public::get_included_list($post))
					{
						echo dy_Public::get_included_list($post);
					}
				?>					
			</div>
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
				<?php
					if(dy_Public::get_not_included_list($post))
					{
						echo dy_Public::get_not_included_list($post);
					}
				?>	
				<?php
					if(dy_Public::get_terms_conditions_list($post))
					{
						echo dy_Public::get_terms_conditions_list($post);
					}
				?>			
			</div>
		</div>
		
<hr/>

	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php 
				if(dy_Public::get_location_list_ul($post))
				{
					echo dy_Public::get_location_list_ul($post);
				}
			?>			
		</div>
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php 
				if(dy_Public::get_category_list_ul($post))
				{
					echo dy_Public::get_category_list_ul($post);
				}
			?>			
		</div>
	</div>

<hr/>

	<h4><?php echo esc_html_e(__('Booking Details', 'dynamicpackages')); ?>:</h4>
	<div class="bottom-20"><?php echo apply_filters('dy_package_details', null); ?></div>


<hr/>

	<?php
		if(dy_Public::restrictions())
		{
			echo dy_Public::restrictions();
		}
	?>	
	
	<?php dynamicpackages_Tables::package_price_table(); ?>

	<?php if(!dy_validators::has_children()):?>
			<?php do_action('dy_check_prices_form'); ?>
	<?php endif; ?>
	
	<?php comments_template('', true); ?>
				
	</div>

</div>