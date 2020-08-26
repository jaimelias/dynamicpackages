<?php

class bank_transfer{
	
	function __construct()
	{
		if(is_admin())
		{
			add_action( 'admin_init', array('bank_transfer', 'settings_init'), 1);
			add_action('admin_menu', array('bank_transfer', 'add_settings_page'), 101);			
		}
		else
		{
			add_filter('the_content', array('bank_transfer', 'filter_content'), 102);
			add_filter('the_title', array('bank_transfer', 'title'), 102);
			add_filter('pre_get_document_title', array('bank_transfer', 'title'), 102);
			add_filter('get_the_excerpt', array('bank_transfer', 'filter_excerpt'), 102);
			add_filter('gateway_buttons', array('bank_transfer', 'button'), 4);
			add_filter('list_gateways', array('bank_transfer', 'add_gateway'), 4);
			add_action('wp_enqueue_scripts', array('bank_transfer', 'scripts'), 102);
		}
	}
	public static function is_active()
	{
		$output = false;
		global $bank_transfer_is_active;
		
		if(isset($bank_transfer_is_active))
		{
			$output = true;
		}
		else
		{
			if(get_option('bank_transfer') != '')
			{
				$GLOBALS['bank_transfer_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public static function show_bank()
	{
		$output = false;
		global $bank_transfer_show_bank;
		
		if(isset($bank_transfer_show_bank))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && self::is_active())
			{
				if(self::is_valid())
				{
					$GLOBALS['bank_transfer_show_bank'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public static function is_valid_request()
	{
		$output = false;
		global $bank_is_valid_request;
		
		if(isset($bank_is_valid_request))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_platform']) && isset($_POST['total']))
			{
				if($_POST['dy_platform'] == 'bank_transfer' && intval($_POST['total']) > 1)
				{
					$GLOBALS['bank_is_valid_request'] = true;
					$output = true;
				}
			}		
		}
		
		return $output;
	}
	public static function filter_excerpt($excerpt)
	{
		if(in_the_loop() && dynamicpackages_Validators::validate_quote() && self::is_valid_request())
		{
			$excerpt = esc_html(__('Hello', 'dynamicpackages').' '.sanitize_text_field($_POST['fname']).',');
		}
		return $excerpt;
	}
	public static function filter_content($content)
	{
		if(in_the_loop() && dynamicpackages_Validators::validate_quote() && self::is_valid_request())
		{
			if(dynamicpackages_Validators::validate_recaptcha())
			{
				dynamicpackages_Checkout::webhook('tp_quote_webhook', json_encode($_POST));
				$content = self::message();
				self::send();
			}
		}
		return $content;
	}
	
	public static function send()
	{
		
		$admin_email = get_option('admin_email');
		$headers = array('Content-type: text/html');
		$admin_subject = sanitize_text_field($_POST['fname']).' '.__('attempts to pay using', 'dynamicpackages').' '. sanitize_text_field($_POST['dy_platform']);
		$admin_body = '<p>'.$admin_subject.'</p>'.sanitize_text_field($_POST['description']);
		$admin_body .= '<p>'.__('Name', 'dynamicpackages').': '.sanitize_text_field($_POST['fname']).' '.sanitize_text_field($_POST['lastname']).'<br/>';
		$admin_body .= __('Email', 'dynamicpackages').': '.sanitize_text_field($_POST['email']).'<br/>';
		$admin_body .= __('Phone', 'dynamicpackages').': '.sanitize_text_field($_POST['phone']).'</p>';
		
		$user_subject = __('Payment Instructions', 'dynamicpackages').' - '.get_bloginfo('name');
		$user_body = '<p>'.__('Hello', 'dynamicpackages').' '.sanitize_text_field($_POST['fname']).',</p>'.self::message();
		
		wp_mail($admin_email, $admin_subject, $admin_body, $headers);
		wp_mail(sanitize_email($_POST['email']), $user_subject, $user_body, $headers);
	}	
	public static function title($title)
	{
		if(in_the_loop() && dynamicpackages_Validators::validate_quote() && self::is_valid_request())
		{
			$title = esc_html(__('Pay From Your Bank', 'dynamicpackages'));
		}
		return $title;
	}
	public static function message()
	{
		
		$amount = dy_utilities::currency_symbol().number_format(sanitize_text_field($_POST['total']), 2, '.', ',');
		
		$label = __('payment', 'dynamicpackages');
		
		if(dynamicpackages_Validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}		
		
		$output = '<p class="large">'.__('To complete the booking please send us the', 'dynamicpackages');
		$output .= ' '.$label.' (';
		$output .= $amount;
		
		$output .= ') '. __('to the following account', 'dynamicpackages').'.</p>';
		
		$output .= '<p class="large dy_pad padding-10">'.self::account().'</p>';
		
		$output .= '<p class="large">'.esc_html(__('Once we receive the slip and payment your booking will be completed this way', 'dynamicpackages')).': <strong>'.sanitize_text_field($_POST['description']).'</strong></p>';
		
		return $output;
	}
	
	public static function account()
	{
		
		$type = __('Saving Account', 'dynamicpackages');
		
		if(get_option('bank_transfer_type') == 1)
		{
			$type = __('Checking Account', 'dynamicpackages');
		}
		
		$bank = esc_html(__('Bank', 'dynamicpackages')).': <strong>'.esc_html(get_option('bank_transfer_name')).'</strong> <br/>';
		$bank .= esc_html(__('Account Type', 'dynamicpackages')).': <strong>'.esc_html($type).'</strong><br/>';
		$bank .= esc_html(__('Account Number', 'dynamicpackages')).': <strong>'.esc_html(get_option('bank_transfer')).'</strong><br/>';
		$bank .= esc_html(__('Account Name', 'dynamicpackages')).': <strong>'.esc_html(get_option('bank_transfer_account')).'</strong>';

		return $bank;
	}

	public static function is_valid()
	{
		$output = false;
		global $bank_transfer_is_valid;
		
		if(isset($bank_transfer_is_valid))
		{
			return true;
		}
		else
		{
			if(self::is_active() && !isset($_GET['quote']))
			{
				$min = floatval(get_option(sanitize_title('bank_transfer_min')));
				$show = intval(get_option(sanitize_title('bank_transfer_show')));
				$payment = package_field('package_payment');
				$deposit = floatval(dynamicpackages_Public::get_deposit());
				
				if(is_booking_page())
				{
					$total = dynamicpackages_Public::total();
				}
				else
				{
					$total = floatval(dynamicpackages_Public::starting_at());
					
					if(package_field('package_starting_at_unit') == 0)
					{
						$total = $total * intval(package_field('package_max_persons'));
					}
					
				}

				if(dynamicpackages_Validators::has_deposit())
				{
					$total = $total * ($deposit/100);
				}
				
				if($total >= $min)
				{
					if($payment == $show && $payment == 0)
					{
						$output = true;
					}
					else
					{
						if(dynamicpackages_Validators::has_deposit())
						{
							$output = true;
						}
					}
				}
			}
			
			if($output == true){
				$GLOBALS['bank_transfer_is_valid'] = true;
			}
		}
		return $output;
	}

	public static function settings_init()
	{
		//Bank
		
		register_setting('bank_transfer_settings', 'bank_transfer', 'sanitize_text_field');
		register_setting('bank_transfer_settings', 'bank_transfer_account', 'sanitize_text_field');
		register_setting('bank_transfer_settings', 'bank_transfer_name', 'sanitize_text_field');
		register_setting('bank_transfer_settings', 'bank_transfer_type', 'sanitize_text_field');
		register_setting('bank_transfer_settings', 'bank_transfer_show', 'intval');
		register_setting('bank_transfer_settings', 'bank_transfer_min', 'floatval');
		
		add_settings_section(
			'bank_transfer_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'bank_transfer_settings'
		);		
		
		add_settings_section(
			'bank_transfer_settings_section', 
			esc_html(__( 'Bank Settings', 'dynamicpackages' )), 
			'', 
			'bank_transfer_settings'
		);
		
		add_settings_field( 
			'bank_transfer_name', 
			esc_html(__( 'Bank Name', 'dynamicpackages' )), 
			array('bank_transfer', 'input_text'), 
			'bank_transfer_settings', 
			'bank_transfer_settings_section', 'bank_transfer_name'
		);		
		add_settings_field( 
			'bank_transfer_type', 
			esc_html(__( 'Account Type', 'dynamicpackages' )), 
			array('bank_transfer', 'display_bank_transfer_type'), 
			'bank_transfer_settings', 
			'bank_transfer_settings_section'
		);	
		add_settings_field( 
			'bank_transfer_account', 
			esc_html(__( 'Account Name', 'dynamicpackages' )), 
			array('bank_transfer', 'input_text'), 
			'bank_transfer_settings', 
			'bank_transfer_settings_section', 'bank_transfer_account'
		);			
		
		add_settings_field( 
			'bank_transfer', 
			esc_html(__( 'Account Number', 'dynamicpackages' )), 
			array('bank_transfer', 'input_number'), 
			'bank_transfer_settings', 
			'bank_transfer_settings_section', 'bank_transfer'
		);	
		add_settings_field( 
			'bank_transfer_min', 
			esc_html(__( 'Min. Amount', 'dynamicpackages' )), 
			array('bank_transfer', 'input_number'), 
			'bank_transfer_settings', 
			'bank_transfer_control_section', 'bank_transfer_min'
		);
		add_settings_field( 
			'bank_transfer_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array('bank_transfer', 'display_bank_transfer_show'), 
			'bank_transfer_settings', 
			'bank_transfer_control_section'
		);		
	}
	
	public static function input_text($name){
		$option = get_option($name);
		?>
		<input type="text" name="<?php echo esc_html($name); ?>" id="<?php echo esc_html($name); ?>" value="<?php echo esc_html($option); ?>" />
		<?php
	}
	
	public static function input_number($name){
		$option = get_option($name);
		?>
		<input type="number" name="<?php echo esc_html($name); ?>" id="<?php echo esc_html($name); ?>" value="<?php echo esc_html($option); ?>" /> #
		<?php
	}	
		
	
	public static function display_bank_transfer_type() { ?>
		<select name='bank_transfer_type'>
			<option value="0" <?php selected(get_option('bank_transfer_type'), 0); ?>><?php echo esc_html('Saving', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option('bank_transfer_type'), 1); ?>><?php echo esc_html('Checking', 'dynamicpackages'); ?></option>
		</select>
	<?php }
			
	
	public static function display_bank_transfer_show() { ?>
		<select name='bank_transfer_show'>
			<option value="0" <?php selected(get_option('bank_transfer_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option('bank_transfer_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
		</select>
	<?php }	

	public static function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', 'Bank', 'Bank', 'manage_options', 'bank_transfer', array('bank_transfer', 'settings_page'));
	}
	public static function settings_page()
		 { 
		?><div class="wrap">
		<form action='options.php' method='post'>
			
			<h1><?php echo esc_html(__('Local Bank', 'dynamicpackages')); ?></h1>	
			<?php
			settings_fields( 'bank_transfer_settings' );
			do_settings_sections( 'bank_transfer_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	public static function button($output)
	{
		if(self::show_bank() && in_array(self::gateway_name(), self::list_gateways_cb()))
		{
			$output .= ' <button class="pure-button bottom-20 pure-button-bank  withbank rounded" type="button"><i class="fas fa-money-check-alt"></i> '.esc_html(__('Local Bank', 'dynamicpackages')).'</button>';			
		}
		return $output;
	}
	public static function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	public static function gateway_name()
	{
		return __('Local Bank Transfer', 'dynamicpackages');
	}
	public static function add_gateway($array)
	{
		if(self::show_bank() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$array[] = self::gateway_name();
		}
		return $array;	
	}
	public static function scripts()
	{
		if(self::show_bank())
		{
			wp_add_inline_style('minimalLayout', self::css());
			wp_add_inline_script('dynamicpackages', self::js(), 'before');	
		}
	}
	public static function css()
	{
		ob_start();
		?>
			.pure-button.pure-button-bank, .pure-button-bank
			{
				background-color: #262626;
				color: #fff;
			}
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}
	public static function js()
	{
		ob_start();
		?>
		$(function(){
			$('.withbank').click(function()
			{
				var bank_logo = $('<p class="large"><?php echo esc_html(__('Pay to local bank account in', 'dynamicpackages')); ?> <strong><?php echo esc_html(get_option('bank_transfer_name')); ?></strong></p>').addClass('text-muted');
				$('#dynamic-checkout').addClass('hidden');
				$('#dynamic_form').removeClass('hidden');
				$('#dy_form_icon').html(bank_logo);
				$('#dynamic_form').find('input[name="name"]').focus();
				$('#dynamic_form').find('input[name="dy_platform"]').val('bank_transfer');
				
				//facebook pixel
				if(typeof fbq !== typeof undefined)
				{
					console.log('InitiateCheckout');
					fbq('track', 'InitiateCheckout');
				}

				//google analytics
				if(typeof ga !== typeof undefined)
				{
					var dy_vars = checkout_vars();
					var eventArgs = {};
					eventArgs.eventAction = 'Click';
					eventArgs.eventLabel = 'Bank';
					eventArgs.eventCategory = 'Gateway';
					ga('send', 'event', eventArgs);	
				}				
				
			});
		});
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}	
}