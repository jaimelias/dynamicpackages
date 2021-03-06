<?php

class yappy_direct{
	
	function __construct()
	{
		$this->init();
	}
	public function init()
	{
		add_action('init', array(&$this, 'args'));
		
		if(is_admin())
		{
			add_action( 'admin_init', array(&$this, 'settings_init'), 1);
			add_action('admin_menu', array(&$this, 'add_settings_page'), 100);	
		}
		else
		{
			
			add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
			add_filter('dy_request_the_title', array(&$this, 'title'), 101);
			add_filter('wp_headers', array(&$this, 'send_data'));
			add_filter('gateway_buttons', array(&$this, 'button'), 3);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 3);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 101);
			add_filter('coupon_gateway', array(&$this, 'single_coupon'), 10, 3);
		}		
	}
	
	public function args()
	{
		$this->gateway_title = 'Yappy';
		$this->gateway_name = 'yappy_direct';		
		$this->gateway_short_name = 'yappy';		
		$this->number = get_option($this->gateway_name);
		$this->max = get_option($this->gateway_name . '_max');
		$this->show = get_option($this->gateway_name . '_show');
		$this->color = '#fff';
		$this->background_color = '#013685';
	}
	
	public function send_data()
	{		
		if(dy_validators::is_request_valid() && $this->is_valid_request())
		{
			global $dy_valid_recaptcha;

			if(isset($dy_valid_recaptcha))
			{
				add_filter('dy_email_notes', array(&$this, 'message'));
				add_filter('dy_email_label_notes', array(&$this, 'label_notes'));
			}
		}

	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->gateway_title);
	}	
	
	public function is_active()
	{
		$output = false;
		$which_var = $this->gateway_name . '_is_active';
		
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = true;
		}
		else
		{
			if($this->number != '')
			{
				$GLOBALS[$which_var] = true;
				$output = true;
			}
		}
		return $output;
	}
	
	public function show()
	{
		$output = false;
		global $yappy_direct_show;
		
		if(isset($yappy_direct_show))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$GLOBALS[$this->gateway_name . '_show'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public function is_valid_request()
	{
		$output = false;
		$which_var = $this->gateway_name . '_is_valid_request';
		global $$which_var;
		global $dy_request_invalids;
		
		if(isset($$which_var))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_request']) && !isset($dy_request_invalids))
			{
				if($_POST['dy_request'] == $this->gateway_name && dy_utilities::payment_amount() > 1)
				{
					$output = true;
					$GLOBALS[$which_var] = true;	
				}
			}		
		}
		
		return $output;
	}

	public function filter_content($content)
	{
		if(in_the_loop() && dy_validators::is_request_valid() && $this->is_valid_request())
		{
			global $dy_valid_recaptcha;

			if(isset($dy_valid_recaptcha))
			{
				$content = $this->message(null);
			}
		}
		return $content;
	}
	public function title($title)
	{
		if(in_the_loop() && dy_validators::is_request_valid() && $this->is_valid_request())
		{
			$title = esc_html(__('Thank you for using Yappy', 'dynamicpackages'));
		}
		return $title;
	}
	
	public function message($message)
	{		
		$first = __('To complete the booking please enter your Yappy App and send the', 'dynamicpackages');
		$last = __('to the number', 'dynamicpackages');
		$amount = dy_utilities::currency_symbol().number_format(dy_utilities::payment_amount(), 2, '.', ',');
		$label = __('payment', 'dynamicpackages');
		
		if(dy_validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}
		
		$message .= '<p class="large">'.esc_html($first.' '.$label.' ('.$amount.') '.$last).' <strong>'.esc_html($this->number).'</strong>.</p>';	
		$message .= '<p class="large dy_pad padding-10 strong">'.esc_html(__('Send', 'dynamicpackages').' '.$amount.' '.$last.' '.$this->number).'</p>';
		
		return $message;
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
			if($this->is_active() )
			{
				$max = floatval($this->max);
				$show = intval($this->show);
				$payment = package_field('package_payment');
				$deposit = floatval(dy_utilities::get_deposit());
				
				if(is_booking_page() || is_checkout_page())
				{
					$total = dy_utilities::payment_amount();
				}
				else
				{
					$total = floatval(dy_utilities::starting_at());
					
					if(package_field('package_fixed_price') == 0)
					{
						$total = $total * intval(package_field('package_max_persons'));
					}
					
				}

				
				if($total <= $max)
				{
					if($payment == $show && $payment == 0)
					{
						$output = true;
					}
					else
					{
						if(dy_validators::has_deposit())
						{
							$output = true;
						}
					}
				}
			}
			
			if($output == true){
				$GLOBALS[$this->gateway_name . '_is_valid'] = true;
			}
		}
		return $output;
	}

	public function settings_init()
	{
		//Yappy
		
		register_setting($this->gateway_name . '_settings', $this->gateway_name, 'intval');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_show', 'intval');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_max', 'floatval');
		
		add_settings_section(
			$this->gateway_name . '_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);
		
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'Yappy Cell Phone Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section', $this->gateway_name
		);	
		add_settings_field( 
			$this->gateway_name . '_max', 
			esc_html(__( 'Max. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section', $this->gateway_name . '_max'
		);
		add_settings_field( 
			$this->gateway_name . '_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'display_yappy_direct_show'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section'
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
		<select name="<?php esc_html_e($this->gateway_name . '_show'); ?>">
			<option value="0" <?php selected($this->show, 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected($this->show, 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
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
			
			<h1><?php esc_html_e($this->gateway_title); ?></h1>
			<?php
				settings_fields( $this->gateway_name . '_settings' );
				do_settings_sections( $this->gateway_name . '_settings' );
				submit_button();
			?>			
		</form>
		
		<?php
	}
	public function button($output)
	{
		if($this->show() && in_array($this->gateway_title, $this->list_gateways_cb()))
		{
			$output .= ' <button style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';"  class="pure-button bottom-20 with_'.esc_html($this->gateway_name).' rounded" type="button"><img alt="yappy" width="21" height="12" src="'.esc_url(plugin_dir_url( __FILE__ ).'yappy-icon.svg').'"/> '.esc_html(__('Pay with Yappy', 'dynamicpackages')).'</button>';			
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
		
		if(isset($dy_valid_recaptcha) && isset($_POST['dy_request']) && dy_validators::is_request_valid())
		{
			if($_POST['dy_request'] == 'request' || $_POST['dy_request'] == apply_filters('dy_fail_checkout_gateway_name', null))
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
			wp_add_inline_script('dynamicpackages', $this->js(), 'before');	
		}
	}

	public function js()
	{
		ob_start();
		?>
		jQuery(function(){
			jQuery('.with_<?php esc_html_e($this->gateway_name); ?>').click(function()
			{
				let logo = jQuery('<img>').attr({'src': dy_url()+'gateways/matrix/yappy/yappy.svg'});
				jQuery(logo).attr({'width': '80', 'height': '69'});
				jQuery('#dynamic_form').removeClass('hidden');
				jQuery('#dy_form_icon').html(logo);
				jQuery('#dynamic_form').find('input[name="first_name"]').focus();
				jQuery('#dynamic_form').find('input[name="dy_request"]').val('<?php echo esc_html($this->gateway_name); ?>');
				
				//facebook pixel
				if(typeof fbq !== typeof undefined)
				{
					console.log('InitiateCheckout');
					fbq('track', 'InitiateCheckout');
				}

				//google analytics
				if(typeof gtag !== 'undefined')
				{
					gtag('event', 'select_gateway', {
						items : '<?php echo esc_html($this->gateway_name); ?>'
					});					
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
		if(strtolower($gateway) == $this->gateway_short_name)
		{
			$str = '<aside class="clearfix"><img class="inline-block pull-left" style="vertical-align: middle; margin-right: 10px;" width="40" height="40" alt="yappy" src="'.esc_url(plugin_dir_url( __FILE__ ).'yappy-icon.svg').'"/><span class="semibold">'.esc_html(__('Pay with Yappy', 'dynamicpackages')).'.</span> '.$str.'</aside>';
		}
		
		return $str;
	}
}