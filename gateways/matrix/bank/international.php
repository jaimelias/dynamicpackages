<?php

class wire_transfer{
	
	function __construct()
	{
		if(is_admin())
		{
			add_action( 'admin_init', array('wire_transfer', 'settings_init'), 1);
			add_action('admin_menu', array('wire_transfer', 'add_settings_page'), 102);			
		}
		else
		{
			add_filter('the_content', array('wire_transfer', 'filter_content'), 103);
			add_filter('the_title', array('wire_transfer', 'title'), 103);
			add_filter('pre_get_document_title', array('wire_transfer', 'title'), 103);
			add_filter('get_the_excerpt', array('wire_transfer', 'filter_excerpt'), 104);
			add_filter('gateway_buttons', array('wire_transfer', 'button'), 5);
			add_filter('list_gateways', array('wire_transfer', 'add_gateway'), 5);
			add_action('wp_enqueue_scripts', array('wire_transfer', 'scripts'), 103);
		}
	}
	public static function is_active()
	{
		$output = false;
		global $wire_transfer_is_active;
		
		if(isset($wire_transfer_is_active))
		{
			$output = true;
		}
		else
		{
			if(get_option('wire_transfer') != '')
			{
				$GLOBALS['wire_transfer_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public static function show_wire()
	{
		$output = false;
		global $wire_transfer_show_wire;
		
		if(isset($wire_transfer_show_wire))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && self::is_active())
			{
				if(self::is_valid())
				{
					$GLOBALS['wire_transfer_show_wire'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public static function is_valid_request()
	{
		$output = false;
		global $wire_is_valid_request;
		
		if(isset($wire_is_valid_request))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_platform']) && isset($_POST['total']))
			{
				if($_POST['dy_platform'] == 'wire_transfer' && intval($_POST['total']) > 1)
				{
					$GLOBALS['wire_is_valid_request'] = true;
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
			$title = esc_html(__('Pay With an International Wire Transfer', 'dynamicpackages'));
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
		
		$output .= '<div class="large dy_pad">'.self::account().'</div>';
		
		$output .= '<p class="large">'.esc_html(__('Once we receive the slip and payment your booking will be completed this way', 'dynamicpackages')).': <strong>'.sanitize_text_field($_POST['description']).'</strong></p>';
		
		return $output;
	}
	
	public static function account()
	{
		$wire = '<h3>'.esc_html(__('Beneficiary Bank', 'dynamicpackages')).'</h3><p>';
		
		if(get_option('wire_transfer_name') != '')
		{
			$wire .= esc_html(__('Beneficiary Bank Name', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_name')).'</strong><br/>';
		}
		
		if(get_option('wire_transfer_address') != '')
		{
			$wire .= esc_html(__('Beneficiary Bank Address', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_address')).'</strong><br/>';
		}

		if(get_option('wire_transfer_swift') != '')
		{
			$wire .= esc_html(__('Beneficiary Bank Swift', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_swift')).'</strong><br/>';
		}		
		
		if(get_option('wire_transfer_account') != '')
		{
			$wire .= esc_html(__('Beneficiary Account Name', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_account')).'</strong><br/>';
		}		
		
		if(get_option('wire_transfer') != '')
		{
			$wire .= esc_html(__('Beneficiary Account Number', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer')).'</strong><br/>';
		}
		
		if(get_option('wire_transfer_iban') != '')
		{
			$wire .= esc_html(__('Beneficiary Account IBAN', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_iban')).'</strong><br/>';
		}		
		
		$wire .= '</p><h3>'.esc_html(__('Intermediary Bank', 'dynamicpackages')).'</h3><p>';
		
		if(get_option('wire_transfer_name_i') != '')
		{
			$wire .= esc_html(__('Intermediary Bank Name', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_name_i')).'</strong><br/>';
		}
		
		if(get_option('wire_transfer_address_i') != '')
		{
			$wire .= esc_html(__('Intermediary Bank Address', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_address_i')).'</strong><br/>';
		}

		if(get_option('wire_transfer_swift_i') != '')
		{
			$wire .= esc_html(__('Intermediary Bank Swift', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_swift_i')).'</strong><br/>';
		}		
		
		if(get_option('wire_transfer_account_i') != '')
		{
			$wire .= esc_html(__('Intermediary Account Name', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_account_i')).'</strong><br/>';
		}		
		
		if(get_option('wire_transfer_i') != '')
		{
			$wire .= esc_html(__('Intermediary Account Number', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_i')).'</strong><br/>';
		}
		
		if(get_option('wire_transfer_iban_i') != '')
		{
			$wire .= esc_html(__('Intermediary Account IBAN', 'dynamicpackages')).': <strong>'.esc_html(get_option('wire_transfer_iban_i')).'</strong><br/>';
		}
		
		$wire .= '</p>';
		
		return $wire;
	}

	public static function is_valid()
	{
		$output = false;
		global $wire_transfer_is_valid;
		
		if(isset($wire_transfer_is_valid))
		{
			return true;
		}
		else
		{
			if(self::is_active() && !isset($_GET['quote']))
			{
				$min = floatval(get_option(sanitize_title('wire_transfer_min')));
				$show = intval(get_option(sanitize_title('wire_transfer_show')));
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
				$GLOBALS['wire_transfer_is_valid'] = true;
			}
		}
		return $output;
	}

	public static function settings_init()
	{
		//Beneficiary
		register_setting('wire_transfer_settings', 'wire_transfer', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_account', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_name', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_address', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_swift', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_iban', 'sanitize_text_field');
		
		//Intermediary
		register_setting('wire_transfer_settings', 'wire_transfer_i', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_account_i', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_name_i', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_address_i', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_swift_i', 'sanitize_text_field');
		register_setting('wire_transfer_settings', 'wire_transfer_iban_i', 'sanitize_text_field');		
		
		//controls
		register_setting('wire_transfer_settings', 'wire_transfer_show', 'intval');
		register_setting('wire_transfer_settings', 'wire_transfer_min', 'floatval');
		
		
		//section
		add_settings_section(
			'wire_transfer_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'wire_transfer_settings'
		);		
		
		//section
		add_settings_section(
			'wire_transfer_beneficiary_section', 
			esc_html(__( 'Beneficiary Bank Settings', 'dynamicpackages' )), 
			'', 
			'wire_transfer_settings'
		);
		
		//section
		add_settings_section(
			'wire_transfer_intermediary_section', 
			esc_html(__( 'Intermediary Bank Settings', 'dynamicpackages' )), 
			'', 
			'wire_transfer_settings'
		);		
		
		
		
		//Beneficiary		
		add_settings_field( 
			'wire_transfer_name', 
			esc_html(__( 'Beneficiary Bank Name', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_beneficiary_section', 'wire_transfer_name'
		);
		add_settings_field( 
			'wire_transfer_address', 
			esc_html(__( 'Beneficiary Bank Address', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_beneficiary_section', 'wire_transfer_address'
		);
		add_settings_field( 
			'wire_transfer_swift', 
			esc_html(__( 'Beneficiary Bank Swift', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_beneficiary_section', 'wire_transfer_swift'
		);				
		add_settings_field( 
			'wire_transfer_account', 
			esc_html(__( 'Beneficiary Account Name', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_beneficiary_section', 'wire_transfer_account'
		);			
		
		add_settings_field( 
			'wire_transfer', 
			esc_html(__( 'Beneficiary Account Number', 'dynamicpackages' )), 
			array('wire_transfer', 'input_number'), 
			'wire_transfer_settings', 
			'wire_transfer_beneficiary_section', 'wire_transfer'
		);
		add_settings_field( 
			'wire_transfer_iban', 
			esc_html(__( 'Beneficiary Account IBAN', 'dynamicpackages' )), 
			array('wire_transfer', 'input_number'), 
			'wire_transfer_settings', 
			'wire_transfer_beneficiary_section', 'wire_transfer_iban'
		);	
		
		//Intermediary			
		add_settings_field( 
			'wire_transfer_name_i', 
			esc_html(__( 'Intermediary Bank Name', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_intermediary_section', 'wire_transfer_name_i'
		);
		add_settings_field( 
			'wire_transfer_address_i', 
			esc_html(__( 'Intermediary Bank Address', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_intermediary_section', 'wire_transfer_address_i'
		);
		add_settings_field( 
			'wire_transfer_swift_i', 
			esc_html(__( 'Intermediary Bank Swift', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_intermediary_section', 'wire_transfer_swift_i'
		);		
		add_settings_field( 
			'wire_transfer_account_i', 
			esc_html(__( 'Intermediary Account Name', 'dynamicpackages' )), 
			array('wire_transfer', 'input_text'), 
			'wire_transfer_settings', 
			'wire_transfer_intermediary_section', 'wire_transfer_account_i'
		);			
		
		add_settings_field( 
			'wire_transfer_i', 
			esc_html(__( 'Intermediary Account Number', 'dynamicpackages' )), 
			array('wire_transfer', 'input_number'), 
			'wire_transfer_settings', 
			'wire_transfer_intermediary_section', 'wire_transfer_i'
		);
		add_settings_field( 
			'wire_transfer_iban_i', 
			esc_html(__( 'Intermediary Account IBAN', 'dynamicpackages' )), 
			array('wire_transfer', 'input_number'), 
			'wire_transfer_settings', 
			'wire_transfer_intermediary_section', 'wire_transfer_iban_i'
		);			

		//controls
		add_settings_field( 
			'wire_transfer_min', 
			esc_html(__( 'Min. Amount', 'dynamicpackages' )), 
			array('wire_transfer', 'input_number'), 
			'wire_transfer_settings', 
			'wire_transfer_control_section', 'wire_transfer_min'
		);
		add_settings_field( 
			'wire_transfer_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array('wire_transfer', 'display_wire_transfer_show'), 
			'wire_transfer_settings', 
			'wire_transfer_control_section'
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
	
	public static function display_wire_transfer_show() { ?>
		<select name='wire_transfer_show'>
			<option value="0" <?php selected(get_option('wire_transfer_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option('wire_transfer_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
		</select>
	<?php }	

	public static function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', 'Wire', 'Wire', 'manage_options', 'wire_transfer', array('wire_transfer', 'settings_page'));
	}
	public static function settings_page()
		 { 
		?><div class="wrap">
		<form action='options.php' method='post'>
			
			<h1><?php echo esc_html(__('Wire Transfer', 'dynamicpackages')); ?></h1>	
			<?php
			settings_fields( 'wire_transfer_settings' );
			do_settings_sections( 'wire_transfer_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	public static function button($output)
	{
		if(self::show_wire() && in_array(self::gateway_name(), self::list_gateways_cb()))
		{
			$output .= ' <button class="pure-button bottom-20 pure-button-wire  withwire rounded" type="button"><i class="fas fa-globe"></i> '.esc_html(__('Wire Transfer', 'dynamicpackages')).'</button>';			
		}
		return $output;
	}
	public static function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	public static function gateway_name()
	{
		return __('International Wire Transfer', 'dynamicpackages');
	}
	public static function add_gateway($array)
	{
		if(self::show_wire() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$array[] = self::gateway_name();
		}
		return $array;	
	}
	public static function scripts()
	{
		if(self::show_wire())
		{
			wp_add_inline_style('minimalLayout', self::css());
			wp_add_inline_script('dynamicpackages', self::js(), 'before');	
		}
	}
	public static function css()
	{
		ob_start();
		?>
			.pure-button.pure-button-wire, .pure-button-wire
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
			$('.withwire').click(function()
			{
				var wire_logo = $('<span style="font-size: 3em;"></span>').addClass('text-muted');
				wire_logo.append('<i class="fas fa-globe"></i>');
				$('#dynamic-checkout').addClass('hidden');
				$('#dynamic_form').removeClass('hidden');
				$('#dy_form_icon').html(wire_logo);
				$('#dynamic_form').find('input[name="name"]').focus();
				$('#dynamic_form').find('input[name="dy_platform"]').val('wire_transfer');
				
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
					eventArgs.eventLabel = 'Wire';
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