<?php

class nequi_direct{
	
	function __construct()
	{
		if(is_admin())
		{
			add_action( 'admin_init', array('nequi_direct', 'settings_init'), 1);
			add_action('admin_menu', array('nequi_direct', 'add_settings_page'), 100);			
		}
		else
		{
			add_filter('the_content', array('nequi_direct', 'filter_content'), 101);
			add_filter('the_title', array('nequi_direct', 'title'), 101);
			add_filter('pre_get_document_title', array('nequi_direct', 'title'), 101);
			add_filter('get_the_excerpt', array('nequi_direct', 'filter_excerpt'), 101);
			add_filter('gateway_buttons', array('nequi_direct', 'button'), 3);
			add_filter('list_gateways', array('nequi_direct', 'add_gateway'), 3);
			add_action('wp_enqueue_scripts', array('nequi_direct', 'scripts'), 101);
			add_filter('coupon_gateway', array('nequi_direct', 'single_coupon'), 10, 3);
			add_filter('coupon_gateway_hide', array('nequi_direct', 'single_coupon_hide'), 10, 3);
		}
	}
	public static function is_active()
	{
		$output = false;
		global $nequi_direct_is_active;
		
		if(isset($nequi_direct_is_active))
		{
			$output = true;
		}
		else
		{
			if(get_option('nequi_direct') != '')
			{
				$GLOBALS['nequi_direct_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public static function show_nequi()
	{
		$output = false;
		global $nequi_direct_show_nequi;
		
		if(isset($nequi_direct_show_nequi))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && self::is_active())
			{
				if(self::is_valid())
				{
					$GLOBALS['nequi_direct_show_nequi'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public static function is_valid_request()
	{
		$output = false;
		global $nequi_is_valid_request;
		
		if(isset($nequi_is_valid_request))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_platform']) && isset($_POST['total']))
			{
				if($_POST['dy_platform'] == 'nequi_direct' && intval($_POST['total']) > 1)
				{
					$GLOBALS['nequi_is_valid_request'] = true;
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
	public static function title($title)
	{
		if(in_the_loop() && dynamicpackages_Validators::validate_quote() && self::is_valid_request())
		{
			$title = esc_html(__('Thank you for using Nequi', 'dynamicpackages'));
		}
		return $title;
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
	
	public static function message()
	{
		
		$first = __('To complete the booking please enter your Nequi App and send the', 'dynamicpackages');
		$last = __('to the number', 'dynamicpackages');
		$amount = dy_utilities::currency_symbol().number_format(sanitize_text_field($_POST['total']), 2, '.', ',');
		$label = __('payment', 'dynamicpackages');
		
		if(dynamicpackages_Validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}
		
		$output = '<p class="large">'.esc_html($first.' '.$label.' ('.$amount.') '.$last).' <strong>'.esc_html(get_option('nequi_direct')).'</strong>.</p>';
		
		$output .= '<p class="large dy_pad padding-10 strong">'.esc_html(__('Send', 'dynamicpackages').' '.$amount.' '.$last.' '.get_option('nequi_direct')).'</p>';
		
		$output .= '<p class="large">'.esc_html(__('Once we receive the payment your booking will be completed this way', 'dynamicpackages')).': <strong>'.sanitize_text_field($_POST['description']).'</strong></p>';
		
		return $output;
	}

	public static function is_valid()
	{
		$output = false;
		global $nequi_direct_is_valid;
		
		if(isset($nequi_direct_is_valid))
		{
			return true;
		}
		else
		{
			if(self::is_active() && !isset($_GET['quote']))
			{
				$max = floatval(get_option(sanitize_title('nequi_direct_max')));
				$show = intval(get_option(sanitize_title('nequi_direct_show')));
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
				
				if($total <= $max)
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
				$GLOBALS['nequi_direct_is_valid'] = true;
			}
		}
		return $output;
	}

	public static function settings_init()
	{
		//Nequi
		
		register_setting('nequi_direct_settings', 'nequi_direct', 'intval');
		register_setting('nequi_direct_settings', 'nequi_direct_show', 'intval');
		register_setting('nequi_direct_settings', 'nequi_direct_max', 'floatval');
		
		add_settings_section(
			'nequi_direct_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'nequi_direct_settings'
		);
		
		add_settings_field( 
			'nequi_direct', 
			esc_html(__( 'Nequi Cell Phone Number', 'dynamicpackages' )), 
			array('nequi_direct', 'input_number'), 
			'nequi_direct_settings', 
			'nequi_direct_settings_section', 'nequi_direct'
		);	
		add_settings_field( 
			'nequi_direct_max', 
			esc_html(__( 'Max. Amount', 'dynamicpackages' )), 
			array('nequi_direct', 'input_number'), 
			'nequi_direct_settings', 
			'nequi_direct_settings_section', 'nequi_direct_max'
		);
		add_settings_field( 
			'nequi_direct_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array('nequi_direct', 'display_nequi_direct_show'), 
			'nequi_direct_settings', 
			'nequi_direct_settings_section'
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
	public static function display_nequi_direct_show() { ?>
		<select name='nequi_direct_show'>
			<option value="0" <?php selected(get_option('nequi_direct_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option('nequi_direct_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
		</select>
	<?php }

	public static function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', 'Nequi', 'Nequi', 'manage_options', 'nequi_direct', array('nequi_direct', 'settings_page'));
	}
	public static function settings_page()
		 { 
		?><div class="wrap">
		<form action='options.php' method='post'>
			
			<h1><?php esc_html(_e("Nequi", "dynamicpackages")); ?></h1>	
			<?php
			settings_fields( 'nequi_direct_settings' );
			do_settings_sections( 'nequi_direct_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	public static function button($output)
	{
		if(self::show_nequi() && in_array('Nequi', self::list_gateways_cb()))
		{
			$output .= ' <button class="pure-button bottom-20 pure-button-nequi withnequi rounded" type="button"><img alt="nequi" width="12" height="12" src="'.esc_url(plugin_dir_url( __FILE__ ).'nequi-icon.svg').'"/> '.esc_html(__('Pay with Nequi', 'dynamicpackages')).'</button>';			
		}
		return $output;
	}
	public static function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}	
	public static function add_gateway($array)
	{
		if(self::show_nequi() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$array[] = 'Nequi';		
		}
		return $array;	
	}
	public static function scripts()
	{
		if(self::show_nequi())
		{
			wp_add_inline_style('minimalLayout', self::css());
			wp_add_inline_script('dynamicpackages', self::js(), 'before');	
		}
	}
	public static function css()
	{
		ob_start();
		?>
			.pure-button.pure-button-nequi, .pure-button-nequi
			{
				background-color: #ff2f73;
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
			$('.withnequi').click(function()
			{
				var nequi_logo = $('<img>').attr({'src': dy_url()+'gateways/matrix/nequi/nequi.svg'});
				$(nequi_logo).attr({'width': '214', 'height': '48'});
				$('#dynamic-checkout').addClass('hidden');
				$('#dynamic_form').removeClass('hidden');
				$('#dy_form_icon').html(nequi_logo);
				$('#dynamic_form').find('input[name="phone"]').attr({'min': '60000000', 'max': '69999999', 'type': 'number'});
				$('#dynamic_form').find('input[name="name"]').focus();
				$('#dynamic_form').find('input[name="dy_platform"]').val('nequi_direct');
				
				$('#dynamic_form').find('span.dy_mobile_payment').text('Nequi');
				
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
					eventArgs.eventLabel = 'Nequi';
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
	public static function single_coupon($str, $gateway)
	{
		if(strtolower($gateway) == 'nequi')
		{
			$str = '<aside class="dy_show_country dy_show_country_PA"><div class="pure-g gutters text-center"><div class="pure-u-1-5"><img style="vertical-align: middle" alt="nequi" width="80" class="img-responsive inline-block" src="'.esc_url(plugin_dir_url( __FILE__ ).'nequi.svg').'"/></div><div class="pure-u-4-5"><span class="semibold">'.esc_html(__('Pay with Nequi', 'dynamicpackages')).'.</span> '.$str.'</div></div></aside>';
		}
		
		return $str;
	}
	public static function single_coupon_hide($str, $gateway)
	{
		if(strtolower($gateway) == 'nequi')
		{
			$str = 'hidden';
		}
		return $str;
	}	
}