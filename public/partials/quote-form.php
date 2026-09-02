<?php 
	if ( !defined( 'WPINC' ) ) exit;
	
	$has_gateway = apply_filters('dy_has_gateway', false);
	$hide_form = ($has_gateway) ? 'class="hidden"' : null;
	$submit_form = ($has_gateway) ? __('Proceed', 'dynamicpackages') : __('Submit', 'dynamicpackages');
	$header_form = (is_singular('packages')) ? __('Send Your Request', 'dynamicpackages') : __('Contact Us', 'dynamicpackages');
	$request_form = (is_singular('packages')) ? 'estimate_request' : 'contact';

	$add_ons_value = '';
	$add_ons_package_id = 'dy_add_ons_' . get_dy_id();
	
	if(isset($_COOKIE[$add_ons_package_id]))
	{
		$add_ons_value = $_COOKIE[$add_ons_package_id];
	}

	$autocomplete_scope = $has_gateway
		? 'section-payment billing'
		: 'section-contact';
?>

<form id="dy_package_request_form" <?php echo $hide_form;?>  data-method="post" data-action="<?php echo esc_attr(base64_encode(get_permalink())); ?>">

	    <div class="text-center bottom-20" id="dy_checkout_branding">
			<p class="large text-muted">
				<?php echo esc_html(__($header_form)); ?>
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
		<input type="hidden" name="unique_tx_id" value="" />
		<input type="hidden" name="dy_request" value="<?php echo esc_attr($request_form); ?>" />
		<input type="hidden" name="add_ons" value="<?php echo esc_attr($add_ons_value); ?>"/>
		<input type="hidden" name="dy_id" value="<?php echo esc_attr(get_dy_id()); ?>"/>

		<?php if(get_has('route')): ?>
			<input type="hidden" name="route" value="<?php echo esc_attr(sanitize_text_field($_GET['route'])); ?>"/>
		<?php endif; ?>
		
		<?php if(get_has('enable_payment')): ?>
			<input type="hidden" name="enable_payment" value="true"/>
		<?php endif; ?>


		<?php if(get_has('force_availability')): ?>
			<input type="hidden" name="force_availability" value="true"/>
		<?php endif; ?>		

		<div>
			<h3><?php echo esc_html(__('Contact Details', 'dynamicpackages')); ?></h3>
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="first_name"><?php echo esc_html(__('Name', 'dynamicpackages')); ?></label>
					<input
						type="text"
						name="first_name"
						id="first_name"
						class="bottom-20 required"
						autocomplete="<?php echo esc_attr($autocomplete_scope . ' given-name'); ?>"
						autocapitalize="words"
						required
					>
				</div>
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="lastname"><?php echo esc_html(__('Last Name', 'dynamicpackages')); ?></label>
					<input
						type="text"
						name="lastname"
						id="lastname"
						class="bottom-20 required"
						autocomplete="<?php echo esc_attr($autocomplete_scope . ' family-name'); ?>"
						autocapitalize="words"
						required
					>
				</div>
			</div>
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="email"><?php echo esc_html(__('Email', 'dynamicpackages')); ?></label>
					<input
						type="email"
						name="email"
						id="email"
						class="bottom-20 required"
						autocomplete="<?php echo esc_attr($autocomplete_scope . ' email'); ?>"
						autocapitalize="none"
						autocorrect="off"
						spellcheck="false"
						required
					>				
				</div>
				<div class="pure-u-1 pure-u-md-1-2">
						<label for="repeat_email"><?php echo esc_html(__('Repeat Email', 'dynamicpackages')); ?></label>
						<input
							type="email"
							name="repeat_email"
							id="repeat_email"
							class="bottom-20 required"
							autocomplete="off"
							autocapitalize="none"
							autocorrect="off"
							spellcheck="false"
							required
						>
				</div>
			</div>
			
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<div class="bottom-20">
						<label for="phone"><?php echo esc_html(__('Phone', 'dynamicaviation')); ?></label>
						<div class="pure-g">
							<div class="pure-u-1-2">
								<select
									name="country_calling_code"
									id="country_calling_code"
									class="countryCallingCode required"
									autocomplete="<?php echo esc_attr($autocomplete_scope . ' tel-country-code'); ?>"
									required
									>
									<option value="">--</option>
								</select>
							</div>
							<div class="pure-u-1-2">
								<input
									type="tel"
									inputmode="tel"
									name="phone"
									id="phone"
									class="required"
									autocomplete="<?php echo esc_attr($autocomplete_scope . ' tel-national'); ?>"
									aria-label="<?php echo esc_attr__('Phone Number', 'dynamicpackages'); ?>"
									required
								>
							</div>
						</div>
					</div>
				</div>							
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
		
		<?php if(get_option('dy_cf_turnstile_site_key')): ?>
			<div class="dy-turnstile-submit">
				<div
					class="cf-turnstile"
					data-sitekey="<?php echo esc_attr(get_option('dy_cf_turnstile_site_key')); ?>"
					data-retry="auto"
					data-refresh-expired="auto">
				</div>

				<button
					type="button"
					onClick="checkoutFormSubmit(); return false;"
					class="pure-button pure-button-primary strong large">
					<?php echo esc_html(__($submit_form)); ?>
				</button>
			</div>
<?php endif; ?>
</form>