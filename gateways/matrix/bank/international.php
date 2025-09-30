<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class wire_transfer{
	
	private static $cache = [];

	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->id = 'wire_transfer';
		add_action('init', array(&$this, 'init'));
		add_action( 'admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 102);	
		add_filter('dy_request_the_content', array(&$this, 'filter_content'), 103);
		add_filter('dy_request_the_title', array(&$this, 'title'), 103);
		add_filter('wp_headers', array(&$this, 'send_data'));
		add_filter('dy_list_gateways', array(&$this, 'add_gateway'), 5);
	}
	
	public function init()
	{
		$this->order_status = 'pending';
		$this->valid_recaptcha = validate_recaptcha();

		$this->name = __('Wire Transfer', 'dynamicpackages');
		$this->type = 'bank';
		$this->brands = [$this->name];

		//Beneficiary
		$this->b_account_number = get_option($this->id);	
		$this->b_account_name = get_option($this->id . '_b_account_name');
		$this->b_bank = get_option($this->id . '_b_bank');
		$this->b_bank_address = get_option($this->id . '_b_bank_address');
		$this->b_bank_swift = get_option($this->id . '_b_bank_swift');
		$this->b_bank_iban = get_option($this->id . '_b_bank_iban');
		
		//Intermediary
		$this->i_account_number = get_option($this->id . '_i_account_number');
		$this->i_account_name = get_option($this->id . '_i_account_name');
		$this->i_bank = get_option($this->id . '_i_bank');
		$this->i_bank_address = get_option($this->id . '_i_bank_address');
		$this->i_bank_swift = get_option($this->id . '_i_bank_swift');
		$this->i_bank_iban = get_option($this->id . '_i_bank_iban');		
		
		//controls
		$this->show = get_option($this->id . '_show');
		$this->min = get_option($this->id . '_min');
		$this->color = '#fff';
		$this->background_color = '#262626';
		$this->icon = '<span class="dashicons dashicons-admin-site"></span>';
		$this->gateway_coupon = 'WIRE';
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
				add_filter('dy_order_status', function(){
					return $this->order_status;
				});
			}
		}
	}

	public function subject()
	{
		return sprintf(__('%s, %s sent you a payment request for %s using %s - %s', 'dynamicpackages'), secure_post('first_name'), get_bloginfo('name'), wrap_money_full(dy_utilities::total()), sanitize_text_field($this->name), secure_post('title'));
	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name);
	}

	public function is_active()
	{
		$output = false;
		$cache_key = $this->id.'_is_active';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(!empty($this->b_account_number))
		{
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}

	public function show()
	{
		$output = false;
		$cache_key = $this->id.'_show';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(is_singular('packages') && $this->is_active())
		{
			if($this->is_valid())
			{
				
				$output = true;
			}
		}
		
        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	public function is_request_submitted()
	{
		$output = false;
		$cache_key = $this->id . '_is_valid_request';
		global $dy_request_invalids;

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(is_confirmation_page() && !isset($dy_request_invalids))
		{
			if(secure_post('dy_request') === $this->id && dy_utilities::payment_amount() > 1)
			{
				$output = true;	
			}
		}
		
        //store output in $cache
        self::$cache[$cache_key] = $output;
		
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
			$title = esc_html(__('Pay With an International Wire Transfer', 'dynamicpackages'));
		}
		return $title;
	}
	public function message($message)
	{
		
		$amount = wrap_money_full(dy_utilities::payment_amount());
		$label = __('payment', 'dynamicpackages');
		
		if(dy_validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}		
		
		$message .= '<p class="large">'.__('To complete the booking please send us the', 'dynamicpackages');
		$message .= ' '.$label.' (';
		$message .= $amount;
		$message .= ') '. __('to the following account', 'dynamicpackages').'.</p>';
		$message .= '<div class="large dy_pad">'.$this->account().'</div>';
		
		return $message;
	}
	
	public function account()
	{
		$output = '<p><strong>'.esc_html(__('Beneficiary Bank', 'dynamicpackages')).'</strong><br/>';
		
		if(!empty($this->b_bank))
		{
			$output .= esc_html(__('Beneficiary Bank Name', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank).'</strong><br/>';
		}
		
		if(!empty($this->b_bank_address))
		{
			$output .= esc_html(__('Beneficiary Bank Address', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank_address).'</strong><br/>';
		}

		if(!empty($this->b_bank_swift))
		{
			$output .= esc_html(__('Beneficiary Bank Swift', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank_swift).'</strong><br/>';
		}		
		
		if(!empty($this->b_account_name))
		{
			$output .= esc_html(__('Beneficiary Account Name', 'dynamicpackages')).': <strong>'.esc_html($this->b_account_name).'</strong><br/>';
		}		
		
		if(!empty($this->b_account_number))
		{
			$output .= esc_html(__('Beneficiary Account Number', 'dynamicpackages')).': <strong>'.esc_html($this->b_account_number).'</strong><br/>';
		}
		
		if(!empty($this->b_bank_iban))
		{
			$output .= esc_html(__('Beneficiary Account IBAN', 'dynamicpackages')).': <strong>'.esc_html($this->b_bank_iban).'</strong><br/>';
		}		
		
		$output .= '<br/><strong>'.esc_html(__('Intermediary Bank', 'dynamicpackages')).'</strong><br/>';
		
		if(!empty($this->i_bank))
		{
			$output .= esc_html(__('Intermediary Bank Name', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank).'</strong><br/>';
		}
		
		if(!empty($this->i_bank_address))
		{
			$output .= esc_html(__('Intermediary Bank Address', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank_address).'</strong><br/>';
		}

		if(!empty($this->i_bank_swift))
		{
			$output .= esc_html(__('Intermediary Bank Swift', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank_swift).'</strong><br/>';
		}		
		
		if(!empty($this->i_account_name))
		{
			$output .= esc_html(__('Intermediary Account Name', 'dynamicpackages')).': <strong>'.esc_html($this->i_account_name).'</strong><br/>';
		}		
		
		if(!empty($this->i_account_number))
		{
			$output .= esc_html(__('Intermediary Account Number', 'dynamicpackages')).': <strong>'.esc_html($this->i_account_number).'</strong><br/>';
		}
		
		if(!empty($this->i_bank_iban))
		{
			$output .= esc_html(__('Intermediary Account IBAN', 'dynamicpackages')).': <strong>'.esc_html($this->i_bank_iban).'</strong><br/>';
		}
		
		$output .= '</p>';
		
		return $output;
	}

	public function is_valid()
	{
		$output = false;
		$cache_key = $this->id . '_is_valid';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if($this->is_active() )
		{
			$min = floatval($this->min);
			$show = intval($this->show);
			$payment = package_field('package_payment');
			$deposit = floatval(dy_utilities::get_deposit());
			
			if(is_booking_page() || is_confirmation_page())
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
		
        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	public function settings_init()
	{
		//Beneficiary
		register_setting($this->id . '_settings', $this->id, 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_b_account_name', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_b_bank', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_b_bank_address', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_b_bank_swift', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_b_bank_iban', 'sanitize_text_field');
		
		//Intermediary
		register_setting($this->id . '_settings', $this->id . '_i_account_number', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_i_account_name', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_i_bank', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_i_bank_address', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_i_bank_swift', 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_i_bank_iban', 'sanitize_text_field');		
		
		//controls
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_min', 'floatval');
		
		
		//section
		add_settings_section(
			$this->id . '_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);		
		
		//section
		add_settings_section(
			$this->id . '_b_section', 
			esc_html(__( 'Beneficiary Bank Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);
		
		//section
		add_settings_section(
			$this->id . '_i_section', 
			esc_html(__( 'Intermediary Bank Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);		
		
		
		
		//Beneficiary		
		add_settings_field( 
			$this->id . '_b_bank', 
			esc_html(__( 'Beneficiary Bank Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_b_section', $this->id . '_b_bank'
		);
		add_settings_field( 
			$this->id . '_b_bank_address', 
			esc_html(__( 'Beneficiary Bank Address', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_b_section', $this->id . '_b_bank_address'
		);
		add_settings_field( 
			$this->id . '_b_bank_swift', 
			esc_html(__( 'Beneficiary Bank Swift', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_b_section', $this->id . '_b_bank_swift'
		);				
		add_settings_field( 
			$this->id . '_b_account_name', 
			esc_html(__( 'Beneficiary Account Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_b_section', $this->id . '_b_account_name'
		);			
		
		add_settings_field( 
			$this->id, 
			esc_html(__( 'Beneficiary Account Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_b_section', $this->id
		);
		add_settings_field( 
			$this->id . '_b_bank_iban', 
			esc_html(__( 'Beneficiary Account IBAN', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_b_section', $this->id . '_b_bank_iban'
		);	
		
		//Intermediary			
		add_settings_field( 
			$this->id . '_i_bank', 
			esc_html(__( 'Intermediary Bank Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_i_section', $this->id . '_i_bank'
		);
		add_settings_field( 
			$this->id . '_i_bank_address', 
			esc_html(__( 'Intermediary Bank Address', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_i_section', $this->id . '_i_bank_address'
		);
		add_settings_field( 
			$this->id . '_i_bank_swift', 
			esc_html(__( 'Intermediary Bank Swift', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_i_section', $this->id . '_i_bank_swift'
		);		
		add_settings_field( 
			$this->id . '_i_account_name', 
			esc_html(__( 'Intermediary Account Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_i_section', $this->id . '_i_account_name'
		);			
		
		add_settings_field( 
			$this->id . '_i_account_number', 
			esc_html(__( 'Intermediary Account Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_i_section', $this->id . '_i_account_number'
		);
		add_settings_field( 
			$this->id . '_i_bank_iban', 
			esc_html(__( 'Intermediary Account IBAN', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_i_section', $this->id . '_i_bank_iban'
		);			

		//controls
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


	public function add_gateway($array)
	{
		$add = false;
		

		if($this->show())
		{
			if(is_singular('packages'))
			{
				$add = true;
			}
			
			if($this->valid_recaptcha && is_confirmation_page() && dy_validators::validate_request())
			{
				if(in_array(secure_post('dy_request'), ['estimate_request', apply_filters('dy_fail_checkout_gateway_name', null)]))
				{
					$add = true;
				}	
			}
		}
		
		if($add)
		{
			$array[$this->id] = array(
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'color' => $this->color,
                'background_color' => $this->background_color,
				'brands' => $this->brands,
'branding' => $this->branding(),
'icon' => $this->icon,
'gateway_coupon' => $this->gateway_coupon
            );
		}
		
		return $array;	
	}

	public function branding()
	{
		return '<p class="text-muted large"><span class="dashicons dashicons-admin-site"></span> '.$this->name.'</p>';
	}	
}