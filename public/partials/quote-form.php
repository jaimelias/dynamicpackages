<?php 
	$has_gateway = apply_filters('dy_has_gateway', false);
	$hide_form = ($has_gateway) ? 'class="hidden"' : null;
	$submit_form = ($has_gateway) ? __('Proceed', 'dynamicpackages') : __('Submit', 'dynamicpackages');
	$header_form = (is_singular('packages')) ? __('Send Your Request', 'dynamicpackages') : __('Contact Us', 'dynamicpackages');
	$request_form = (is_singular('packages')) ? 'estimate_request' : 'contact';

	$add_ons_value = '';
	$add_ons_package_id = 'dy_add_ons_' . get_the_ID();
	
	if(isset($_COOKIE[$add_ons_package_id]))
	{
		$add_ons_value = $_COOKIE[$add_ons_package_id];
	}
?>

<form id="dynamic_form"  <?php echo $hide_form;?> method="post" action="<?php echo esc_url(get_permalink()); ?>">

	    <div class="text-center bottom-20" id="dy_checkout_branding">
			<p class="large text-muted">
				<?php esc_html_e($header_form); ?>
			</p>
		</div>
		
		<hr />


		<?php if($has_gateway) : ?>
			<div id="dy_crypto_form" class="hidden small">
				<?php do_action('dy_crypto_form'); ?>
				<hr />
			</div>
		<?php endif; ?>
	
		<!-- Config -->
		<input type="hidden" name="dy_request" value="<?php echo esc_attr($request_form); ?>" />
		<input type="hidden" name="dy_recaptcha" />
		<input type="hidden" name="add_ons" value="<?php echo esc_attr($add_ons_value); ?>"/>
		<input type="hidden" name="lang" value="<?php echo esc_attr(substr(get_bloginfo ( 'language' ), 0, 2 ));?>" />
		
		<!-- Cookies -->
		<input type="hidden" name="channel" class="channel" value="" />
		<input type="hidden" name="device" class="device" value="" />
		<input type="hidden" name="landing_domain" class="landing_domain" value="" />
		<input type="hidden" name="landing_path" class="landing_path" value="" />
		
		<!-- Geolocation -->
		<input type="hidden" name="geo_city" value="" />
		<input type="hidden" name="geo_state_prov" value="" />
		<input type="hidden" name="geo_countrycode2"  value="" />
		<input type="hidden" name="geo_latitude"  value="" />
		<input type="hidden" name="geo_longitude" value="" />
		<input type="hidden" name="geo_ip"  value="" />
		<input type="hidden" name="geo_isp" value="" />

		<div>
			<h3><?php echo (esc_html__('Contact Details', 'dynamicpackages')); ?></h3>
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="first_name"><?php echo (esc_html__('Name', 'dynamicpackages')); ?></label>
					<input type="text" name="first_name" class="bottom-20 required" />
				</div>
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="lastname"><?php echo (esc_html__('Last Name', 'dynamicpackages')); ?></label>
					<input type="text" name="lastname" class="bottom-20 required" />
				</div>
			</div>
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="email"><?php echo (esc_html__('Email', 'dynamicpackages')); ?></label>
					<input type="email" name="email" class="bottom-20 required" />				
				</div>
				<div class="pure-u-1 pure-u-md-1-2">
						<label for="repeat_email"><?php echo (esc_html__('Repeat Email', 'dynamicpackages')); ?></label>
						<input type="email" name="repeat_email" class="bottom-20 required" />
				</div>
			</div>
			
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="phone"><?php echo (esc_html__('Phone', 'dynamicpackages')); ?> <span class="dy_mobile_payment"></span></label>
					<input type="text" name="phone" class="bottom-20 required" />				
				</div>
				<div class="pure-u-1 pure-u-md-1-2"></div>
			</div>			
			
		</div>

		<div id="dy_card_payment_conditions" class="hidden small">
			<?php do_action('dy_cc_warning'); ?>
		</div>
		
		<?php if($has_gateway) : ?>
			<?php do_action('dy_cc_form'); ?>
			<?php do_action('dy_terms_conditions'); ?>
		<?php endif; ?>
		
		<?php do_action('dy_contact_inquiry_textarea'); ?>
		
		<?php if(get_option('dy_recaptcha_site_key')): ?>
			<p>
				<button 
					type="button" 
					id="dy_submit_form"
					data-badge="bottomleft" 
					data-callback="checkoutFormSubmit"
					data-sitekey="<?php echo esc_attr(get_option('dy_recaptcha_site_key')); ?>"
					data-action='checkout'
					class="g-recaptcha pure-button pure-button-primary strong large"><?php esc_html_e($submit_form); ?></button>
			</p>	
		<?php endif; ?>
</form>