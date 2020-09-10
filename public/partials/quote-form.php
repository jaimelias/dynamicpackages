<?php global $post; ?>

<form id="dynamic_form"  <?php if(apply_filters('dy_has_any_gateway', false)) : ?>class="hidden"<?php endif;?> method="post" action="<?php echo esc_url(get_permalink()); ?>">

	
	    <div class="text-center bottom-20" id="dy_form_icon">
			<p class="large text-muted">
				<?php echo esc_html(__('Get Your Quote Now!', 'dynamicpackages')); ?>
			</p>
		</div>
		
		<hr />
	
		<!-- Config -->
		<input type="hidden" name="dy_request" value="request" />
		<input type="hidden" name="dy_recaptcha" />
		<input type="hidden" name="add_ons" />
		<input type="hidden" name="lang" value="<?php echo esc_html(substr(get_bloginfo ( 'language' ), 0, 2 ));?>" />
		
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
						<label for="repeat_email"><?php echo esc_html(__('Repeat Email', 'dynamicpackages')); ?></label>
						<input type="email" name="repeat_email" class="bottom-20 required" />
				</div>
			</div>
			
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-md-1-2">
					<label for="phone"><?php echo esc_html(__('Phone', 'dynamicpackages')); ?> <span class="dy_mobile_payment"></span></label>
					<input type="text" name="phone" class="bottom-20 required" />				
				</div>
				<div class="pure-u-1 pure-u-md-1-2"></div>
			</div>			
			
		</div>
		
		<?php if(apply_filters('dy_has_any_gateway', false)) : ?>
			<div id="dy_cc_form"><?php do_action('dy_cc_form'); ?></div>
		<?php endif; ?>
		
	<p><button type="button" id="dy_submit_form" class="pure-button pure-button-primary rounded"><?php echo (isset($_GET['quote']) || dy_utilities::pax_num() > package_field('package_max_persons')) ? esc_html(__('Send Request!', 'dynamicpackages')) : esc_html(__('Pay Now!', 'dynamicpackages')); ?></button></p>	

</form>