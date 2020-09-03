<?php

class paypal_me{
	
	function __construct()
	{
		$this->gateway_name = 'paypal_me';
		$this->gateway_title = 'Paypal';
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
			
			add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
			add_filter('dy_request_the_title', array(&$this, 'title'), 101);
			add_filter('get_the_excerpt', array(&$this, 'filter_excerpt'), 101);
			add_filter('wp_headers', array(&$this, 'send_data'));
			add_filter('gateway_buttons', array(&$this, 'button'), 2);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 2);
			add_filter('gateway_icons', array(&$this, 'icon'), 2);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 102);
		}		
	}

	public function send_data()
	{		
		if(dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			global $dy_valid_recaptcha;

			if(isset($dy_valid_recaptcha))
			{
				add_filter('dy_email_message', array(&$this, 'message'));
			}
		}

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
			$title = esc_html(__('Thank you for choosing Paypal', 'dynamicpackages'));
		}
		return $title;
	}
	
	
	public function is_active()
	{
		$output = false;
		global $paypal_me_is_active;
		
		if(isset($paypal_me_is_active))
		{
			$output = true;
		}
		else
		{
			if(get_option($this->gateway_name) != '')
			{
				$GLOBALS['paypal_me_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public function show()
	{
		$output = false;
		global $paypal_me_show;
		
		if(isset($paypal_me_show))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$GLOBALS['paypal_me_show'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public function is_valid_request()
	{
		$output = false;
		global $paypal_is_valid_request;
		
		if(isset($paypal_is_valid_request))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_request']) && isset($_POST['total']))
			{
				if($_POST['dy_request'] == $this->gateway_name && intval($_POST['total']) > 1)
				{
					$GLOBALS['paypal_is_valid_request'] = true;
					$output = true;
				}
			}		
		}
		
		return $output;
	}
	public function redirect()
	{
		$paypal_url = 'https://paypal.me/'.get_option($this->gateway_name).'/'.esc_html($_POST['total']);
		wp_redirect(esc_url($paypal_url));
		exit;		
	}
	public function is_valid()
	{
		$output = false;
		global $paypal_me_is_valid;
		
		if(isset($paypal_me_is_valid))
		{
			return true;
		}
		else
		{
			if($this->is_active() && !isset($_GET['quote']))
			{
				$max = floatval(get_option(sanitize_title('paypal_me_max')));
				$show = intval(get_option(sanitize_title('paypal_me_show')));
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
				$GLOBALS['paypal_me_is_valid'] = true;
			}
		}
		return $output;
	}

	public function settings_init()
	{
		//paypal.me
		
		register_setting('paypal_me_settings', $this->gateway_name, 'sanitize_user');
		register_setting('paypal_me_settings', 'paypal_me_show', 'intval');
		register_setting('paypal_me_settings', 'paypal_me_max', 'floatval');
		
		add_settings_section(
			'paypal_me_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'paypal_me_settings'
		);
		
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'Username', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			'paypal_me_settings', 
			'paypal_me_settings_section', $this->gateway_name
		);	
		add_settings_field( 
			'paypal_me_max', 
			esc_html(__( 'Max. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			'paypal_me_settings', 
			'paypal_me_settings_section', 'paypal_me_max'
		);
		add_settings_field( 
			'paypal_me_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'display_paypal_me_show'), 
			'paypal_me_settings', 
			'paypal_me_settings_section'
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
	public function display_paypal_me_show() { ?>
		<select name='paypal_me_show'>
			<option value="0" <?php selected(get_option('paypal_me_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option('paypal_me_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
		</select>
	<?php }	

	public function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', 'Paypal.me', 'Paypal.me', 'manage_options', $this->gateway_name, array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html(_e("Paypal.me", "dynamicpackages")); ?></h1>	
			<?php
			settings_fields( 'paypal_me_settings' );
			do_settings_sections( 'paypal_me_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}	
	public function button($output)
	{
		if($this->show() && in_array($this->gateway_title, $this->list_gateways_cb()))
		{
			$output .= ' <button class="pure-button bottom-20 pure-button-paypal withpaypal rounded" type="button"><i class="fab fa-paypal"></i> '.esc_html(__('Pay with Paypal', 'dynamicpackages')).'</button>';
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
			.pure-button.pure-button-paypal, .pure-button-paypal
			{
				color: #000;
				background-color: gold;
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
			$('.withpaypal').click(function(){
				var paypal_logo = $('<img>').attr({'src': dy_url()+'gateways/matrix/paypal/paypal.svg'});
				$(paypal_logo).attr({'width': '205', 'height': '50'});
				$('#dy_checkout_form').addClass('hidden');
				$('#dynamic_form').removeClass('hidden');
				$('#dy_form_icon').html(paypal_logo);
				$('#dynamic_form').find('input[name="name"]').focus();
				$('#dynamic_form').find('input[name="dy_request"]').val('<?php echo esc_html($this->gateway_name); ?>');
				
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
	public function icon($icon)
	{
		if($this->show() && in_array($this->gateway_title, $this->list_gateways_cb()))
		{
			$icon .= ' <i class="fab fa-paypal"></i>';
		}
		return $icon;
	}
	
	public function message($message)
	{
		$total = number_format(sanitize_text_field($_POST['total']), 2, '.', '');
		$url = 'https://paypal.me/'.get_option($this->gateway_name).'/'.$total;
		$amount = number_format($total, 2, '.', ',');
		$amount = dy_utilities::currency_symbol().''.$amount;
		
		$label = __('full payment of', 'dynamicpackages');
		
		if(dy_Validators::has_deposit())
		{
			$label = __('deposit of', 'dynamicpackages');
		}	
		
		$message .= '<p class="large">'.esc_html(__('To complete the booking please click on the following link and enter your Paypal account.', 'dynamicpackages')).'</p>';
		
		$message .= '<p class="large">'.esc_html(__('You will be paying a ', 'dynamicpackages').' '.$label.' '.$amount).'</p>';
		
		$message .= '<p class="large dy_pad">'.esc_html(__('Once we receive the payment your booking will be completed this way', 'dynamicpackages')).': <strong>'.sanitize_text_field($_POST['description']).'</strong></p>';
		
		$message .= '<p class="large"><a class="pure-button pure-button-paypal" target="_blank" href="'.esc_url($url).'"><i class="fab fa-paypal"></i> '.esc_html(__('Pay with Paypal', 'dynamicpackages').' '.__('now', 'dynamicpackages')).'</a></p>';

		return $message;
	}	
}