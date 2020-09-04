<?php global $post; ?>

<form id="dynamic_form"  <?php if(dy_Gateways::has_any_gateway()) : ?>class="hidden"<?php endif;?> method="post" action="<?php echo esc_url(get_permalink()); ?>">

	
	    <div class="text-center bottom-20" id="dy_form_icon">
			<p class="large text-muted">
				<?php echo esc_html(__('Get Your Quote Now!', 'dynamicpackages')); ?>
			</p>
		</div>
		<hr />
	
		<input type="hidden" name="dy_request" value="request" />
		<input type="hidden" name="dy_recaptcha" />
		<input type="hidden" name="channel" class="channel" value="" />
		<input type="hidden" name="device" class="device" value="" />
		<input type="hidden" name="landing_domain" class="landing_domain" value="" />
		<input type="hidden" name="landing_path" class="landing_path" value="" />
		<input type="hidden" name="city" class="city" value="" />
		<input type="hidden" name="countrycode" class="country_code2" value="" />
		<input type="hidden" name="lang" value="<?php echo esc_html(substr(get_bloginfo ( 'language' ), 0, 2 ));?>" />
		<input type="hidden" name="total" value="<?php echo dy_utilities::currency_format(dy_sum_tax(dy_utilities::amount())); ?>" />

		<div>
			<h3><?php echo esc_html(__('Contact Details', 'dynamicpackages')); ?></h3>
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="first_name"><?php echo esc_html(__('Name', 'dynamicpackages')); ?></label>
					<input type="text" name="first_name" class="bottom-20 required" />
				</div>
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="lastname"><?php echo esc_html(__('Last Name', 'dynamicpackages')); ?></label>
					<input type="text" name="lastname" class="bottom-20 required" />
				</div>
			</div>
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="email"><?php echo esc_html(__('Email', 'dynamicpackages')); ?></label>
					<input type="email" name="email" class="bottom-20 required" />
				</div>
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="phone"><?php echo esc_html(__('Phone', 'dynamicpackages')); ?> <span class="dy_mobile_payment"></span></label>
					<input type="text" name="phone" class="bottom-20 required" />
				</div>
			</div>
		</div>
		
		<hr/>
		
		<div>
			<h3><?php echo esc_html(__('Billing Address', 'dynamicpackages')); ?></h3>
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
		</div>

		<hr/>
		
		<?php echo do_action('dy_form_terms_conditions'); ?>
		
	<p><button type="button" id="dy_submit_form" class="pure-button pure-button-primary rounded"><?php echo esc_html(__('Send Request!', 'dynamicpackages')); ?></button></p>	

</form>