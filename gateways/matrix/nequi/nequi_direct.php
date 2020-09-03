<?php

class nequi_direct{
	
	function __construct()
	{
		$this->gateway_name = 'nequi_direct';
		$this->gateway_title = 'Nequi';
		$this->init();
	}
	public function init()
	{
		if(is_admin())
		{
			add_action('admin_init', array(&$this, 'settings_init'), 1);
			add_action('admin_menu', array(&$this, 'add_settings_page'), 100);			
		}
		else
		{
			add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
			add_filter('dy_request_the_title', array(&$this, 'title'), 101);
			add_filter('get_the_excerpt', array(&$this, 'filter_excerpt'), 101);
			add_filter('wp_headers', array(&$this, 'send_data'));
			add_filter('gateway_buttons', array(&$this, 'button'), 3);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 3);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 101);
			add_filter('coupon_gateway', array(&$this, 'single_coupon'), 10, 3);
			add_filter('coupon_gateway_hide', array(&$this, 'single_coupon_hide'), 10, 3);
		}		
	}

	public function send_data()
	{		
		if(dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			global $dy_valid_recaptcha;

			if(isset($dy_valid_recaptcha))
			{
				add_filter('dy_email_notes', array(&$this, 'message'));
			}
		}

	}

	public function is_active()
	{
		$output = false;
		global $nequi_direct_is_active;
		
		if(isset($nequi_direct_is_active))
		{
			$output = true;
		}
		else
		{
			if(get_option($this->gateway_name) != '')
			{
				$GLOBALS['nequi_direct_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public function show()
	{
		$output = false;
		global $nequi_direct_show;
		
		if(isset($nequi_direct_show))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$GLOBALS['nequi_direct_show'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public function is_valid_request()
	{
		$output = false;
		global $nequi_is_valid_request;
		
		if(isset($nequi_is_valid_request))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_request']) && isset($_POST['total']))
			{
				if($_POST['dy_request'] == $this->gateway_name && intval($_POST['total']) > 1)
				{
					$GLOBALS['nequi_is_valid_request'] = true;
					$output = true;
				}
			}		
		}
		
		return $output;
	}
	public function filter_excerpt($excerpt)
	{
		if(in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			$excerpt = esc_html(__('Hello', 'dynamicpackages').' '.sanitize_text_field($_POST['first_name']).',');
		}
		return $excerpt;
	}
	public function filter_content($content)
	{
		if(in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			global $dy_valid_recaptcha;

			if(isset($dy_valid_recaptcha))
			{
				$content = $this->message(null);
			}
			else
			{
				$content = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Recaptcha', 'dynamicpackages')).'</strong></p>';
			}				
		}
		return $content;
	}
	public function title($title)
	{
		if(in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			$title = esc_html(__('Thank you for using Nequi', 'dynamicpackages'));
		}
		return $title;
	}
	
	public function message($message)
	{
		
		$first = __('To complete the booking please enter your Nequi App and send the', 'dynamicpackages');
		$last = __('to the number', 'dynamicpackages');
		$amount = dy_utilities::currency_symbol().number_format(sanitize_text_field($_POST['total']), 2, '.', ',');
		$label = __('payment', 'dynamicpackages');
		
		if(dy_Validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}
		
		$message = '<p class="large">'.esc_html($first.' '.$label.' ('.$amount.') '.$last).' <strong>'.esc_html(get_option($this->gateway_name)).'</strong>.</p>';
		
		$message .= '<p class="large dy_pad padding-10 strong">'.esc_html(__('Send', 'dynamicpackages').' '.$amount.' '.$last.' '.get_option($this->gateway_name)).'</p>';
		
		$message .= '<p class="large">'.esc_html(__('Once we receive the payment your booking will be completed this way', 'dynamicpackages')).': <strong>'.sanitize_text_field($_POST['description']).'</strong></p>';
		
		return $message;
	}

	public function is_valid()
	{
		$output = false;
		global $nequi_direct_is_valid;
		
		if(isset($nequi_direct_is_valid))
		{
			return true;
		}
		else
		{
			if($this->is_active() && !isset($_GET['quote']))
			{
				$max = floatval(get_option(sanitize_title('nequi_direct_max')));
				$show = intval(get_option(sanitize_title('nequi_direct_show')));
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

				if(dy_Validators::has_deposit())
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
						if(dy_Validators::has_deposit())
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

	public function settings_init()
	{
		//Nequi
		
		register_setting('nequi_direct_settings', $this->gateway_name, 'intval');
		register_setting('nequi_direct_settings', 'nequi_direct_show', 'intval');
		register_setting('nequi_direct_settings', 'nequi_direct_max', 'floatval');
		
		add_settings_section(
			'nequi_direct_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'nequi_direct_settings'
		);
		
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'Nequi Cell Phone Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			'nequi_direct_settings', 
			'nequi_direct_settings_section', $this->gateway_name
		);	
		add_settings_field( 
			'nequi_direct_max', 
			esc_html(__( 'Max. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			'nequi_direct_settings', 
			'nequi_direct_settings_section', 'nequi_direct_max'
		);
		add_settings_field( 
			'nequi_direct_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'display_nequi_direct_show'), 
			'nequi_direct_settings', 
			'nequi_direct_settings_section'
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
	public function display_nequi_direct_show() { ?>
		<select name='nequi_direct_show'>
			<option value="0" <?php selected(get_option('nequi_direct_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option('nequi_direct_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
		</select>
	<?php }

	public function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', $this->gateway_title, $this->gateway_title, 'manage_options', $this->gateway_name, array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html(_e($this->gateway_title, "dynamicpackages")); ?></h1>	
			<?php
			settings_fields( 'nequi_direct_settings' );
			do_settings_sections( 'nequi_direct_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	public function button($output)
	{
		if($this->show() && in_array($this->gateway_title, $this->list_gateways_cb()))
		{
			$output .= ' <button class="pure-button bottom-20 pure-button-nequi withnequi rounded" type="button"><img alt="nequi" width="12" height="12" src="'.esc_url(plugin_dir_url( __FILE__ ).'nequi-icon.svg').'"/> '.esc_html(__('Pay with Nequi', 'dynamicpackages')).'</button>';			
		}
		return $output;
	}
	public function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}	

	public function add_gateway($array)
	{
		global $dy_valid_recaptcha;
		$add = false;
		
		if($this->show() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$add = true;
		}
		
		if(isset($dy_valid_recaptcha) && isset($_POST['dy_request']) && dy_Validators::is_request_valid())
		{
			if($_POST['dy_request'] == 'request')
			{
				$add = true;
			}	
		}		
		
		if($add)
		{
			$array[] = $this->gateway_title;
		}
		
		return $array;	
	}

	public function scripts()
	{
		if($this->show())
		{
			wp_add_inline_style('minimalLayout', $this->css());
			wp_add_inline_script('dynamicpackages', $this->js(), 'before');	
		}
	}
	public function css()
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
	public function js()
	{
		ob_start();
		?>
		$(function(){
			$('.withnequi').click(function()
			{
				var nequi_logo = $('<img>').attr({'src': dy_url()+'gateways/matrix/nequi/nequi.svg'});
				$(nequi_logo).attr({'width': '214', 'height': '48'});
				$('#dy_checkout_form').addClass('hidden');
				$('#dynamic_form').removeClass('hidden');
				$('#dy_form_icon').html(nequi_logo);
				$('#dynamic_form').find('input[name="phone"]').attr({'min': '60000000', 'max': '69999999', 'type': 'number'});
				$('#dynamic_form').find('input[name="name"]').focus();
				$('#dynamic_form').find('input[name="dy_request"]').val('<?php echo esc_html($this->gateway_name); ?>');
				
				$('#dynamic_form').find('span.dy_mobile_payment').text('<?php echo esc_html($this->gateway_title); ?>');
				
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
					eventArgs.eventLabel = '<?php echo esc_html($this->gateway_name); ?>';
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
		if(strtolower($gateway) == 'nequi')
		{
			$str = '<aside class="dy_show_country dy_show_country_PA"><div class="pure-g gutters text-center"><div class="pure-u-1-5"><img style="vertical-align: middle" alt="nequi" width="80" class="img-responsive inline-block" src="'.esc_url(plugin_dir_url( __FILE__ ).'nequi.svg').'"/></div><div class="pure-u-4-5"><span class="semibold">'.esc_html(__('Pay with Nequi', 'dynamicpackages')).'.</span> '.$str.'</div></div></aside>';
		}
		
		return $str;
	}
	public function single_coupon_hide($str, $gateway)
	{
		if(strtolower($gateway) == 'nequi')
		{
			$str = 'hidden';
		}
		return $str;
	}	
}