<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class paypal_me {
	
	private static $cache = [];

	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->id = 'paypal_me';
		add_action('dy_prepare_gateway_submission_' . $this->id, array($this, 'prepare_submission'));
		add_action('init', array($this, 'init'));
		add_action( 'admin_init', array($this, 'settings_init'), 1);
		add_action('admin_menu', array($this, 'add_settings_page'), 100);	
		add_filter('dy_request_the_content', array($this, 'filter_content'), 101);
		add_filter('dy_request_the_title', array($this, 'title'), 101);
		add_filter('dy_list_gateways', array($this, 'add_gateway'), 2);
		add_filter('dy_lead_event_gateways', array($this, 'lead_event_gateways'));
	}
	
	public function init()
	{
		$this->order_status = 'pending';
		$this->name = 'Paypal';
		$this->brands = [$this->name];
		$this->domain = 'paypal.me';
		$this->type = 'alt';	
		$this->username    = (string) get_option($this->id, '');
		$this->show = (int) get_option($this->id . '_show', 0);
		$this->min = (float) get_option($this->id . '_min', 0.0);
		$this->max  = (float) get_option($this->id . '_max', 0.0);
		$this->color = '#000';
		$this->background_color = '#FFD700';
		$this->plugin_dir_url = plugin_dir_url(__DIR__);
		$this->icon = '<span class="dashicons dashicons-cart"></span>';
		$this->gateway_coupon = 'PAYPAL';

		//service fee
		$this->service_fee = (float) get_option($this->id . '_service_fee', 0.0);
		$has_service_fee = $this->service_fee > 0.0;

		$this->percent_symbol = '%';
		$this->service_fee_notification = $has_service_fee 
			? '<p class="large"><strong>'.esc_html(sprintf(__('All payments made with PayPal are subject to a an additional %s%s service fee.', 'dynamicpackages'), $this->service_fee, $this->percent_symbol)).'</strong></p>' 
			: '';
		$this->service_fee_confirmation = $has_service_fee 
			? '<p class="large"><strong>'.esc_html(sprintf(__('This price already includes an additional %s%s Paypal payment service fee.', 'dynamicpackages'), $this->service_fee, $this->percent_symbol)).'</strong></p>' 
			: '';
	}

	public function prepare_submission($submission_context)
	{
		if(!$this->is_request_submitted())
		{
			return;
		}

		$submission_context->accepted = true;

		add_filter('dy_email_notes', array($this, 'message'));
		add_filter('dy_email_label_notes', array($this, 'label_notes'));
		add_filter('dy_email_intro', array($this, 'subject'));
		add_filter('dy_email_subject', array($this, 'subject'));
		add_filter('dy_order_status', function(){
			return $this->order_status;
		});
	}

	public function subject()
	{
		$cache_key = $this->id . '_subject_' . secure_post('unique_tx_id');
		
		if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }
        
		return self::$cache[$cache_key] = sprintf(__('%s, %s sent you a payment request for %s using %s - %s', 'dynamicpackages'), secure_post('first_name'), get_bloginfo('name'), wrap_money_full(dy_utilities::total()), sanitize_text_field($this->name), secure_post('title'));
	}
	
	public function label_notes()
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name);
	}
	
	public function filter_content($content)
	{
		if(in_the_loop() && dy_validators::validate_request() && $this->is_request_submitted())
		{
			

			if(validate_turnstile())
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
			$title = esc_html(__('Thank you for choosing Paypal', 'dynamicpackages'));
		}
		return $title;
	}
	
	
	public function is_active()
	{
		$output = false;
		$cache_key = $this->id.'_is_active';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!empty($this->username))
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
		
        if (array_key_exists($cache_key, self::$cache)) {
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

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		global $dy_request_invalids;
		
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
	
	public function is_valid()
	{
		$output = false;
		$cache_key = $this->id . '_is_valid';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if($this->is_active() )
		{
			$max = floatval($this->max);
			$min = (empty($this->min)) ? 0 : floatval($this->min);
			$show = intval($this->show);
			$payment = package_field('package_payment');
			
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
			
			if($total >= $min && $total <= $max)
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
		register_setting($this->id . '_settings', $this->id, 'sanitize_user');
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_max', 'floatval');
		register_setting($this->id . '_settings', $this->id . '_min', 'floatval');
		register_setting($this->id . '_settings', $this->id . '_service_fee', 'floatval');

		
		add_settings_section(
			$this->id . '_settings_section', 
			__( 'General Settings', 'dynamicpackages' ), 
			'', 
			$this->id . '_settings'
		);
		
		add_settings_field( 
			$this->id, 
			__( 'Username', 'dynamicpackages' ), 
			['dy_input_controller', 'text'], 
			$this->id . '_settings', 
			$this->id . '_settings_section',
			[
				'key' => $this->id,
			]
		);

		add_settings_field( 
			$this->id . '_min', 
			__( 'Min. Amount', 'dynamicpackages' ), 
			['dy_input_controller', 'price'], 
			$this->id . '_settings', 
			$this->id . '_settings_section',
			[
				'key'    => $this->id . '_min',
				'append' => currency_symbol(),
			]
		);

		add_settings_field( 
			$this->id . '_max', 
			__( 'Max. Amount', 'dynamicpackages' ), 
			['dy_input_controller', 'price'], 
			$this->id . '_settings', 
			$this->id . '_settings_section', 
			[
				'key'    => $this->id . '_max',
				'append' => currency_symbol(),
			]
		);

		add_settings_field( 
			$this->id . '_service_fee', 
			__( 'Service Fee', 'dynamicpackages' ), 
			['dy_input_controller', 'percentage'], 
			$this->id . '_settings', 
			$this->id . '_settings_section',
			[
				'key'    => $this->id . '_service_fee',
				'append' => '%',
			]
		);

		add_settings_field( 
			$this->id . '_show', 
			__( 'Show', 'dynamicpackages' ), 
			['dy_select_controller', 'custom'], 
			$this->id . '_settings', 
			$this->id . '_settings_section',
			[
				'key' => $this->id . '_show',
				'options' => [
					0 => __('Full Payments and Deposits', 'dynamicpackages'),
					1 => __('Only Deposits', 'dynamicpackages'),
				],
			]
		);		
	}

	public function add_settings_page()
	{
		add_submenu_page( $this->plugin_id, $this->name, '💸 '. $this->name, 'manage_options', $this->id, array($this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html_e($this->domain); ?></h1>	
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
			
			if(validate_turnstile() && is_confirmation_page() && dy_validators::validate_request())
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
		
		$output = '<img src="'.esc_url($this->plugin_dir_url.'assets/'.$this->id.'.svg').'" width="205" height="50" alt="'.esc_attr($this->name).'" />';
		$output .= $this->service_fee_notification;

		return $output;
	}
	
	public function message($message)
	{
		$amount = money(dy_utilities::payment_amount($this->service_fee));
		$url = 'https://'.$this->domain.'/'.$this->username.'/'.$amount;
		$amount = wrap_money_full($amount);
		
		$label = __('full payment of', 'dynamicpackages');
		
		if(dy_validators::has_deposit())
		{
			$label = __('deposit of', 'dynamicpackages');
		}	
		
		$message .= $this->service_fee_confirmation;
		$message .= '<p class="large">'.esc_html(__('To complete the booking please click on the following link and enter your Paypal account.', 'dynamicpackages')).'</p>';
		$message .= '<p class="large">'.esc_html(sprintf(__('Please send us the %s %s to complete these booking.', 'dynamicpackages'), $label, $amount)).'</p>';		
		$message .= '<p style="margin-bottom: 40px;"><a target="_blank" style="border: 16px solid #FFD700; text-align: center; background-color: '.esc_html($this->background_color).'; color: '.esc_html($this->color).'; font-size: 18px; line-height: 18px; display: block; width: 100%; box-sizing: border-box; text-decoration: none; font-weight: 900;" href="'.esc_url($url).'">'.esc_html(__('Pay with Paypal', 'dynamicpackages').' '.__('now', 'dynamicpackages')).'</a></p>';		

		return $message;
	}	

	public function lead_event_gateways($arr = array()) {
		$arr[] = $this->id;

		return $arr;
	}
}