<?php

class paguelo_facil_on{
	
	function __construct()
	{
		$this->init();
	}
	
	public function init()
	{
		add_action('init', array(&$this, 'args'));
		
		if(is_admin())
		{
			add_action('admin_init', array(&$this, 'settings_init'), 1);
			add_action('admin_menu', array(&$this, 'add_settings_page'), 100);			
		}
		else
		{
			add_action('init', array(&$this, 'checkout'), 50);
			add_filter('dy_request_the_content', array(&$this, 'the_content'));
			add_filter('dy_request_the_title', array(&$this, 'the_title'));
			add_filter('gateway_buttons', array(&$this, 'button'), 1);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 1);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 100);
			add_filter('dy_debug_instructions', array(&$this, 'debug_instructions'));
		}
	}
	
	public function args()
	{
		$this->gateway_name = 'paguelo_facil_on';
		$this->gateway_short_title = __('Paguelo Facil', 'dynamicpackages');
		$this->gateway_title = __('Paguelo Facil On-site', 'dynamicpackages');
		$this->gateway_methods_o = __('Visa or Mastercard', 'dynamicpackages');
		$this->gateway_methods_c = __('Visa, Mastercard', 'dynamicpackages');
		$this->cclw = get_option($this->gateway_name);
		$this->show = get_option($this->gateway_name . '_show');
		$this->min = (get_option($this->gateway_name . '_min')) ? get_option($this->gateway_name . '_min') : 5;
		$this->max = (get_option($this->gateway_name . '_max')) ? get_option($this->gateway_name . '_max') : 500;
		$this->color = '#fff';
		$this->background_color = '#262626';
		$this->dummy_cc = '4321502106746398';
		$this->debug_email = (get_option($this->gateway_name . '_debug_email')) ? get_option($this->gateway_name . '_debug_email') : get_option('admin_email');
		$this->debug($this->dummy_cc, $this->debug_email);		
		$this->production_url = 'https://secure.paguelofacil.com/rest/ccprocessing/';
		$this->sandbox_url = 'https://sandbox.paguelofacil.com/rest/ccprocessing/';
		$this->endpoint = (isset($this->debug_mode)) ? $this->sandbox_url : $this->production_url;
	}
	
	public function checkout()
	{
		global $dy_valid_recaptcha;
		
		if(!isset($this->success))
		{
			if(dy_Validators::validate_checkout($this->gateway_name))
			{
				if(isset($dy_valid_recaptcha))
				{
					$force_status = false;
					
					if(isset($this->debug_mode))
					{
						if($this->debug_mode !== 3)
						{
							$force_status = true;
						}
					}
					
					if($force_status === false)
					{
						$process_request = json_decode($this->process_request(), true);
						$number = 0;
						
						if(is_array($process_request))
						{
							if(array_key_exists('error', $process_request))
							{
								$number = 0;
								$this->error_codes = array(
									'error' => $process_request['error']
								);
							}
							else if(array_key_exists('Status', $process_request))
							{
								if($process_request['Status'] == 'Declined')
								{
									$number = 1;
									$this->error_codes = array(
										'RespText' => $process_request['RespText'],
										'RespCode' => $process_request['RespCode']
									);								
								}
								else if($process_request['Status'] == 'Approved')
								{
									$number = 2;
								}
							}
						}
						else
						{
							$number = 0;
							$this->error_codes = array(
								'error' => 'connection_timeout'
							);	
						}
						
						if(isset($this->error_codes))
						{
							write_log($this->error_codes);
						}						
					}
					else
					{
						$number = $this->debug_mode;
					}
					
					$this->success = $number;
					$this->send_data();
				}
			}			
		}		
	}	

	public function send_data()
	{
		global $dy_valid_recaptcha;
		
		if(dy_Validators::is_request_valid() && $this->is_valid_request() && isset($dy_valid_recaptcha) && isset($this->success))
		{
			add_filter('dy_email_message', array(&$this, 'message'));
			add_filter('dy_email_message', array(&$this, 'email_message_bottom'));
			add_filter('dy_email_intro', array(&$this, 'subject'));
			add_filter('dy_email_subject', array(&$this, 'subject'));
			add_filter('dy_email_label_doc', array(&$this, 'label_doc'));
			add_filter('dy_email_notes', array(&$this, 'email_notes'));
			
			if($this->success == 2)
			{
				add_filter('dy_totals_area', array(&$this, 'totals_area'));
			}
			else
			{
				add_filter('dy_fail_checkout_gateway_name', array(&$this, 'gateway_name'));
			}
		}
	}
	
	public function gateway_name($output)
	{
		return $this->gateway_name;
	}

	public function label_doc($output)
	{
		
		if(isset($this->success))
		{
			if($this->success === 2)
			{
				$output = __('Invoice', 'dynamicpackages');
			}
		}
		
		return $output;
	}	
	
	public function subject($output)
	{		
		if(isset($this->success))
		{
			if($this->success === 2)
			{
				$payment = (dy_Validators::has_deposit()) ? __('Deposit', 'dynamicpackages') : __('Payment', 'dynamicpackages');
				$output = '✔️ ' . sprintf(__('Thank You for Your %s of %s%s: %s', 'dynamicpackages'), $payment, dy_utilities::currency_symbol(), dy_utilities::payment_amount(), $_POST['title']);
			}
			else if($this->success === 1)
			{
				$output = '⚠️ ' . sprintf(__('Your Payment to %s for %s%s was Declined', 'dynamicpackages'), get_bloginfo('name'), dy_utilities::currency_symbol(), dy_utilities::payment_amount()) . ' ⚠️';
			}
			else if($this->success === 0)
			{
				$output = '⚠️ ' . sprintf(__('%s is having problems processing your payment', 'dynamicpackages'), get_bloginfo('name')) . ' ⚠️';
			}
		}
		
		return $output;
	}
	
	public function email_message_bottom($output)
	{
		if(isset($this->success))
		{
			if($this->success === 2)
			{
				$terms_conditions = dy_Public::get_terms_conditions(sanitize_text_field($_POST['post_id']));
				
				if(is_array($terms_conditions))
				{
					if(count($terms_conditions) > 0)
					{
						$output .= '<p>🗎 ' . __('The Terms & Conditions you accepted are attached to this email.', 'dynamicpackages') . '</p>';
					}
				}
				
				$output .= '<p>'. __('Take your time to review our invoice in detail.', 'dynamicpackages') . '</p>';
			}
		}
		
		return $output;
	}
	
	public function message($output)
	{
		if(isset($this->success))
		{
			if($this->success === 2)
			{	
				$output = '<p>⚠️ ' . __('To complete this reservation we require images of the passports (foreigners) or valid Identity Documents (nationals) of each participant. The documents you send will be compared against the originals at the meeting point.', 'dynamicpackages') . '</p>';
				$output .= $this->email_notes(null);
				$output .= '<p>❌ '. __('It is not allowed to book for third parties.', 'dynamicpackages') . '</p>';
			}
			else if($this->success === 1)
			{
				$output = '<p>☎️ ' . esc_html(__('Please contact your bank to authorize the transaction.', 'dynamicpackages')) . ' ☎️</p>';
				$output .= $this->get_errors();
			}
			else
			{
				$output = '<p>' . esc_html(__('Please try again in a few minutes. Our staff will be in touch with you very soon.', 'dynamicpackages')) . '</p>';
				$output .= $this->get_errors();
			}
		}
		
		return $output;
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
			if($this->cclw != '')
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
		$which_var = $this->gateway_name . '_show';
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$GLOBALS[$which_var] = true;
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
					$GLOBALS[$which_var] = true;
					$output = true;
				}
			}		
		}
		
		return $output;
	}
	
	public function the_content($output)
	{
		global $dy_valid_recaptcha;
		
		
		if(isset($this->success) && in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			if(isset($dy_valid_recaptcha))
			{
				if($this->success === 2)
				{
					$payment = (dy_Validators::has_deposit()) ? __('deposit', 'dynamicpackages') : __('payment', 'dynamicpackages');
					
					$output = '<p class="minimal_success strong"><i class="fas fa-check"></i> ' . sprintf(__('Thank you for your %s of %s%s.', 'dynamicpackages'), $payment, dy_utilities::currency_symbol(), dy_utilities::payment_amount()) . '</p>';
					
					$output .= '<div class="bottom-20">' . apply_filters('dy_package_description', null) . '</div>';
					$output .= '<div class="bottom-20">' . $this->message(null) . '</div>';
					
					$output .= '<p class="minimal_success strong"><i class="fas fa-envelope"></i> '.esc_html(sprintf(__('We have sent you an email to %s with more details and the confirmation of this booking.', 'dynamicpackages'), sanitize_text_field($_POST['email']))).'</p>';
					
					$add_to_calendar = apply_filters('dy_add_to_calendar', null);
					
					if($add_to_calendar)
					{
						$output .= '<div class="text-center">'. $add_to_calendar .'</div>';
					}					
				}
				else if($this->success === 1)
				{
					$output = '<p class="minimal_alert strong">' . esc_html(__('Please contact your bank to authorize the transaction.', 'dynamicpackages')) . '</p>';
					$output .= $this->get_errors();
				}
				else
				{
					$output = '<p class="minimal_alert strong">' . esc_html(__('Please try again in a few minutes. Our staff will be in touch with you very soon.', 'dynamicpackages')) . '</p>';
					$output .= $this->get_errors();
				}				
			}
		}
		return $output;
	}
		
		
	public function get_errors()
	{
		$output = null;

		if(isset($this->error_codes))
		{
			foreach($this->error_codes as $k => $v)
			{
				$output .= '<p class="minimal_alert strong">'.$k .': '.$v.'</p>';
			}			
		}
		
		return $output;
	}		
		
	public function the_title($output)
	{
		
		
		if(isset($this->success) && in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			if($this->success === 2)
			{
				$output = __('Payment Approved', 'dynamicpackages');
			}
			else if($this->success === 1)
			{
				$output = __('Payment Declined', 'dynamicpackages');
			}
			else
			{
				$output = __('Checkout Error', 'dynamicpackages');
			}
		}
		return $output;
	}

	public function email_notes($output)
	{
		
		
		if(isset($this->success))
		{
			if($this->success === 2)
			{				
				$output = null;
				$message = package_field('package_provider_message');
				$details = apply_filters('dy_package_details', null); 
				$output .= ($details) ? $details : null;
				$output .= ($message) ? '<br/><br/>' . esc_html($message) : null;				
			}
		}
		
		return $output;
	}


	public function is_valid()
	{
		$output = false;
		$which_var = $this->gateway_name . '_is_valid';
		global $$which_var;
		
		if(isset($$which_var))
		{
			return true;
		}
		else
		{
			if($this->is_active() && !isset($_GET['quote']))
			{
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
				
				if($total >= $this->min && $total <= $this->max)
				{
					if($payment == $this->show && $payment == 0)
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
				$GLOBALS[$which_var] = true;
			}
		}
		return $output;
	}

	public function settings_init()
	{
		register_setting($this->gateway_name . '_settings', $this->gateway_name, 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_min', 'intval');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_max', 'intval');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_show', 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_debug_email', 'sanitize_email');
		
		add_settings_section(
			$this->gateway_name . '_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);		
		
		add_settings_section(
			$this->gateway_name . '_settings_section', 
			esc_html(sprintf(__( '%s Settings', 'dynamicpackages' ), $this->gateway_title)), 
			'', 
			$this->gateway_name . '_settings'
		);
				
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'CCLW', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
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
			$this->gateway_name . '_max', 
			esc_html(__( 'Max. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_control_section', $this->gateway_name . '_max'
		);
		add_settings_field( 
			$this->gateway_name . '_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'input_select_on_show'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_control_section'
		);
		
		add_settings_field( 
			$this->gateway_name . '_debug_email', 
			esc_html(__( 'Debug Email', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_control_section', $this->gateway_name . '_debug_email'
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
		
	public function input_select_on_show() { ?>
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
			<?php echo $this->debug_instructions(); ?>
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
		if($this->show() && in_array($this->gateway_methods_c, $this->list_gateways_cb()))
		{
			$output .= ' <button style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';" class="pure-button bottom-20 with_cc with_' . esc_html($this->gateway_name) . ' rounded" type="button"><i class="fas fa-credit-card"></i> '.esc_html($this->gateway_methods_o).'</button>';			
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
			if($_POST['dy_request'] == 'request' || $_POST['dy_request'] == apply_filters('dy_fail_checkout_gateway_name', null))
			{
				$add = true;
			}	
		}		
		
		if($add)
		{
			$array[] = $this->gateway_methods_c;
		}
		
		return $array;	
	}
	
	public function totals_area($output)
	{
		if(dy_Validators::has_deposit())
		{
			$outstanding = dy_utilities::currency_symbol().dy_utilities::currency_format(dy_utilities::outstanding_amount());
			$total =  dy_utilities::currency_symbol().dy_utilities::currency_format(dy_utilities::payment_amount());
			$date = sanitize_text_field($_POST['booking_date']);
			
			$output .= '<br/><strong style="color: #666666;">'.__('Paid', 'dynamicpackages').' <span class="sm-hide">('.$date.')</span></strong><br/> -'.$total;
			$output .= '<br/><strong style="color: #666666;">'.__('Amount Due', 'dynamicpackages').'</strong><br/> '.$outstanding;
		}
		
		return $output;
	}
	
	public function scripts()
	{
		if($this->show())
		{
			wp_add_inline_script('dynamicpackages', $this->js(), 'before');	
		}
	}
	
	public function build_request()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		
		if(array_key_exists('HTTP_CF_CONNECTING_IP ', $_SERVER))
		{
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		
		$CCNum = sanitize_text_field($_POST['CCNum']);
		$CVV2 = sanitize_text_field($_POST['CVV2']);
		$email = sanitize_text_field($_POST['email']);
		$hash = $CCNum.$CVV2.$email;
		
		$data = array(
			'CCLW' => $this->cclw,
			'txType' => 'SALE',
			'CMTN' => dy_utilities::payment_amount(),
			'CDSC' => substr(apply_filters('dy_package_description', null), 0, 150),
			'CCNum' => $CCNum,
			'ExpMonth' => sanitize_text_field($_POST['ExpMonth']),
			'ExpYear' => sanitize_text_field($_POST['ExpYear']),
			'CVV2' => $CVV2,
			'Name' => sanitize_text_field($_POST['first_name']),
			'LastName' => sanitize_text_field($_POST['lastname']),
			'Email' => $email,
			'Address' => sanitize_text_field($_POST['address']),
			'Tel' => sanitize_text_field($_POST['phone']),
			'Ip'=> $ip,
			'SecretHash' => hash('sha512', $hash)
		);
				
		return $data;
	}
	
	public function process_request()
	{
		$params = http_build_query($this->build_request());
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $this->endpoint);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Accept: */*'));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $params);
		$result = curl_exec($ch);
		return $result;
	}

	public function debug($dummy_cc)
	{	
		if(isset($_POST['CCNum']) && isset($_POST['CVV2']) && isset($_POST['email']))
		{			
			if($_POST['CCNum'] == $dummy_cc && $this->user_can_debug() && $_POST['email'] == $this->debug_email)
			{
				if($_POST['CVV2'] == '222')
				{
					$this->debug_mode = 2;
				}
				else if($_POST['CVV2'] == '111')
				{
					$this->debug_mode = 1;
				}
				else if($_POST['CVV2'] == '000')
				{
					$this->debug_mode = 0;
				}
				else
				{
					$this->debug_mode = 3;
				}
			}
		}
	}
	
	public function user_can_debug()
	{
		$output = false;
		
		if(is_user_logged_in())
		{
			if(current_user_can('editor') || current_user_can('administrator'))
			{
				$output = true;
			}
		}
		
		return $output;
	}
	
	
		
	public function debug_instructions()
	{
		if($this->user_can_debug())
		{
			return '<p style="line-height: 2; color: #696969; background-color: #ADD8E6; padding: 10px;">🤖 '.sprintf(__('Use the card %s together with the email %s to test Paguelo Facil Development Enviroment. Use the CVV code 222 to generate approved transactions, 111 to generate declined transaction and 000 to generate errors and any other number will retreive Paguelo Facil original response.', 'dynamicpackages'), '<strong>'.esc_html($this->dummy_cc).'</strong>', '<strong>'.esc_html($this->debug_email).'</strong>').'</p>';			
		}
	}

	public function js()
	{
		ob_start();
		?>
		jQuery(function(){
			jQuery('.with_<?php esc_html_e($this->gateway_name); ?>').click(function()
			{
				var <?php echo esc_html($this->gateway_name); ?>_logo = jQuery('<p class="large"><?php echo esc_html(sprintf(__('Pay with %s thanks to', 'dynamicpackages'), $this->gateway_methods_o)); ?> <strong><?php echo esc_html($this->gateway_short_title); ?></strong></p>').addClass('text-muted');
				jQuery('#dynamic_form').removeClass('hidden');
				jQuery('#dy_form_icon').html(<?php echo esc_html($this->gateway_name); ?>_logo);
				jQuery('#dynamic_form').find('input[name="name"]').focus();
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