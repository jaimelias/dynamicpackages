<?php

class yappy_direct{
	
	function __construct()
	{
		$this->gateway_name = 'yappy_direct';
		$this->init();
	}
	public function init()
	{
		if(is_admin())
		{
			add_action( 'admin_init', array(&$this, 'settings_init'), 1);
			add_action('admin_menu', array(&$this, 'add_settings_page'), 100);	
		}
		else
		{
			add_filter('the_content', array(&$this, 'filter_content'), 101);
			add_filter('the_title', array(&$this, 'title'), 101);
			add_filter('pre_get_document_title', array(&$this, 'title'), 101);
			add_filter('get_the_excerpt', array(&$this, 'filter_excerpt'), 101);
			add_filter('gateway_buttons', array(&$this, 'button'), 3);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 3);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 101);
			add_filter('coupon_gateway', array(&$this, 'single_coupon'), 10, 3);
			add_filter('coupon_gateway_hide', array(&$this, 'single_coupon_hide'), 10, 3);
		}		
	}
	public function is_active()
	{
		$output = false;
		global $yappy_direct_is_active;
		
		if(isset($yappy_direct_is_active))
		{
			$output = true;
		}
		else
		{
			if(get_option($this->gateway_name) != '')
			{
				$GLOBALS['yappy_direct_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public function show_yappy()
	{
		$output = false;
		global $yappy_direct_show_yappy;
		
		if(isset($yappy_direct_show_yappy))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$GLOBALS['yappy_direct_show_yappy'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public function is_valid_request()
	{
		$output = false;
		global $yappy_is_valid_request;
		
		if(isset($yappy_is_valid_request))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_platform']) && isset($_POST['total']))
			{
				if($_POST['dy_platform'] == $this->gateway_name && intval($_POST['total']) > 1)
				{
					$GLOBALS['yappy_is_valid_request'] = true;
					$output = true;
				}
			}		
		}
		
		return $output;
	}
	public function filter_excerpt($excerpt)
	{
		if(in_the_loop() && dynamicpackages_Validators::validate_quote() && $this->is_valid_request())
		{
			$excerpt = esc_html(__('Hello', 'dynamicpackages').' '.sanitize_text_field($_POST['fname']).',');
		}
		return $excerpt;
	}
	public function filter_content($content)
	{
		if(in_the_loop() && dynamicpackages_Validators::validate_quote() && $this->is_valid_request())
		{
			if(dynamicpackages_Validators::validate_recaptcha())
			{
				dynamicpackages_Checkout::webhook('dy_quote_webhook', json_encode($_POST));
				$content = $this->message();
				$this->send();
			}
		}
		return $content;
	}
	public function title($title)
	{
		if(in_the_loop() && dynamicpackages_Validators::validate_quote() && $this->is_valid_request())
		{
			$title = esc_html(__('Thank you for using Yappy', 'dynamicpackages'));
		}
		return $title;
	}
	
	public function send()
	{
		
		$admin_email = get_option('admin_email');
		$headers = array('Content-type: text/html');
		$admin_subject = sanitize_text_field($_POST['fname']).' '.__('attempts to pay using', 'dynamicpackages').' '. sanitize_text_field($_POST['dy_platform']);
		$admin_body = '<p>'.$admin_subject.'</p>'.sanitize_text_field($_POST['description']);
		$admin_body .= '<p>'.__('Name', 'dynamicpackages').': '.sanitize_text_field($_POST['fname']).' '.sanitize_text_field($_POST['lastname']).'<br/>';
		$admin_body .= __('Email', 'dynamicpackages').': '.sanitize_text_field($_POST['email']).'<br/>';
		$admin_body .= __('Phone', 'dynamicpackages').': '.sanitize_text_field($_POST['phone']).'</p>';
		
		$user_subject = __('Payment Instructions', 'dynamicpackages').' - '.get_bloginfo('name');
		$user_body = '<p>'.__('Hello', 'dynamicpackages').' '.sanitize_text_field($_POST['fname']).',</p>'.$this->message();
		
		wp_mail($admin_email, $admin_subject, $admin_body, $headers);
		wp_mail(sanitize_email($_POST['email']), $user_subject, $user_body, $headers);
	}
	
	public function message()
	{
		
		$first = __('To complete the booking please enter your Yappy App and send the', 'dynamicpackages');
		$last = __('to the number', 'dynamicpackages');
		$amount = dy_utilities::currency_symbol().number_format(sanitize_text_field($_POST['total']), 2, '.', ',');
		$label = __('payment', 'dynamicpackages');
		
		if(dynamicpackages_Validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}
		
		$output = '<p class="large">'.esc_html($first.' '.$label.' ('.$amount.') '.$last).' <strong>'.esc_html(get_option($this->gateway_name)).'</strong>.</p>';
		
		$output .= '<p class="large dy_pad padding-10 strong">'.esc_html(__('Send', 'dynamicpackages').' '.$amount.' '.$last.' '.get_option($this->gateway_name)).'</p>';
		
		$output .= '<p class="large">'.esc_html(__('Once we receive the payment your booking will be completed this way', 'dynamicpackages')).': <strong>'.sanitize_text_field($_POST['description']).'</strong></p>';
		
		return $output;
	}

	public function is_valid()
	{
		$output = false;
		global $yappy_direct_is_valid;
		
		if(isset($yappy_direct_is_valid))
		{
			return true;
		}
		else
		{
			if($this->is_active() && !isset($_GET['quote']))
			{
				$max = floatval(get_option(sanitize_title('yappy_direct_max')));
				$show = intval(get_option(sanitize_title('yappy_direct_show')));
				$payment = package_field('package_payment');
				$deposit = floatval(dy_utilities::get_deposit());
				
				if(is_booking_page())
				{
					$total = dy_utilities::total();
				}
				else
				{
					$total = floatval(dy_utilities::starting_at());
					
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
				$GLOBALS['yappy_direct_is_valid'] = true;
			}
		}
		return $output;
	}

	public function settings_init()
	{
		//Yappy
		
		register_setting('yappy_direct_settings', $this->gateway_name, 'intval');
		register_setting('yappy_direct_settings', 'yappy_direct_show', 'intval');
		register_setting('yappy_direct_settings', 'yappy_direct_max', 'floatval');
		
		add_settings_section(
			'yappy_direct_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'yappy_direct_settings'
		);
		
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'Yappy Cell Phone Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			'yappy_direct_settings', 
			'yappy_direct_settings_section', $this->gateway_name
		);	
		add_settings_field( 
			'yappy_direct_max', 
			esc_html(__( 'Max. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			'yappy_direct_settings', 
			'yappy_direct_settings_section', 'yappy_direct_max'
		);
		add_settings_field( 
			'yappy_direct_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'display_yappy_direct_show'), 
			'yappy_direct_settings', 
			'yappy_direct_settings_section'
		);		
	}
	
	public function input_text($name){
		$option = get_option($name);
		?>
		<input type="text" name="<?php echo esc_html($name); ?>" id="<?php echo esc_html($name); ?>" value="<?php echo esc_html($option); ?>" />
		<?php
	}
	public function input_number($name){
		$option = get_option($name);
		?>
		<input type="number" name="<?php echo esc_html($name); ?>" id="<?php echo esc_html($name); ?>" value="<?php echo esc_html($option); ?>" /> #
		<?php
	}
	public function display_yappy_direct_show() { ?>
		<select name='yappy_direct_show'>
			<option value="0" <?php selected(get_option('yappy_direct_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option('yappy_direct_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
		</select>
	<?php }

	public function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', 'Yappy', 'Yappy', 'manage_options', $this->gateway_name, array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action='options.php' method='post'>
			
			<h1><?php esc_html(_e("Yappy", "dynamicpackages")); ?></h1>	
			<?php
			settings_fields( 'yappy_direct_settings' );
			do_settings_sections( 'yappy_direct_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	public function button($output)
	{
		if($this->show_yappy() && in_array('Yappy', $this->list_gateways_cb()))
		{
			$output .= ' <button class="pure-button bottom-20 pure-button-yappy withyappy rounded" type="button"><img alt="yappy" width="21" height="12" src="'.esc_url(plugin_dir_url( __FILE__ ).'yappy-icon.svg').'"/> '.esc_html(__('Pay with Yappy', 'dynamicpackages')).'</button>';			
		}
		return $output;
	}
	public function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}	
	public function add_gateway($array)
	{
		if($this->show_yappy() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$array[] = 'Yappy';		
		}
		return $array;	
	}
	public function scripts()
	{
		if($this->show_yappy())
		{
			wp_add_inline_style('minimalLayout', $this->css());
			wp_add_inline_script('dynamicpackages', $this->js(), 'before');	
		}
	}
	public function css()
	{
		ob_start();
		?>
			.pure-button.pure-button-yappy, .pure-button-yappy
			{
				background-color: #013685;
				color: #fff;
			}
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}
	public function js()
	{
		ob_start();
		?>
		$(function(){
			$('.withyappy').click(function()
			{
				var yappy_logo = $('<img>').attr({'src': dy_url()+'gateways/matrix/yappy/yappy.svg'});
				$(yappy_logo).attr({'width': '80', 'height': '69'});
				$('#dynamic-checkout').addClass('hidden');
				$('#dynamic_form').removeClass('hidden');
				$('#dy_form_icon').html(yappy_logo);
				$('#dynamic_form').find('input[name="phone"]').attr({'min': '60000000', 'max': '69999999', 'type': 'number'});
				$('#dynamic_form').find('input[name="name"]').focus();
				$('#dynamic_form').find('input[name="dy_platform"]').val('<?php echo $this->gateway_name ?>');
				
				$('#dynamic_form').find('span.dy_mobile_payment').text('Yappy');
				
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
					eventArgs.eventLabel = 'Yappy';
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
	
	public function single_coupon($str, $gateway)
	{
		if(strtolower($gateway) == 'yappy')
		{
			$str = '<aside class="dy_show_country dy_show_country_PA"><div class="pure-g gutters text-center"><div class="pure-u-1-5"><img style="vertical-align: middle" width="40" alt="yappy" class="img-responsive inline-block" src="'.esc_url(plugin_dir_url( __FILE__ ).'yappy.svg').'"/></div><div class="pure-u-4-5"><span class="semibold">'.esc_html(__('Pay with Yappy', 'dynamicpackages')).'.</span> '.$str.'</div></div></aside>';
		}
		
		return $str;
	}
	public function single_coupon_hide($str, $gateway)
	{
		if(strtolower($gateway) == 'yappy')
		{
			$str = 'hidden';
		}
		return $str;
	}
}