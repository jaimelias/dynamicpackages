<?php

if ( !defined( 'WPINC' ) ) exit;

class usdt{
	
	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		add_action('init', array(&$this, 'init'));
		add_action( 'admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 100);
		add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
		add_filter('dy_request_the_title', array(&$this, 'title'), 101);
		add_filter('wp_headers', array(&$this, 'send_data'));
		add_filter('gateway_buttons', array(&$this, 'button'), 3);
		add_filter('list_gateways', array(&$this, 'add_gateway'), 2);
	}

	public function init()
	{
		$this->valid_recaptcha = validate_recaptcha();
		$this->id = 'usdt';
		$this->name = 'Tether (USDT)';
		$this->type = 'crypto';
		$this->all_networks = $this->get_all_networks();
		$this->enabled_networks = $this->get_enabled_networks();
		$this->show = get_option($this->id . '_show');
		$this->max = get_option($this->id . '_max');
		$this->color = '#fff';
		$this->background_color = '#50AF95';
		$this->plugin_dir_url = plugin_dir_url(__DIR__);
	}

	public function get_all_networks()
	{
		return array(
			'eth' => array(
				'name' => 'Ethereum (ERC-20) Network'
			), 
			'bsc' => array(
				'name' => 'Binance Smart Chain (BEP-20)'
			), 
			'matic' => array(
				'name' => 'Poligon (MATIC) Network'
			), 
			'sol' => array(
				'name' => 'Solana Network'
			), 
			'avax' => array(
				'name' => 'Avalanche Network'
			)
		);
	}

	public function get_enabled_networks()
	{
		$output = array();

		foreach($this->all_networks as $key => $value)
		{
			if(!empty(get_option($this->id . '_' . $key)))
			{
				$output[$key] = $value;
			}
		}

		return $output;
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

	public function title($title)
	{
		if(in_the_loop() && dy_validators::validate_request() && $this->is_request_submitted())
		{
			$title = esc_html(sprintf(__('You have chosen %s as your payment method!', 'dynamicpackages'), $this->name));
		}
		return $title;
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
			$active_networks = false;

			foreach($this->all_networks as $key => $value)
			{
				if(!empty(get_option($this->id . '_' . $key)))
				{
					$active_networks = true;
					break;
				}
			}

			if($active_networks)
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
			if(is_checkout_page() && isset($_POST['dy_network']) && !isset($dy_request_invalids))
			{
				$network = sanitize_text_field($_POST['dy_network']);

				if($_POST['dy_request'] === $this->id && dy_utilities::payment_amount() > 1 && array_key_exists($network, $this->all_networks))
				{
					$output = true;
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}
		
		return $output;
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
			
			$GLOBALS[$which_var] = $output;

		}
		
		return $output;
	}

	public function settings_init()
	{		
		register_setting($this->id . '_settings', $this->id, 'sanitize_user');
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_max', 'floatval');

		foreach($this->all_networks as $key => $value)
		{
			register_setting($this->id . '_settings', $this->id . '_' . $key, 'sanitize_user');
		}
		
		add_settings_section(
			$this->id . '_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
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
		
		foreach($this->all_networks as $key => $value)
		{
			add_settings_field( 
				$this->id . '_' . $key , 
				esc_html(sprintf(__("%s Contract Address", 'dynamicpackages'), $value['name'])), 
				array(&$this, 'input_text'), 
				$this->id . '_settings', 
				$this->id . '_settings_section', $this->id . '_' . $key
			);
		}

	}
	
	public function input_text($name){
		$option = get_option($name);
		?>
		<input type="text" style="width: 450px;" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($option); ?>" />
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
			$icon = '<img width="15" height="15" src="'.esc_url($this->plugin_dir_url.'assets/crypto/'.$this->id.'_icon.svg').'" alt="'.esc_attr($this->name).'" />';

			$output .= ' <button data-type="'.esc_attr($this->type).'"  data-id="'.esc_attr($this->id).'" data-branding="'.esc_attr($this->branding()).'" data-networks="'.esc_attr(json_encode($this->enabled_networks)).'" style="color: '.esc_html($this->color).'; background-color: '.esc_html($this->background_color).';" class="pure-button bottom-20 with_'.esc_html($this->id).' rounded" type="button">'.$icon.' '.esc_html($this->name).'</button>';
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
			$array[] = $this->name;
		}
		
		return $array;	
	}
	
	public function branding()
	{
		return '<img src="'.esc_url($this->plugin_dir_url.'assets/crypto/'.$this->id.'.svg').'" width="50" height="50" alt="'.esc_attr($this->name).'" />';
	}
	
	public function message($message)
	{
		$amount = number_format(dy_utilities::payment_amount(), 2, '.', '');
		$amount = currency_symbol().''.money($amount);
		$network = sanitize_text_field($_POST['dy_network']);
		$address = get_option($this->id . '_' . $network);
		$network_name = $this->enabled_networks[$network]['name'];

		$label = __('full payment of', 'dynamicpackages');
		
		if(dy_validators::has_deposit())
		{
			$label = __('deposit of', 'dynamicpackages');
		}

		$styleAttr = ' style="padding: 10px 0; color: '.esc_attr($this->color).'; background-color: '.esc_attr($this->background_color).';" ';

		$message .= '<p class="large">'.esc_html(sprintf(__('Please send us the %s %s to complete these booking.', 'dynamicpackages'), $label, $amount)).'</p>';
		$message .= '<p class="large">'.esc_html(sprintf(__('When paying with %s you must make sure that you use the %s network.', 'dynamicpackages'), $this->name, $network_name)).'</p>';
		$message .= '<p class="large">'.esc_html(__('Our payment address is as follows:', 'dynamicpackages')).'</p>';
		$message .= '<p class="large copyToClipboard pointer" '.$styleAttr.'><strong '.$styleAttr.'>'.esc_html($address).'</strong> <span class="dashicons dashicons-clipboard"></span></p>';
		
		return $message;
	}	
}