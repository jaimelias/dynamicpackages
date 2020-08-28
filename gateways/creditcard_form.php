
<div id="credit_card_form">
		
	<div id="dynamic_terms">
		<form id="dy_checkout_form" class="hidden" method="post" action="<?php echo esc_url(get_permalink().'#checkout'); ?>">
		
	<input type="hidden" name="dy_recaptcha" />
		
	<div class="text-center bottom-20">
		<img width="250" height="50" alt="Visa - Mastercard" src="<?php echo esc_url(plugin_dir_url( __FILE__ ).'matrix/visa-mastercard.svg'); ?>" />
	</div>
	
	<div class="minimal_alert bottom-20">
		<p class="small">
			<strong><?php echo esc_html(__('Before you book', 'dynamicpackages')); ?></strong> 
			<?php echo esc_html(__('In to complete the reservation process, we´ll request images of passports (foreigners) or identification documents (nationals) of each participant. These documents sent will be verified against the original ones at the meeting point by our staff. It is not allowed to book for third parties.', 'dynamicpackages')); ?>
		</p>
	</div>
		
		<div class="hidden">
			<input type="text" name="channel" class="channel" value="channel" />
		</div>
			
			<fieldset>
			<h3><?php echo esc_html(__('Contact Details', 'dynamicpackages')); ?></h3>
				<div class="pure-g gutters">
					<div class="pure-u-1 pure-u-lg-1-2">
						<label for="name"><?php echo esc_html(__('Name', 'dynamicpackages')); ?></label>
						<input type="text" name="fname" class="bottom-20" />
					</div>
					<div class="pure-u-1 pure-u-lg-1-2">
						<label for="lastname"><?php echo esc_html(__('Last Name', 'dynamicpackages')); ?></label>
						<input type="text" name="lastname" class="bottom-20" />
					</div>
				</div>
				<div class="pure-g gutters">
					<div class="pure-u-1 pure-u-lg-1-2">
						<label for="email"><?php echo esc_html(__('Email', 'dynamicpackages')); ?></label>
						<input type="email" name="email" class="bottom-20" />
					</div>
					<div class="pure-u-1 pure-u-lg-1-2">
						<label for="phone"><?php echo esc_html(__('Phone', 'dynamicpackages')); ?></label>
						<input type="text" name="phone" class="bottom-20" />
					</div>
				</div>
				<div class="pure-g gutters">
					<div class="pure-u-1 pure-u-lg-1-3">
						<label for="country"><?php echo esc_html(__('Country', 'dynamicpackages')); ?></label>
						<select name="country" class="countrylist bottom-20"><option>--</option></select>
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
							
			</fieldset>
			
			
			
			<fieldset>
			<h3><?php echo esc_html(__('Billing Details', 'dynamicpackages')); ?></h3>
			<p><label for="CCNum"><?php echo esc_html(__('Credit Card Number', 'dynamicpackages')); ?></label>
			<input class="large" min="16" type="number" name="CCNum" /></p>
			
				<div class="pure-g gutters">
					<div class="pure-u-1 pure-u-lg-1-3">
						<label for="ExpMonth"><?php echo esc_html(__('Expiration Month', 'dynamicpackages')); ?></label>
					
						<select name="ExpMonth" class="bottom-20">
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
			</fieldset>		

			<fieldset class="package_terms_conditions">
				<h4><?php echo esc_html(__('Terms & Conditions', 'dynamicpackages')); ?></h4>		
			</fieldset>		
			
			<p><button type="button" id="confirm_checkout" class="pure-button pure-button-primary rounded"><?php echo esc_html(__('Pay Now!', 'dynamicpackages')); ?></button></p>
		</form>
	</div>
</div>