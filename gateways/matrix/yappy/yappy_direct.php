<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class yappy_direct {

	private static $cache = [];
	
	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->valid_recaptcha = validate_recaptcha();
		add_action('init', array(&$this, 'init'));
		add_action( 'admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 100);	
		add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
		add_filter('dy_request_the_title', array(&$this, 'title'), 101);
		add_filter('wp', array(&$this, 'send_data'));
		add_filter('dy_list_gateways', array(&$this, 'add_gateway'), 3);
		add_filter('coupon_gateway', array(&$this, 'single_coupon'), 10, 3);
	}
	
	public function init()
	{
		$this->order_status = 'pending';
		$this->name = 'Yappy';
		$this->brands = [$this->name];
		$this->id = 'yappy_direct';		
		$this->gateway_short_name = 'yappy';
		$this->type = 'alt';	
		$this->number = get_option($this->id);
		$this->business = get_option($this->id . '_business');
		$this->max = get_option($this->id . '_max');
		$this->show = get_option($this->id . '_show');
		$this->qrcode = get_option($this->id . '_qrcode');
		$this->color = '#fff';
		$this->background_color = '#013685';
		$this->plugin_dir_url = plugin_dir_url(__DIR__);
		$this->icon = '<img alt="yappy" width="21" height="12" src="'.esc_url($this->plugin_dir_url.'assets/'.$this->id.'_icon.svg').'" />';
		$this->gateway_coupon = 'YAPPY';
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
		return sprintf(__('%s, %s sent you a payment request for %s using %s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), wrap_money_full(dy_utilities::total()), sanitize_text_field($this->name), sanitize_text_field($_POST['title']));
	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name);
	}	
	
	public function is_active()
	{
		$output = false;
		$cache_key = $this->id . '_is_active';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(!empty($this->number) || !empty($this->business))
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

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		global $dy_request_invalids;
		

		if(is_checkout_page() && !isset($dy_request_invalids))
		{
			if($_POST['dy_request'] === $this->id && dy_utilities::payment_amount() > 1)
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

				if(!empty($this->qrcode))
				{
					$payment_amount = money(dy_utilities::payment_amount());
					$amount = wrap_money_full($payment_amount);

					$label = __('payment', 'dynamicpackages');
		
					if(dy_validators::has_deposit())
					{
						$label = __('deposit', 'dynamicpackages');
					}

					$content .= '<p class="large dy_pad padding-10">'.esc_html(sprintf(__('B. Alternatively, you can also scan the following QR code within your Banco General Yappy app and then send us the %s (%s).', 'dynamicpackages'), $label, $amount)).'</p>';
					$content .= '<p><img width="250" height="250" src="'.esc_url($this->qrcode).'" alt="yappy qrcode"/></p>';
				}
			}
		}
		return $content;
	}
	public function title($title)
	{
		if(in_the_loop() && dy_validators::validate_request() && $this->is_request_submitted())
		{
			$title = esc_html(sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name));
		}
		return $title;
	}
	
	public function message($message)
	{
		$destination = (!empty($this->business)) ? '@'.strtoupper($this->business) : $this->number;
		$destination = '<strong>'.esc_html($destination).'</strong>';
		$amount = wrap_money_full(dy_utilities::payment_amount());
		$label = (dy_validators::has_deposit()) ? esc_html(__('deposit', 'dynamicpackages')) : esc_html(__('payment', 'dynamicpackages'));
		
		$text = (!empty($this->business)) 
			? sprintf(__('A. Find us at the Yappy Business Directory by the company name %s and then send us the %s (%s).', 'dynamicpackages'), $destination, $label, $amount)
			: sprintf(__('A. Send the %s (%s) to the Yappy number %s.', 'dynamicpackages'), $label, $amount, $destination);
		
		$message = '<p><strong>'.esc_html(__('Step', 'dynamicpackages')).' 1:</strong></p>';
		$message .= '<p class="large dy_pad padding-10">'.esc_html(__('Enter your Banco General Yappy app.', 'dynamicpackages')).'</p>';
		$message .= '<p><strong>'.esc_html(__('Step', 'dynamicpackages')).' 2:</strong></p>';
		$message .= '<p class="large dy_pad padding-10">'.$text.'</p>';


		return $message;
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
		
        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	public function settings_init()
	{
		//Yappy
		
		register_setting($this->id . '_settings', $this->id, 'intval');
		register_setting($this->id . '_settings', $this->id . '_business', 'esc_html');
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_max', 'floatval');
		register_setting($this->id . '_settings', $this->id . '_qrcode', 'esc_url');
		
		add_settings_section(
			$this->id . '_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);
		
		add_settings_field( 
			$this->id, 
			esc_html(__( 'Yappy Cell Phone Number', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id
		);	
		
		add_settings_field( 
			$this->id . '_business', 
			esc_html(__( 'Yappy Business Name', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id . '_business'
		);			
		add_settings_field( 
			$this->id . '_max', 
			esc_html(__( 'Max. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id . '_max'
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
			$this->id . '_settings_section',
			$show_args
		);
		
		add_settings_field( 
			$this->id . '_qrcode', 
			esc_html(__( 'QR Code Url', 'dynamicpackages' )), 
			array(&$this, 'input_url'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id . '_qrcode'
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
	public function input_url($name){
		$option = get_option($name);
		?>
		<input type="url" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($option); ?>" />
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
		add_submenu_page($this->plugin_id, $this->name, '💸 '. $this->name, 'manage_options', $this->id, array(&$this, 'settings_page'));
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
			
			if($this->valid_recaptcha && is_checkout_page() && dy_validators::validate_request())
			{
				if($_POST['dy_request'] == 'estimate_request' || $_POST['dy_request'] == apply_filters('dy_fail_checkout_gateway_name', null))
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
		return '<img src="'.esc_url($this->plugin_dir_url.'assets/'.$this->id.'.svg').'" width="80" height="69" alt="'.esc_attr($this->name).'" />';
	}

	
	public function single_coupon($str, $gateway)
	{
		if(strtolower($gateway) == $this->gateway_short_name)
		{
			$str = '<aside class="clearfix"><img class="inline-block pull-left" style="vertical-align: middle; margin-right: 10px;" width="40" height="40" alt="yappy" src="'.esc_url($this->plugin_dir_url.'assets/'.$this->id.'_icon.svg').'" /><span class="semibold">'.esc_html(sprintf(__('Pay with %s', 'dynamicpackages'), $this->name)).'.</span> '.$str.'</aside>';
		}
		
		return $str;
	}
}