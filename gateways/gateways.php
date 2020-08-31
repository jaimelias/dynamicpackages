<?php

class dy_Gateways
{
	function __construct()
	{
		$this->load_gateways();
		$this->add_to_calendar = new dy_Add_To_Calendar();
		$this->load_classes();
		$this->init();
	}
	
	public function load_classes()
	{
		$this->plugin_checkout = new dy_CC_Checkout($this->add_to_calendar);
		$this->paypal_me = new paypal_me();
		$this->nequi_direct = new nequi_direct();
		$this->yappy_direct = new yappy_direct();	
		$this->bank_transfer = new bank_transfer();
		$this->wire_transfer = new wire_transfer();
	}
	public function init()
	{
		add_action('admin_init', array(&$this, 'load_gateways'));
		add_action('init', array(&$this, 'load_gateways'));
		add_filter('list_gateways', array(&$this, 'coupon'), 9);
		add_action('checkout_area', array(&$this, 'add_to_checkout_area'), 1);
		add_filter('the_content', array(&$this, 'the_content'), 102);		
	}

	public function the_content($content)
	{
		if(is_singular('packages') && isset($_GET['booking_date']))
		{
			if(is_booking_page())
			{
				if(dy_Validators::validate_hash())
				{
					$pax_regular = intval(sanitize_text_field($_GET['pax_regular']));			
					$sum_people = $pax_regular;	
					$GLOBALS['dy_add_to_calendar'] = $this->add_to_calendar;

					if(isset($_GET['pax_discount']))
					{
						$sum_people = $sum_people + intval(sanitize_text_field($_GET['pax_discount']));
					}
					if(isset($_GET['pax_free']))
					{
						$sum_people = $sum_people + intval(sanitize_text_field($_GET['pax_free']));
					}					
					
					if(isset($_GET['booking_date']))
					{
						if(sanitize_text_field($_GET['booking_date']) == '')
						{
							$content = '<p class="minimal_alert"><strong>'.esc_html(dy_Public::hour_restriction()).'</strong></p>';		
						}
						else
						{
							if($pax_regular < package_field('package_min_persons') || $sum_people > package_field('package_max_persons'))
							{
								$content = '<p class="minimal_success strong">'.esc_html(dy_Public::people_restriction()).'</p>';
								$content .= '<h2>'.__('Contact The Experts', 'dynamicpackages').' - '.__('Request Quote', 'dynamicpackages').'</h2>';
								$content .= dy_Public::booking_sidebar();							
							}
							else
							{
								ob_start();
								require_once(plugin_dir_path( __DIR__  ) . 'gateways/checkout-page.php');
								$content = ob_get_contents();
								ob_end_clean();									
							}	
						}
					}
					else
					{
						ob_start();
						require_once(plugin_dir_path( __DIR__ ) . 'gateways/checkout-page.php');
						$content = ob_get_contents();
						ob_end_clean();						
					}					
				}
				else
				{
					$content = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Request', 'dynamicpackages')).'</strong></p>';
				}
			}
			else
			{
				$content = null;
				
				$content .= '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Request', 'dynamicpackages')).'</strong></p>';
			}		
		}

		return $content;
	}
	public static function load_gateways()
	{
		require_once plugin_dir_path(__FILE__).'cc-gateways.php';		
		require_once plugin_dir_path(__FILE__).'matrix/paypal/paypal_me.php';		
		require_once plugin_dir_path(__FILE__).'matrix/nequi/nequi_direct.php';
		require_once plugin_dir_path(__FILE__).'matrix/yappy/yappy_direct.php';
		require_once plugin_dir_path(__FILE__).'matrix/bank/local.php';	
		require_once plugin_dir_path(__FILE__).'matrix/bank/international.php';	
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
		
		if(count(self::list_gateways_cb()) > 0 && dy_utilities::pax_num() <= package_field('package_max_persons'))
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
			
			if(dy_Validators::has_deposit())
			{
				$output .= ' '.__('the deposit', 'dynamicpackages');
			}
			
			$output .= ' ('.dy_utilities::currency_symbol().'<span class="dy_calc dy_calc_total">'.number_format(dy_utilities::amount(), 2, '.', ',').'</span>';
			
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
			if(is_booking_page() && dy_Validators::valid_coupon())
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
		if(dy_Gateways::has_any_gateway())
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
		
		$output .= dy_Public::booking_sidebar();	
		echo $output;	
	}
}

?>