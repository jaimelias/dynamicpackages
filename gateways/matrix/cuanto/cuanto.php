<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class cuanto{

	private static $cache = [];
	
	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->id = 'cuanto';
		add_action('init', array(&$this, 'init'));
		add_action( 'admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 100);	
		add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
		add_filter('wp_headers', array(&$this, 'send_data'));
		add_filter('gateway_buttons', array(&$this, 'button'), 3);
		add_filter('list_gateways', array(&$this, 'add_gateway'), 2);	
	}
	
	public function init()
	{
		$this->order_status = 'pending';
		$this->valid_recaptcha = validate_recaptcha();
		$this->name = 'Cuanto.app';
		$this->methods_o = __('Mastercard or Visa', 'dynamicpackages');
		$this->methods_arr = ['Mastercard', 'Visa'];
		$this->type = 'card-off-site';
		$this->domain = 'cuanto.app';		
		$this->username = get_option($this->id);
		$this->show = get_option($this->id . '_show');
		$this->max = get_option($this->id . '_max');
		$this->color = '#000';
		$this->background_color = '#8CD0C5';
		$this->plugin_dir_url = plugin_dir_url(__DIR__);
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
		return sprintf(__('%s, %s sent you a payment request for %s%s using %s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), currency_symbol(), money(dy_utilities::total()), sanitize_text_field($this->name), sanitize_text_field($_POST['title']));
	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name);
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
	
	
	public function is_active()
	{
		$output = false;
		$cache_key = $this->id.'_is_active';
		
        if (isset(self::$cache[$cache_key])) {
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
		register_setting($this->id . '_settings', $this->id, 'sanitize_user');
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_max', 'floatval');
		
		add_settings_section(
			$this->id . '_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);
		
		add_settings_field( 
			$this->id, 
			esc_html(__( 'Username', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id
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
		add_submenu_page( $this->plugin_id, $this->name, '💸 '.$this->name, 'manage_options', $this->id, array(&$this, 'settings_page'));
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
		if($this->show() && array_intersect($this->list_gateways_cb(), $this->methods_arr))
		{
			$output .= ' <button data-type="'.esc_attr($this->type).'"  data-id="'.esc_attr($this->id).'" data-branding="'.esc_attr($this->branding()).'" style="color: '.esc_attr($this->color).'; background-color: '.esc_attr($this->background_color).';" class="pure-button bottom-20 rounded" type="button"><span class="dashicons dashicons-cart"></span> '.esc_html($this->methods_o).'</button>';
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
			array_push($array, ...$this->methods_arr);
		}
		
		return $array;	
	}


	public function branding()
	{
		$output = '<p><img src="'.esc_url($this->plugin_dir_url.'assets/visa-mastercard.svg').'" width="250" height="50" /></p>';
		$output .= '<p class="large text-muted">'.esc_html(sprintf(__('Pay with %s thanks to %s', 'dynamicpackages'), $this->methods_o, $this->name)).'</p>';
		return $output;
	}
	
	public function message($message)
	{
		$payment_amount = dy_utilities::payment_amount();
		$formated_amount = intval($payment_amount * 100);
		$url = 'https://'.$this->domain.'/'.$this->username.'/c/'.$formated_amount;
		$amount = currency_symbol().''.money($payment_amount);
		
		$label = __('full payment of', 'dynamicpackages');
		
		if(dy_validators::has_deposit())
		{
			$label = __('deposit of', 'dynamicpackages');
		}	
		
		$message .= '<p class="large">'.esc_html(__('To complete the booking please click on the following link. We are also going to send you this same information by email.', 'dynamicpackages')).'</p>';
		$message .= '<p class="large">'.esc_html(sprintf(__('Please send us the %s %s to complete these booking.', 'dynamicpackages'), $label, $amount)).'</p>';		
		$message .= '<p style="margin-bottom: 40px;"><a target="_blank" style="border: 16px solid #8CD0C5; text-align: center; background-color: '.esc_html($this->background_color).'; color: '.esc_html($this->color).'; font-size: 18px; line-height: 18px; display: block; width: 100%; box-sizing: border-box; text-decoration: none; font-weight: 900;" href="'.esc_url($url).'"><span class="dashicons dashicons-cart"></span> '.esc_html(sprintf(__('Pay with %s', 'dynamicpackages'), $this->methods_o)).'</a></p>';

		return $message;  
	}	
}