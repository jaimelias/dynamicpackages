
<hr/>

<div class="dy_card_form_fields hidden">

	<h3><?php echo esc_html(__('Billing Address', 'dynamicpackages')); ?></h3>
	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="country"><?php echo esc_html(__('Country', 'dynamicpackages')); ?></label>
			<select name="country" id="country" class="countrylist bottom-20"><option value="">--</option></select>
		</div>
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="city"><?php echo esc_html(__('City', 'dynamicpackages')); ?></label>
			<input type="text" name="city" id="city" class="bottom-20" />
		</div>
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="address"><?php echo esc_html(__('Address', 'dynamicpackages')); ?></label>
			<input type="text" name="address" id="address" class="bottom-20" />
		</div>					
	</div>
	<hr/>
</div>

<div class="dy_card_form_fields hidden">
	<h3><?php echo esc_html(__('Card Details', 'dynamicpackages')); ?></h3>
	<?php echo apply_filters('dy_debug_instructions', null); ?>
	<p><label for="CCNum"><?php echo esc_html(__('Card Numbers', 'dynamicpackages')); ?></label>
	<input class="large" min="16" type="number" name="CCNum" id="CCNum" /></p>

	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="ExpMonth"><?php echo esc_html(__('Expiration Month', 'dynamicpackages')); ?></label>
		
			<select name="ExpMonth" id="ExpMonth" class="bottom-20">
			<option value="">--</option>
			<?php 
				for($x = 0; $x < 12; $x++ )
				{
					$month = sprintf("%02d", $x+1);
					echo '<option value="'.esc_attr($month).'">'.esc_html($month).'</option>';
				}
			?>
			</select>	
		</div>
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="ExpYear"><?php echo esc_html(__('Expiration Year', 'dynamicpackages')); ?></label>
			<select name="ExpYear" id="ExpYear" class="bottom-20">
			<option value="">--</option>
			<?php 
				for($x = intval(date('y')); $x < intval(date('y'))+10; $x++ )
				{
					$year = sprintf("%02d", $x);
					echo '<option value="'.esc_attr($year).'">'.esc_html($year).'</option>';
				}
			?>						
			</select>
		</div>					
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="CVV2">CVV</label>
			<input min="0" max="999" type="number" name="CVV2" id="CVV2" class="bottom-20"/>
		</div>
	</div>
	<hr/>
</div>



<?php echo do_action('dy_form_terms_conditions'); ?>