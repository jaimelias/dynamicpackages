<?php

if ( !defined( 'WPINC' ) ) exit;


class bank_transfer{
	
	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->id = 'bank_transfer';
		add_action('init', array(&$this, 'init'));
		add_action( 'admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 101);	
		add_filter('dy_request_the_content', array(&$this, 'filter_content'), 102);
		add_filter('dy_request_the_title', array(&$this, 'title'), 102);
		add_filter('wp_headers', array(&$this, 'send_data'));
		add_filter('gateway_buttons', array(&$this, 'button'), 4);
		add_filter('list_gateways', array(&$this, 'add_gateway'), 4);
	}
	

	public function init()
	{
		$this->valid_recaptcha = validate_recaptcha();
		$this->name = __('Local Bank Transfer', 'dynamicpackages');
		$this->type = 'bank';
		$this->bank = get_option($this->id . '_bank');
		$this->number = get_option($this->id);
		$this->type = get_option($this->id . '_type');
		$this->beneficiary = get_option($this->id . '_beneficiary');
		$this->min = get_option($this->id . '_min');
		$this->show = get_option($this->id . '_show');
		$this->color = '#fff';
		$this->background_color = '#262626';
	}	

	public function send_data()
	{		
		if(dy_validators::validate_request() && $this->is_request_submitted())
		{
			if($this->valid_recaptcha)
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
		return sprintf(__('%s, %s sent you a payment request for %s%s using %s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), dy_utilities::currency_symbol(), dy_utilities::currency_format(dy_utilities::total()), sanitize_text_field($this->name), sanitize_text_field($_POST['title']));
	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name);
	}

	public function is_active()
	{
		$output = false;
		$which_var = $this->id.'_is_active';
		global $$which_var; 
		
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(!empty($this->number))
			{
				$output = true;
			}

			$GLOBALS[$which_var] = $output;
		}
		return $output;
	}
	public function show()
	{
		$output = false;
		$which_var = $this->id.'_show';
		global $$which_var; 
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$output = true;
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}
		return $output;
	}

	public function is_request_submitted()
	{
		$output = false;
		$which_var = $this->id . '_is_valid_request';
		global $$which_var;
		global $dy_request_invalids;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(is_checkout_page() && !isset($dy_request_invalids))
			{
				if($_POST['dy_request'] === $this->id && dy_utilities::payment_amount() > 1)
				{
					$output = true;
					
				}
			}
			
			$GLOBALS[$which_var] = $output;	
		}
		
		return $output;
	}

	public function filter_content($content)
	{
		if(in_the_loop() && dy_validators::validate_request() && $this->is_request_submitted())
		{
			if($this->valid_recaptcha)
			{
				$content = $this->message(null);
			}	
		}
		return $content;
	}
		
	public function title($title)
	{
		if(in_the_loop() && dy_validators::validate_request() && $this->is_request_submitted())
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
		$which_var = $this->id . '_is_valid';
		global $$which_var;
		
		if(isset($$which_var))
		{
			return $$which_var;
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
			
			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}

	public function settings_init()
	{
		//Bank
		
		register_setting($this->id . '_settings', $this->id, 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_beneficiary', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_bank', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_type', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_min', 'floatval');
		
		add_settings_section(
			$this->id . '_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);		
		
		add_settings_section(
			$this->id . '_settings_section', 
			esc_html(__( 'Bank Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);
		
		add_settings_field( 
			$this->id . '_bank', 
			esc_html(__( 'Bank Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id . '_bank'
		);

		$type_args = array(
			'name' => $this->id . '_type',
			'options' => array(
				array(
					'text' => __('Saving', 'dynamicpackages'),
					'value' => 0
				),
				array(
					'text' => esc_html('Checking', 'dynamicpackages'),
					'value' => 1
				),
			)
		);

		add_settings_field( 
			$this->id . '_type', 
			esc_html(__( 'Account Type', 'dynamicpackages' )), 
			array(&$this, 'select'), 
			$this->id . '_settings', 
			$this->id . '_settings_section',
			$type_args
		);	

		add_settings_field( 
			$this->id . '_beneficiary', 
			esc_html(__( 'Account Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id . '_beneficiary'
		);			
		
		add_settings_field( 
			$this->id, 
			esc_html(__( 'Account Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id
		);	
		add_settings_field( 
			$this->id . '_min', 
			esc_html(__( 'Min. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_control_section', $this->id . '_min'
		);


		$show_args = array(
			'name' => $this->id . '_show',
			'options' => array(
				array(
					'text' => __('Full Payments and Deposits', 'dynamicpackages'),
					'value' => 0
				),
				array(
					'text' => esc_html('Only Deposits', 'dynamicpackages'),
					'value' => 1
				),
			)
		);

		add_settings_field( 
			$this->id . '_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'select'), 
			$this->id . '_settings', 
			$this->id . '_control_section', 
			$show_args
		);		
	}
	
	public function input_text($name){
		$option = get_option($name);
		?>
		<input type="text" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($option); ?>" />
		<?php
	}
	
	public function input_number($name){
		$option = get_option($name);
		?>
		<input type="number" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($option); ?>" /> #
		<?php
	}		
	
	public function select($args) {
		
		$name = $args['name'];
		$options = $args['options'];
		$value = intval(get_option($name));
		$render_options = '';
		

		for($x = 0; $x < count($options); $x++)
		{
			$this_value = intval($options[$x]['value']);
			$this_text = $options[$x]['text'];
			$selected = ($value === $this_value) ? ' selected ' : '';
			$render_options .= '<option value="'.esc_attr($this_value).'" '.esc_attr($selected).'>'.esc_html($this_text).'</option>';
		}

		?>
			<select name="<?php echo esc_attr($name); ?>">
				<?php echo $render_options; ?>
			</select>
		<?php 
	}	

	public function add_settings_page()
	{
		add_submenu_page( $this->plugin_id, $this->name, '💸 '. $this->name, 'manage_options', $this->id, array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html_e($this->name); ?></h1>	
			<?php
			settings_fields( $this->id . '_settings' );
			do_settings_sections( $this->id . '_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	public function button($output)
	{
		if($this->show() && in_array($this->name, $this->list_gateways_cb()))
		{
			$output .= ' <button data-type="'.esc_attr($this->type).'"  data-id="'.esc_attr($this->id).'" data-branding="'.esc_attr($this->branding()).'" style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';" class="pure-button bottom-20 rounded" type="button"><span class="dashicons dashicons-money-alt"></span> '.esc_html($this->name).'</button>';			
		}
		return $output;
	}
	public function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	public function add_gateway($array)
	{
		$add = false;
		
		if($this->show() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$add = true;
		}
		
		if($this->valid_recaptcha && is_checkout_page() && dy_validators::validate_request())
		{
			if($_POST['dy_request'] == 'estimate_request' || $_POST['dy_request'] == apply_filters('dy_fail_checkout_gateway_name', null))
			{
				$add = true;
			}	
		}		
		
		if($add)
		{
			$array[] = $this->name;
		}
		
		return $array;	
	}


	public function branding()
	{
		return '<p class="large text-muted">'.$this->name.' <strong>'.$this->bank.'</strong></p>';
	}
}