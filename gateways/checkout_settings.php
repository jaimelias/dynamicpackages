<?php

class dynamicpackages_Settings
{
	function __construct()
	{
		$this->load_gateways();
		$this->plugin_checkout = new dynamicpackages_Checkout();
		$this->paypal_me = new paypal_me();
		$this->nequi_direct = new nequi_direct();
		$this->yappy_direct = new yappy_direct();	
		$this->bank_transfer = new bank_transfer();
		$this->wire_transfer = new wire_transfer();	
		$this->init();
	}
	
	public function init()
	{
		add_action('admin_init', array(&$this, 'load_gateways'));
		add_action('init', array(&$this, 'load_gateways'));
		add_filter('list_gateways', array(&$this, 'coupon'), 9);
		add_action('checkout_area', array(&$this, 'add_to_checkout_area'), 1);		
	}
	public static function load_gateways()
	{
		//Credit Cards Rest APIs
		require_once plugin_dir_path(__FILE__).'checkout_matrix.php';
		
		//Paypal.me
		require_once plugin_dir_path(__FILE__).'matrix/paypal/paypal_me.php';
		
		//Nequi Direct
		require_once plugin_dir_path(__FILE__).'matrix/nequi/nequi_direct.php';
		
		//Yappy Direct
		require_once plugin_dir_path(__FILE__).'matrix/yappy/yappy_direct.php';
		
		//Bank Transfer
		require_once plugin_dir_path(__FILE__).'matrix/bank/local.php';
	
		//Wire Transfer
		require_once plugin_dir_path(__FILE__).'matrix/bank/international.php';	
	}
	
	public static function is_gateway_active()
	{
		$output = false;
		global $is_gateway_active;
		
		if(isset($is_gateway_active))
		{
			$output = true;
		}
		else
		{
			if(get_option('primary_gateway') != '' && !isset($_GET['quote']))
			{
				$option = get_option('primary_gateway');
				
				if($option != '0')
				{
					$GLOBALS['is_gateway_active'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}


	public static function settings_page()
	{ 
		?><div class="wrap">
		<form action='options.php' method='post'>
			
			<h1><?php esc_html(_e("Dynamicpackages", "dynamicpackages")); ?></h1>	
			<?php
			settings_fields( 'tp_settings' );
			do_settings_sections( 'tp_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	

	

	

	public static function gateway_buttons()
	{
		return self::gateway_buttons_cb();
	}	
	public static function gateway_buttons_cb()
	{
		return apply_filters('gateway_buttons', '');
	}
	
	public static function checkout_area()
	{
		
		do_action('checkout_area');
	}
	public static function checkout_items()
	{
		do_action('checkout_items');
	}	
	public static function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	
	public static function join_gateways()
	{
		$array = self::list_gateways_cb();
		return join(' '.__('or', 'dynamicpackages').' ', array_filter(array_merge(array(join(', ', array_slice($array, 0, -1))), array_slice($array, -1)), 'strlen'));
	}
	
	public static function has_any_gateway()
	{
		$output = false;
		
		if(count(self::list_gateways_cb()) > 0 && dynamicpackages_Public::pax_num() <= package_field('package_max_persons'))
		{
			$GLOBALS['has_any_gateway'] = true;
			$output = true;
		}
		
		return $output;
	}
	public static function choose_gateway()
	{
		$output = null;
		
		if(count(self::list_gateways_cb()) > 0)
		{
			$output = __('Pay', 'dynamicpackages');
			
			if(dynamicpackages_Validators::has_deposit())
			{
				$output .= ' '.__('the deposit', 'dynamicpackages');
			}
			
			$output .= ' ('.dy_utilities::currency_symbol().'<span class="dy_calc dy_calc_total">'.number_format(dynamicpackages_Checkout::amount(), 2, '.', ',').'</span>';
			
			$output .= ') '.__('with', 'dynamicpackages');
			
			if(count(self::list_gateways_cb()) == 1)
			{
				$this_gateway = self::list_gateways_cb();
				$output .= ' '.$this_gateway[0];
			}
			else
			{
				$output .= ' '.self::join_gateways();
			}
		}
		return $output;		
	}
	public static function coupon($array)
	{
		if(is_singular('packages') && package_field('package_auto_booking') > 0)
		{	
			if(is_booking_page() && dynamicpackages_Validators::valid_coupon())
			{
				$coupon = ucwords(strtolower(sanitize_text_field($_GET['booking_coupon'])));
				
				if(in_array($coupon, $array))
				{
					$coupon = array($coupon);
					$array = array_intersect($coupon, $array);
					
				}				
			}
		}
		return $array;
	}
	
	public static function add_to_checkout_area()
	{
		$output = '';
		if(dynamicpackages_Settings::has_any_gateway())
		{
			$output .= '<p class="text-center bottom-20 large">'.self::choose_gateway().'.</p><div class="text-center bottom-20">'.self::gateway_buttons().'</div>';
		}
		
		if(intval(package_field('package_auto_booking' )) == 1 && !isset($_GET['quote']))
		{
			$gateway = dy_utilities::get_this_gateway();
			
			if($gateway)
			{
				if(array_key_exists('form', $gateway))
				{
					ob_start();
					require_once(plugin_dir_path(__FILE__).$gateway['form']);
					$output .= ob_get_contents();
					ob_end_clean();						
				}
			}
		}
		
		$output .= dynamicpackages_Public::booking_sidebar();	
		echo $output;	
	}
}

?>