<?php

class bank_transfer{
	
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
			add_action('admin_menu', array(&$this, 'add_settings_page'), 101);			
		}
		else
		{
			add_filter('dy_request_the_content', array(&$this, 'filter_content'), 102);
			add_filter('dy_request_the_title', array(&$this, 'title'), 102);
			add_filter('wp_headers', array(&$this, 'send_data'));
			add_filter('gateway_buttons', array(&$this, 'button'), 4);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 4);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 102);
		}
	}
	
	public function args()
	{
		$this->gateway_name = 'bank_transfer';
		$this->gateway_title = __('Local Bank', 'dynamicpackages');
		$this->bank = get_option($this->gateway_name . '_bank');
		$this->number = get_option($this->gateway_name);
		$this->type = get_option($this->gateway_name . '_type');
		$this->beneficiary = get_option($this->gateway_name . '_beneficiary');
		$this->min = get_option($this->gateway_name . '_min');
		$this->show = get_option($this->gateway_name . '_show');
		$this->color = '#fff';
		$this->background_color = '#262626';
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
				add_filter('dy_email_intro', array(&$this, 'subject'));
				add_filter('dy_email_subject', array(&$this, 'subject'));
			}
		}
	}

	public function subject()
	{
		return sprintf(__('%s, %s sent you a payment request for %s%s using %s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), dy_utilities::currency_symbol(), dy_utilities::currency_format(dy_utilities::total()), sanitize_text_field($this->gateway_title), sanitize_text_field($_POST['title']));
	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->gateway_title);
	}

	public function is_active()
	{
		$output = false;
		global $bank_transfer_is_active;
		
		if(isset($bank_transfer_is_active))
		{
			$output = true;
		}
		else
		{
			if($this->number != '')
			{
				$GLOBALS[$this->gateway_name . '_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public function show()
	{
		$output = false;
		global $bank_transfer_show;
		
		if(isset($bank_transfer_show))
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
			$title = esc_html(__('Pay From Your Bank', 'dynamicpackages'));
		}
		return $title;
	}
	public function message($message)
	{
		
		$amount = dy_utilities::currency_symbol().number_format(dy_utilities::payment_amount(), 2, '.', ',');
		$label = __('payment', 'dynamicpackages');
		
		if(dy_validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}		
		
		$message .= '<p class="large">'.__('To complete the booking please send us the', 'dynamicpackages');
		$message .= ' '.$label.' (';
		$message .= $amount;
		$message .= ') '. __('to the following account', 'dynamicpackages').'.</p>';
		$message .= '<p class="large dy_pad padding-10">'.$this->account().'</p>';
		
		return $message;
	}
	
	public function account()
	{
		
		$type = __('Saving Account', 'dynamicpackages');
		
		if($this->type == 1)
		{
			$type = __('Checking Account', 'dynamicpackages');
		}
		
		$bank = esc_html(__('Bank', 'dynamicpackages')).': <strong>'.esc_html($this->bank).'</strong> <br/>';
		$bank .= esc_html(__('Account Type', 'dynamicpackages')).': <strong>'.esc_html($type).'</strong><br/>';
		$bank .= esc_html(__('Account Number', 'dynamicpackages')).': <strong>'.esc_html($this->number).'</strong><br/>';
		$bank .= esc_html(__('Account Name', 'dynamicpackages')).': <strong>'.esc_html($this->beneficiary).'</strong>';

		return $bank;
	}

	public function is_valid()
	{
		$output = false;
		global $bank_transfer_is_valid;
		
		if(isset($bank_transfer_is_valid))
		{
			return true;
		}
		else
		{
			if($this->is_active() )
			{
				$min = floatval($this->min);
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
				
				if($total >= $min)
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
		//Bank
		
		register_setting($this->gateway_name . '_settings', $this->gateway_name, 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_beneficiary', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_bank', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_type', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_show', 'intval');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_min', 'floatval');
		
		add_settings_section(
			$this->gateway_name . '_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);		
		
		add_settings_section(
			$this->gateway_name . '_settings_section', 
			esc_html(__( 'Bank Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);
		
		add_settings_field( 
			$this->gateway_name . '_bank', 
			esc_html(__( 'Bank Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section', $this->gateway_name . '_bank'
		);		
		add_settings_field( 
			$this->gateway_name . '_type', 
			esc_html(__( 'Account Type', 'dynamicpackages' )), 
			array(&$this, 'display_bank_transfer_type'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section'
		);	
		add_settings_field( 
			$this->gateway_name . '_beneficiary', 
			esc_html(__( 'Account Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section', $this->gateway_name . '_beneficiary'
		);			
		
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'Account Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section', $this->gateway_name
		);	
		add_settings_field( 
			$this->gateway_name . '_min', 
			esc_html(__( 'Min. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_control_section', $this->gateway_name . '_min'
		);
		add_settings_field( 
			$this->gateway_name . '_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'display_bank_transfer_show'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_control_section'
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
		
	
	public function display_bank_transfer_type() { ?>
		<select name="<?php esc_html_e($this->gateway_name . '_type'); ?>">
			<option value="0" <?php selected($this->type, 0); ?>><?php echo esc_html('Saving', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected($this->type, 1); ?>><?php echo esc_html('Checking', 'dynamicpackages'); ?></option>
		</select>
	<?php }
			
	
	public function display_bank_transfer_show() { ?>
		<select name="<?php esc_html_e($this->gateway_name . '_show'); ?>">
			<option value="0" <?php selected(get_option($this->gateway_name . '_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option($this->gateway_name . '_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
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
			$output .= ' <button style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';" class="pure-button bottom-20 pure-button-bank  with_' . esc_html($this->gateway_name) . ' rounded" type="button"><i class="fas fa-money-check-alt"></i> '.esc_html($this->gateway_title).'</button>';			
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
				let logo = jQuery('<p class="large"><?php echo esc_html(__('Pay to local bank account in', 'dynamicpackages')); ?> <strong><?php echo esc_html($this->bank); ?></strong></p>').addClass('text-muted');
				jQuery('#dynamic_form').removeClass('hidden');
				jQuery('.cc_payment_conditions').addClass('hidden');
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
}