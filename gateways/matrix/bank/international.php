<?php

class wire_transfer{
	
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
			add_action('admin_menu', array(&$this, 'add_settings_page'), 102);			
		}
		else
		{
			add_filter('dy_request_the_content', array(&$this, 'filter_content'), 103);
			add_filter('dy_request_the_title', array(&$this, 'title'), 103);
			add_filter('wp_headers', array(&$this, 'send_data'));
			add_filter('gateway_buttons', array(&$this, 'button'), 5);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 5);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 103);
		}		
	}
	
	public function args()
	{
		$this->gateway_name = 'wire_transfer';
		$this->gateway_title = __('Wire Transfer', 'dynamicpackages');
		
		//Beneficiary
		$this->b_account_number = get_option($this->gateway_name);	
		$this->b_account_name = get_option($this->gateway_name . '_b_account_name');
		$this->b_bank = get_option($this->gateway_name . '_b_bank');
		$this->b_bank_address = get_option($this->gateway_name . '_b_bank_address');
		$this->b_bank_swift = get_option($this->gateway_name . '_b_bank_swift');
		$this->b_bank_iban = get_option($this->gateway_name . '_b_bank_iban');
		
		//Intermediary
		$this->i_account_number = get_option($this->gateway_name . '_i_account_number');
		$this->i_account_name = get_option($this->gateway_name . '_i_account_name');
		$this->i_bank = get_option($this->gateway_name . '_i_bank');
		$this->i_bank_address = get_option($this->gateway_name . '_i_bank_address');
		$this->i_bank_swift = get_option($this->gateway_name . '_i_bank_swift');
		$this->i_bank_iban = get_option($this->gateway_name . '_i_bank_iban');		
		
		//controls
		$this->show = get_option($this->gateway_name . '_show');
		$this->min = get_option($this->gateway_name . '_min');
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
		global $wire_transfer_is_active;
		
		if(isset($wire_transfer_is_active))
		{
			$output = true;
		}
		else
		{
			if($this->b_account_number != '')
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
		global $wire_transfer_show;
		
		if(isset($wire_transfer_show))
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
			$title = esc_html(__('Pay With an International Wire Transfer', 'dynamicpackages'));
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
		
		$message = '<p class="large">'.__('To complete the booking please send us the', 'dynamicpackages');
		$message .= ' '.$label.' (';
		$message .= $amount;
		$message .= ') '. __('to the following account', 'dynamicpackages').'.</p>';
		$message .= '<div class="large dy_pad">'.$this->account().'</div>';
		
		return $message;
	}
	
	public function account()
	{
		$wire = '<p><strong>'.esc_html(__('Beneficiary Bank', 'dynamicpackages')).'</strong><br/>';
		
		if($this->b_bank != '')
		{
			$wire .= esc_html(__('Beneficiary Bank Name', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank).'</strong><br/>';
		}
		
		if($this->b_bank_address != '')
		{
			$wire .= esc_html(__('Beneficiary Bank Address', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank_address).'</strong><br/>';
		}

		if($this->b_bank_swift != '')
		{
			$wire .= esc_html(__('Beneficiary Bank Swift', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank_swift).'</strong><br/>';
		}		
		
		if($this->b_account_name != '')
		{
			$wire .= esc_html(__('Beneficiary Account Name', 'dynamicpackages')).': <strong>'.esc_html($this->b_account_name).'</strong><br/>';
		}		
		
		if($this->b_account_number != '')
		{
			$wire .= esc_html(__('Beneficiary Account Number', 'dynamicpackages')).': <strong>'.esc_html($this->b_account_number).'</strong><br/>';
		}
		
		if($this->b_bank_iban != '')
		{
			$wire .= esc_html(__('Beneficiary Account IBAN', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank_iban).'</strong><br/>';
		}		
		
		$wire .= '<br/><strong>'.esc_html(__('Intermediary Bank', 'dynamicpackages')).'</strong><br/>';
		
		if($this->i_bank != '')
		{
			$wire .= esc_html(__('Intermediary Bank Name', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank).'</strong><br/>';
		}
		
		if($this->i_bank_address != '')
		{
			$wire .= esc_html(__('Intermediary Bank Address', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank_address).'</strong><br/>';
		}

		if($this->i_bank_swift != '')
		{
			$wire .= esc_html(__('Intermediary Bank Swift', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank_swift).'</strong><br/>';
		}		
		
		if($this->i_account_name != '')
		{
			$wire .= esc_html(__('Intermediary Account Name', 'dynamicpackages')).': <strong>'.esc_html($this->i_account_name).'</strong><br/>';
		}		
		
		if($this->i_account_number != '')
		{
			$wire .= esc_html(__('Intermediary Account Number', 'dynamicpackages')).': <strong>'.esc_html($this->i_account_number).'</strong><br/>';
		}
		
		if($this->i_bank_iban != '')
		{
			$wire .= esc_html(__('Intermediary Account IBAN', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank_iban).'</strong><br/>';
		}
		
		$wire .= '</p>';
		
		return $wire;
	}

	public function is_valid()
	{
		$output = false;
		global $wire_transfer_is_valid;
		
		if(isset($wire_transfer_is_valid))
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
					
					if(package_field('package_starting_at_unit') == 0)
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
		//Beneficiary
		register_setting($this->gateway_name . '_settings', $this->gateway_name, 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_b_account_name', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_b_bank', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_b_bank_address', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_b_bank_swift', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_b_bank_iban', 'sanitize_text_field');
		
		//Intermediary
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_i_account_number', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_i_account_name', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_i_bank', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_i_bank_address', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_i_bank_swift', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_i_bank_iban', 'sanitize_text_field');		
		
		//controls
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_show', 'intval');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_min', 'floatval');
		
		
		//section
		add_settings_section(
			$this->gateway_name . '_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);		
		
		//section
		add_settings_section(
			$this->gateway_name . '_b_section', 
			esc_html(__( 'Beneficiary Bank Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);
		
		//section
		add_settings_section(
			$this->gateway_name . '_i_section', 
			esc_html(__( 'Intermediary Bank Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);		
		
		
		
		//Beneficiary		
		add_settings_field( 
			$this->gateway_name . '_b_bank', 
			esc_html(__( 'Beneficiary Bank Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_b_section', $this->gateway_name . '_b_bank'
		);
		add_settings_field( 
			$this->gateway_name . '_b_bank_address', 
			esc_html(__( 'Beneficiary Bank Address', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_b_section', $this->gateway_name . '_b_bank_address'
		);
		add_settings_field( 
			$this->gateway_name . '_b_bank_swift', 
			esc_html(__( 'Beneficiary Bank Swift', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_b_section', $this->gateway_name . '_b_bank_swift'
		);				
		add_settings_field( 
			$this->gateway_name . '_b_account_name', 
			esc_html(__( 'Beneficiary Account Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_b_section', $this->gateway_name . '_b_account_name'
		);			
		
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'Beneficiary Account Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_b_section', $this->gateway_name
		);
		add_settings_field( 
			$this->gateway_name . '_b_bank_iban', 
			esc_html(__( 'Beneficiary Account IBAN', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_b_section', $this->gateway_name . '_b_bank_iban'
		);	
		
		//Intermediary			
		add_settings_field( 
			$this->gateway_name . '_i_bank', 
			esc_html(__( 'Intermediary Bank Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_i_section', $this->gateway_name . '_i_bank'
		);
		add_settings_field( 
			$this->gateway_name . '_i_bank_address', 
			esc_html(__( 'Intermediary Bank Address', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_i_section', $this->gateway_name . '_i_bank_address'
		);
		add_settings_field( 
			$this->gateway_name . '_i_bank_swift', 
			esc_html(__( 'Intermediary Bank Swift', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_i_section', $this->gateway_name . '_i_bank_swift'
		);		
		add_settings_field( 
			$this->gateway_name . '_i_account_name', 
			esc_html(__( 'Intermediary Account Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_i_section', $this->gateway_name . '_i_account_name'
		);			
		
		add_settings_field( 
			$this->gateway_name . '_i_account_number', 
			esc_html(__( 'Intermediary Account Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_i_section', $this->gateway_name . '_i_account_number'
		);
		add_settings_field( 
			$this->gateway_name . '_i_bank_iban', 
			esc_html(__( 'Intermediary Account IBAN', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_i_section', $this->gateway_name . '_i_bank_iban'
		);			

		//controls
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
			array(&$this, 'display_wire_transfer_show'), 
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
	
	public function display_wire_transfer_show() { ?>
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
			$output .= ' <button style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';" class="pure-button bottom-20 pure-button-wire  with_' . esc_html($this->gateway_name) . ' rounded" type="button"><i class="fas fa-globe"></i> '.esc_html($this->gateway_title).'</button>';			
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
			wp_add_inline_style('minimalLayout', $this->css());
			wp_add_inline_script('dynamicpackages', $this->js(), 'before');	
		}
	}
	public function css()
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
	public function js()
	{
		ob_start();
		?>
		jQuery(function(){
			jQuery('.with_<?php esc_html_e($this->gateway_name); ?>').click(function()
			{
				var wire_logo = jQuery('<span style="font-size: 3em;"></span>').addClass('text-muted');
				wire_logo.append('<i class="fas fa-globe"></i>');
				jQuery('#dynamic_form').removeClass('hidden');
				jQuery('#dy_form_icon').html(wire_logo);
				jQuery('#dynamic_form').find('input[name="first_name"]').focus();
				jQuery('#dynamic_form').find('input[name="dy_request"]').val('<?php echo esc_html($this->gateway_name); ?>');
				
				//facebook pixel
				if(typeof fbq !== typeof undefined)
				{
					console.log('InitiateCheckout');
					fbq('track', 'InitiateCheckout');
				}

				//google analytics
				if(typeof ga !== typeof undefined)
				{
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
}