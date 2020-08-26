<?php global $post; ?>

<form id="dynamic_form"  <?php if(dynamicpackages_Settings::has_any_gateway()) : ?>class="hidden"<?php endif;?> method="post" action="<?php echo esc_url(get_permalink()); ?>">

	
	    <div class="text-center bottom-20" id="dy_form_icon">
			<p class="large text-muted">
				<?php echo esc_html(__('Get Your Quote Now!', 'dynamicpackages')); ?>
			</p>
		</div>
		<hr />
	
		<input type="hidden" name="dy_platform" value="quote" />
		<input type="hidden" name="dy_recaptcha" />
		<input type="hidden" name="channel" class="channel" value="" />
		<input type="hidden" name="device" class="device" value="" />
		<input type="hidden" name="landing_domain" class="landing_domain" value="" />
		<input type="hidden" name="landing_path" class="landing_path" value="" />
		<input type="hidden" name="city" class="city" value="" />
		<input type="hidden" name="countrycode" class="country_code2" value="" />
		<input type="hidden" name="lang" value="<?php echo esc_html(substr(get_bloginfo ( 'language' ), 0, 2 ));?>" />
		<input type="hidden" name="total" value="<?php echo dy_utilities::currency_format(dy_sum_tax(dynamicpackages_Checkout::amount())); ?>" />

		<div class="pure-g gutters">
			<div class="pure-u-1 pure-u-md-1-2">
				<label for="fname"><?php echo esc_html(__('Name', 'dynamicpackages')); ?></label>
				<input type="text" name="fname" class="bottom-20 required" />
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
	<p><button type="button" id="dy_submit_form" class="pure-button pure-button-primary rounded"><?php echo esc_html(__('Send Request!', 'dynamicpackages')); ?></button></p>	

</form>