<hr/>

<div>
	<h3><?php echo esc_html(__('Before Booking', 'dynamicpackages')); ?></h3>
	<p class="minimal_alert small"><i class="fas fa-exclamation-triangle"></i> <?php esc_html_e(__('It is not allowed to book for third parties.', 'dynamicpackages')); ?></p>
	<p class="minimal_alert small"><i class="fas fa-exclamation-triangle"></i> <?php esc_html_e(__('To complete this reservation we require images of the passports (foreigners) or valid Identity Documents (nationals) of each participant. The documents you send will be compared against the originals at the meeting point.', 'dynamicpackages')); ?></p>
</div>

<hr/>

<div>

	<h3><?php echo esc_html(__('Billing Address', 'dynamicpackages')); ?></h3>
	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="country"><?php echo esc_html(__('Country', 'dynamicpackages')); ?></label>
			<select name="country" class="countrylist bottom-20"><option value="">--</option></select>
		</div>
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="city"><?php echo esc_html(__('City', 'dynamicpackages')); ?></label>
			<input type="text" name="city" class="bottom-20" />
		</div>
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="address"><?php echo esc_html(__('Address', 'dynamicpackages')); ?></label>
			<input type="text" name="address" class="bottom-20" />
		</div>					
	</div>
</div>

<hr/>

<div>
	<h3><?php echo esc_html(__('Card Details', 'dynamicpackages')); ?></h3>
	<p><label for="CCNum"><?php echo esc_html(__('Credit Card Number', 'dynamicpackages')); ?></label>
	<input class="large" min="16" type="number" name="CCNum" /></p>

	<div class="pure-g gutters">
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="ExpMonth"><?php echo esc_html(__('Expiration Month', 'dynamicpackages')); ?></label>
		
			<select name="ExpMonth" class="bottom-20">
			<option value="">--</option>
			<?php 
				for($x = 0; $x < 12; $x++ )
				{
					echo '<option>'.sanitize_text_field(sprintf("%02d", $x+1)).'</option>';
				}
			?>
			</select>	
		</div>
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="ExpYear"><?php echo esc_html(__('Expiration Year', 'dynamicpackages')); ?></label>
			<select name="ExpYear" class="bottom-20">
			<option value="">--</option>
			<?php 
				for($x = intval(date('y')); $x < intval(date('y'))+10; $x++ )
				{
					echo '<option>'.sanitize_text_field(sprintf("%02d", $x)).'</option>';
				}
			?>						
			</select>
		</div>					
		<div class="pure-u-1 pure-u-lg-1-3">
			<label for="CVV2">CVV</label>
			<input min="0" max="999" type="number" name="CVV2" class="bottom-20"/>
		</div>
	</div>
</div>

<hr/>

<?php echo do_action('dy_form_terms_conditions'); ?>