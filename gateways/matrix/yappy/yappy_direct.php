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
			add_filter('coupon_gateway', array(&$this, 'single_coupon'), 10, 3);
		}		
	}
	
	public function args()
	{
		$this->name = 'Yappy';
		$this->id = 'yappy_direct';		
		$this->gateway_short_name = 'yappy';
		$this->type = 'alt';	
		$this->number = get_option($this->id);
		$this->business = get_option($this->id . '_business');
		$this->max = get_option($this->id . '_max');
		$this->show = get_option($this->id . '_show');
		$this->color = '#fff';
		$this->background_color = '#013685';
		$this->plugin_dir_url = plugin_dir_url(__DIR__);
	}
	
	public function send_data()
	{		
		if(dy_validators::validate_request() && $this->is_valid_request())
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
		return sprintf(__('%s, %s sent you a payment request for %s%s using %s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), dy_utilities::currency_symbol(), dy_utilities::currency_format(dy_utilities::total()), sanitize_text_field($this->name), sanitize_text_field($_POST['title']));
	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name);
	}	
	
	public function is_active()
	{
		$output = false;
		$which_var = $this->id . '_is_active';
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = true;
		}
		else
		{
			if($this->number != '' || $this->business != '')
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
		$which_var = $this->id.'_show';
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
		$which_var = $this->id . '_is_valid_request';
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
				if($_POST['dy_request'] == $this->id && dy_utilities::payment_amount() > 1)
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
		if(in_the_loop() && dy_validators::validate_request() && $this->is_valid_request())
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
		if(in_the_loop() && dy_validators::validate_request() && $this->is_valid_request())
		{
			$title = esc_html(__('Thank you for using Yappy', 'dynamicpackages'));
		}
		return $title;
	}
	
	public function message($message)
	{
		$payment_amount = dy_utilities::payment_amount();
		$currency_symbol = dy_utilities::currency_symbol();
		$destination = ($this->business != '') ? strtoupper($this->business) : $this->number;
		$first = __('To complete the booking please enter your Yappy App and send the', 'dynamicpackages');
		$last = ($this->business != '')? __('to the business', 'dynamicpackages') : __('to the number', 'dynamicpackages');
		$amount = $currency_symbol.number_format($payment_amount, 2, '.', ',');
		$label = __('payment', 'dynamicpackages');
		
		
		if(dy_validators::has_deposit())
		{
			$label = __('deposit', 'dynamicpackages');
		}
		
		$message .= '<p class="large">'.esc_html($first.' '.$label.' ('.$amount.') '.$last).' <strong>'.esc_html($destination).'</strong>.</p>';	
		$message .= '<p class="large dy_pad padding-10 strong">'.esc_html(__('Send', 'dynamicpackages').' '.$amount.' '.$last.' '. $destination).'</p>';
		
		return $message;
	}

	public function is_valid()
	{
		$output = false;
		$which_var = $this->id . '_is_valid';
		global $$which_var;
		
		if(isset($$which_var))
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
				$GLOBALS[$which_var] = true;
			}
		}
		return $output;
	}

	public function settings_init()
	{
		//Yappy
		
		register_setting($this->id . '_settings', $this->id, 'intval');
		register_setting($this->id . '_settings', $this->id . '_business', 'esc_html');
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
	}
	
	public function input_text($name){
		$option = get_option($name);
		?>
		<input type="text" name="<?php esc_html_e($name); ?>" id="<?php esc_html_e($name); ?>" value="<?php esc_html_e($option); ?>" />
		<?php
	}
	public function input_number($name){
		$option = get_option($name);
		?>
		<input type="number" name="<?php esc_html_e($name); ?>" id="<?php esc_html_e($name); ?>" value="<?php esc_html_e($option); ?>" /> #
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
		add_submenu_page( 'edit.php?post_type=packages', $this->name, $this->name, 'manage_options', $this->id, array(&$this, 'settings_page'));
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
			$output .= ' <button data-type="'.esc_attr($this->type).'"  data-id="'.esc_attr($this->id).'" data-branding="'.esc_attr($this->branding()).'" style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';"  class="pure-button bottom-20 with_'.esc_html($this->id).' rounded" type="button"><img alt="yappy" width="21" height="12" src="'.esc_url($this->plugin_dir_url.'assets/alt/'.$this->id.'_icon.svg').'" /> '.esc_html($this->name).'</button>';			
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
		
		if(isset($dy_valid_recaptcha) && isset($_POST['dy_request']) && dy_validators::validate_request())
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
		return '<img src="'.esc_url($this->plugin_dir_url.'assets/alt/'.$this->id.'.svg').'" width="80" height="69" alt="'.esc_attr($this->name).'" />';
	}

	
	public function single_coupon($str, $gateway)
	{
		if(strtolower($gateway) == $this->gateway_short_name)
		{
			$str = '<aside class="clearfix"><img class="inline-block pull-left" style="vertical-align: middle; margin-right: 10px;" width="40" height="40" alt="yappy" src="'.esc_url($this->plugin_dir_url.'assets/alt/'.$this->id.'_icon.svg').'" /><span class="semibold">'.esc_html(sprintf(__('Pay with %s', 'dynamicpackages'), $this->name)).'.</span> '.$str.'</aside>';
		}
		
		return $str;
	}
}