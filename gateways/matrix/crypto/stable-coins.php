<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class stable_coins {

    private static $cache = [];

	private const NETWORKS = array(
		'trx' => array('name' => 'Tron (TRC-20)'),
		'eth' => array('name' => 'Ethereum (ERC-20) Network'),
		'bsc' => array('name' => 'Binance Smart Chain (BEP-20)'),
		'matic' => array('name' => 'Poligon (MATIC) Network'),
		'sol' => array('name' => 'Solana Network'),
		'avax' => array('name' => 'Avalanche Network'),
	);

	private const ASSETS = array(
		'usdt' => array(
			'name' => 'Tether (USDT)',
			'background_color' => '#50AF95',
			'networks' => array('trx', 'eth', 'bsc', 'matic', 'sol', 'avax'),
		),
		'usdc' => array(
			'name' => 'USD Coin (USDC)',
			'background_color' => '#2775CA',
			'networks' => array('eth', 'bsc', 'matic', 'sol', 'avax'),
		),
	);

    public function __construct($plugin_id, $id)
    {
        if (!isset(self::ASSETS[$id])) {
            throw new InvalidArgumentException('Unsupported stable USD gateway.');
        }

        $this->plugin_id = $plugin_id;
        $this->id = $id;

		add_action('dy_prepare_gateway_submission_' . $this->id, array($this, 'prepare_submission'));
		add_action('init', array(&$this, 'init'));
		add_action( 'admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 100);	
		add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
		add_filter('dy_request_the_title', array(&$this, 'title'), 101);
		add_filter('dy_list_gateways', array(&$this, 'add_gateway'), 2);
		add_filter('dy_lead_event_gateways', array(&$this, 'lead_event_gateways'));
    }

    public function init()
    {
        $config = self::ASSETS[$this->id];

        $this->order_status = 'pending';
        $this->name = $config['name'];
        $this->brands = array($this->name);
        $this->type = 'crypto';
        $this->all_networks = $this->get_all_networks();
        $this->enabled_networks = $this->get_enabled_networks();
        $this->show = get_option($this->id . '_show');
        $this->max = get_option($this->id . '_max');
        $this->color = '#fff';
        $this->background_color = $config['background_color'];
        $this->plugin_dir_url = plugin_dir_url(__DIR__);
        $this->gateway_coupon = strtoupper($this->id);

        $this->icon = sprintf(
            '<img width="15" height="15" src="%s" alt="%s" />',
            esc_url($this->plugin_dir_url . 'assets/' . $this->id . '_icon.svg'),
            esc_attr($this->name)
        );
    }

    public function get_all_networks()
    {
        $networks = array();

        foreach (self::ASSETS[$this->id]['networks'] as $network_id) {
            $networks[$network_id] = self::NETWORKS[$network_id];
        }

        return $networks;
    }

	public function get_enabled_networks()
	{
		$output = [];

		foreach($this->all_networks as $key => $value)
		{
			if(!empty(get_option($this->id . '_' . $key)))
			{
				$output[$key] = $value;
			}
		}

		return $output;
	}

	public function prepare_submission($submission_context)
	{
		if(!$this->is_request_submitted())
		{
			return;
		}

		$submission_context->accepted = true;

		add_filter('dy_email_notes', array(&$this, 'message'));
		add_filter('dy_email_label_notes', array(&$this, 'label_notes'));
		add_filter('dy_email_intro', array(&$this, 'subject'));
		add_filter('dy_email_subject', array(&$this, 'subject'));
		add_filter('dy_order_status', function(){
			return $this->order_status;
		});
	}

	public function subject()
	{
		return sprintf(__('%s, %s sent you a payment request for %s using %s - %s', 'dynamicpackages'), secure_post('first_name'), get_bloginfo('name'), wrap_money_full(dy_utilities::total()), sanitize_text_field($this->name), secure_post('title'));
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
			$title = esc_html(sprintf(__('You have chosen %s as your payment method!', 'dynamicpackages'), $this->name));
		}
		return $title;
	}
	
	
	public function is_active()
	{
		$output = false;
		$cache_key = $this->id.'_is_active';

		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$active_networks = false;

		foreach (array_keys($this->all_networks) as $key) {
			if (!empty(get_option("{$this->id}_{$key}"))) {
				$active_networks = true;
				break;
			}
		}

		if($active_networks)
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
		
		if(is_confirmation_page() && isset($_POST['dy_network']) && !isset($dy_request_invalids))
		{
			$network = secure_post('dy_network', '', 'sanitize_key');

			if(secure_post('dy_request') === $this->id && dy_utilities::payment_amount() > 1 && array_key_exists($network, $this->enabled_networks))
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
				'networks' => $this->enabled_networks,
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
		return '<img src="'.esc_url($this->plugin_dir_url.'assets/'.$this->id.'.svg').'" width="50" height="50" alt="'.esc_attr($this->name).'" />';
	}
	
	public function message($message)
	{
		$amount = wrap_money_full(dy_utilities::payment_amount());
		$network = secure_post('dy_network', '', 'sanitize_key');
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
	public function lead_event_gateways($arr = array()) {
		$arr[] = $this->id;

		return $arr;
	}

}

