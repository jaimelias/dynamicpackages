<?php global $post; global $new_content; dynamicpackages_Public::event_date_update($post->ID); ?>

<div class="pure-g gutters">


	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
	
		
		<?php dynamicpackages_Public::children_package(); ?>
		
		<?php if(!dynamicpackages_Validators::has_children()):?>
			<div id="auto_booking"><?php dynamicpackages_Forms::auto_booking(); ?></div>
		<?php endif; ?>
		
		<?php dynamicpackages_Tables::daytour_pricetable(); ?>
		
		<?php dynamicpackages_Public::return_parent();?>
	</div>

	<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3 height-100">
		<div class="bottom-20"><?php dynamicpackages_Public::details(); ?></div>
		<div class="bottom-20"><?php dynamicpackages_Public::show_coupons(); ?></div>
	
	<?php  echo  $new_content; ?>
	
	<?php if(dynamicpackages_Validators::is_child()): ?><p><?php dynamicpackages_Public::return_parent();?></p><?php endif; ?>
	<hr />


		<div class="pure-g gutters">
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">		
				<?php
					if(dynamicpackages_Public::get_included_list($post))
					{
						echo dynamicpackages_Public::get_included_list($post);
					}
				?>					
			</div>
			<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
				<?php
					if(dynamicpackages_Public::get_not_included_list($post))
					{
						echo dynamicpackages_Public::get_not_included_list($post);
					}
				?>	
				<?php
					if(dynamicpackages_Public::get_terms_conditions_list($post))
					{
						echo dynamicpackages_Public::get_terms_conditions_list($post);
					}
				?>			
			</div>
		</div>
		
<hr/>

	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php 
				if(dynamicpackages_Public::get_location_list_ul($post))
				{
					echo dynamicpackages_Public::get_location_list_ul($post);
				}
			?>			
		</div>
		<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-2">
			<?php 
				if(dynamicpackages_Public::get_category_list_ul($post))
				{
					echo dynamicpackages_Public::get_category_list_ul($post);
				}
			?>			
		</div>
	</div>

<hr/>

	<h4><?php echo esc_html(__('Booking Details', 'dynamicpackages')); ?>:</h4>
	<div class="bottom-20"><?php dynamicpackages_Public::details(); ?></div>


<hr/>

	<?php
		if(dynamicpackages_Public::restrictions())
		{
			echo dynamicpackages_Public::restrictions();
		}
	?>	
	
	<?php dynamicpackages_Tables::daytour_pricetable(); ?>
	
	<?php comments_template('', true); ?>
				
	</div>

</div>