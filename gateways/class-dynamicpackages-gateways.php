<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Gateways
{

	private static $cache = [];

	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->load_gateways();
		$this->load_classes();
		$this->init();
	}
	
	public function load_gateways()
	{
		require_once plugin_dir_path(__FILE__).'matrix/cuanto/cuanto.php';		
		require_once plugin_dir_path(__FILE__).'matrix/paguelo_facil/paguelo_facil_on.php';		
		require_once plugin_dir_path(__FILE__).'matrix/paypal/paypal_me.php';		
		require_once plugin_dir_path(__FILE__).'matrix/nequi/nequi_direct.php';
		require_once plugin_dir_path(__FILE__).'matrix/yappy/yappy_direct.php';
		require_once plugin_dir_path(__FILE__).'matrix/bank/local.php';	
		require_once plugin_dir_path(__FILE__).'matrix/bank/international.php';	
		require_once plugin_dir_path(__FILE__).'matrix/estimate/estimate.php';
		require_once plugin_dir_path(__FILE__).'matrix/stablepay/usdt.php';
		require_once plugin_dir_path(__FILE__).'matrix/stablepay/usdc.php';
		require_once plugin_dir_path(__FILE__).'matrix/stablepay/busd.php';
		require_once plugin_dir_path(__FILE__).'matrix/pay-later/pay-later.php';
	}	
	
	public function load_classes()
	{
		$this->add_to_calendar = new dy_Add_To_Calendar();
		$this->estimate = new estimate_request($this->plugin_id);
		$this->paguelo_facil_on = new paguelo_facil_on($this->plugin_id);
		$this->cuanto = new cuanto($this->plugin_id);
		$this->paypal_me = new paypal_me($this->plugin_id);
		$this->nequi_direct = new nequi_direct($this->plugin_id);
		$this->yappy_direct = new yappy_direct($this->plugin_id);
		$this->pay_later = new pay_later($this->plugin_id);
		$this->bank_transfer = new bank_transfer($this->plugin_id);
		$this->wire_transfer = new wire_transfer($this->plugin_id);
		$this->usdt = new usdt($this->plugin_id);
		$this->usdc = new usdc($this->plugin_id);
		$this->busd = new busd($this->plugin_id);
	}
	public function init()
	{
		add_action('dy_cc_form', array(&$this, 'cc_form'));
		add_action('admin_init', array(&$this, 'load_gateways'));
		add_action('init', array(&$this, 'load_gateways'));
		add_filter('list_gateways', array(&$this, 'filter_coupon_gateway'), 9);
		add_action('dy_checkout_area', array(&$this, 'checkout_area'), 1);
		add_filter('the_content', array(&$this, 'the_content'), 102);			
		add_action('dy_terms_conditions', array(&$this, 'terms_conditions'));
		add_filter('dy_has_gateway', array(&$this, 'has_gateway'));
		add_filter('dy_join_gateways', array(&$this, 'join_gateways'));
		add_action('dy_invalid_min_duration', array(&$this, 'invalid_min_duration'));
		add_action('dy_coupon_confirmation', array(&$this, 'coupon_confirmation'));
		add_action('dy_cc_warning', array(&$this, 'cc_warning'));
		add_action('dy_crypto_form', array(&$this, 'crypto_form'));		
		add_action('dy_whatsapp_button', array(&$this, 'whatsapp_button'));
	}
	


	public function the_content($content)
	{
		if(is_singular('packages') && isset($_GET['booking_date']))
		{
			if(is_booking_page())
			{
				if(dy_validators::validate_hash())
				{
					$package_min_persons = package_field('package_min_persons');
					$package_max_persons = package_field('package_max_persons');
					$pax_regular = intval(sanitize_text_field($_GET['pax_regular']));			
					$sum_people = (isset($_GET['pax_discount'])) ? $pax_regular + intval(sanitize_text_field($_GET['pax_discount'])) : $pax_regular;
					$sum_people = (isset($_GET['pax_free'])) ? $sum_people + intval(sanitize_text_field($_GET['pax_free'])) : $sum_people;

					if($pax_regular <  $package_min_persons || $sum_people > $package_max_persons)
					{
						$content = '<p class="minimal_success strong">'.esc_html(__('Send us your request and we will send you the quote shortly.', 'dynamicpackages')).'</p>';
						$content .= '<h2>'.__('Contact The Experts', 'dynamicpackages').' - '.__('Request Quote', 'dynamicpackages').'</h2>';
						$content .= apply_filters('dy_booking_sidebar', null);							
					}
					else
					{
						ob_start();
						require_once(plugin_dir_path( __DIR__  ) . 'gateways/partials/checkout-page.php');
						$content = ob_get_contents();
						ob_end_clean();									
					}					
				}
				else
				{
					$content = '<p class="minimal_alert strong">'.esc_html( __('Invalid Request', 'dynamicpackages')).'</p>';
				}
			}
			else
			{				
				$content = '<p class="minimal_alert strong">'.esc_html( __('Invalid Request', 'dynamicpackages')).'</p>';
			}		
		}

		return $content;
	}

	public function gateway_buttons()
	{
		return $this->gateway_buttons_cb();
	}	
	public function gateway_buttons_cb()
	{
		return apply_filters('gateway_buttons', '');
	}
		
	public function list_gateways_cb()
	{
		$cache_key = 'dy_list_gateways_cb';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$gateways = apply_filters('list_gateways', array());
		
		if(dy_validators::validate_coupon())
		{
			$get_coupon = strtolower(dy_utilities::get_coupon('code'));
			$get_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $get_coupon);

			if(!empty($get_coupon))
			{
				$coupon_gateways = array_filter($gateways, function ($gateway) use ($get_coupon) {
					
					if(!empty($gateway))
					{
						return strcasecmp($gateway, $get_coupon) === 0;
					}
				});

				if(is_array($coupon_gateways))
				{
					if(count($coupon_gateways) === 1)
					{
						$gateway = $coupon_gateways;
					}
				}
			}
		}

		self::$cache[$cache_key] = $gateways;

		return $gateways;
	}
	
	public function join_gateways()
	{
		$array = array_unique($this->list_gateways_cb());		
		return join(' '.__('or', 'dynamicpackages').' ', array_filter(array_merge(array(join(', ', array_slice($array, 0, -1))), array_slice($array, -1)), 'strlen'));
	}
	
	public function has_gateway()
	{
		$output = false;
		$gateways = $this->list_gateways_cb();
		$pax_num = intval(dy_utilities::pax_num());
		$max_persons = intval(package_field('package_max_persons'));
		$auto_booking = intval(package_field('package_auto_booking'));

		if(is_array($gateways) && $pax_num <= $max_persons && $auto_booking > 0)
		{
			if(count($gateways) > 0)
			{
				$GLOBALS['has_gateway'] = true;
				$output = true;
			}
		}
		
		return $output;
	}

	public function choose_gateway()
	{
		$output = '';
		$gateways = $this->list_gateways_cb();
		$payment_gateways = array_diff($gateways, array($this->estimate->name));


		if(count($payment_gateways) > 0)
		{
			$output = __('Pay', 'dynamicpackages');
			
			if(dy_validators::has_deposit())
			{
				$output .= ' '.__('the deposit', 'dynamicpackages');
			}
			
			$output .= ' ('.currency_symbol().'<span class="dy_calc dy_calc_total">'.money(dy_utilities::payment_amount()).'</span>';
			
			$output .= ') '.__('with', 'dynamicpackages');
			
			$output .= ' ' . $this->join_gateways() . '.';
		}
		else{

			$output = '🤖 ' . $this->estimate->only_estimate . ' ⬇️';
		}

		return $output;		
	}
	public function filter_coupon_gateway($gateways)
	{
		if(is_singular('packages') && is_array($gateways))
		{	
			if(is_booking_page() && dy_validators::validate_coupon() && count($gateways) > 0)
			{
				$get_coupon = strtolower(dy_utilities::get_coupon('code'));
				$get_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $get_coupon);
				
				if(!empty($get_coupon))
				{
					$coupon_gateways = array_filter($gateways, function ($gateway) use ($get_coupon) {
						if(!empty($gateway))
						{
							return strcasecmp($gateway, $get_coupon) === 0;
						}
					});

					if(is_array($coupon_gateways))
					{
						if(count($coupon_gateways) === 1)
						{
							return $coupon_gateways;
						}
					}
				}
			}
		}
		return $gateways;
	}
	
	public function checkout_area()
	{
		$output = '';
		
		if($this->has_gateway())
		{
			$output = '<p class="text-center bottom-20 large">'.$this->choose_gateway().'</p><div id="dy_payment_buttons" class="text-center bottom-20">'.$this->gateway_buttons().'</div>';
		}
		else
		{
			$output = '<p class="text-center bottom-20 large">🤖 ' . $this->estimate->only_estimate . ' ⬇️</p>';
		}
		
		$output .= apply_filters('dy_booking_sidebar', null);	
		echo $output;	
	}
	
	public function terms_conditions()
	{
		$terms_conditions = dy_utilities::get_taxonomies('package_terms_conditions');
		$output = '';
		
		if(is_array($terms_conditions))
		{
			if(count($terms_conditions) > 0)
			{
				$output = '<h3>'.esc_html(__('Terms & Conditions', 'dynamicpackages')).'</h3><p>';
				
				for($x = 0; $x < count($terms_conditions); $x++ )
				{
					$term = $terms_conditions[$x];
					$id = $term->term_taxonomy_id;
					$url = get_term_link($id);
					$name = $term->name;
					
					$output .= '<label for="terms_conditions_'.esc_html($id).'" class="checkmark-container"><input type="checkbox" name="terms_conditions_'.esc_html($id).'" id="terms_conditions_'.esc_html($id).'" class="required" /><span class="checkmark"></span> <a href="'.esc_url($url).'" target="_blank">'.esc_html($name).'</a></label>';
				}

				$output .= '</p><hr/>';
			}
		}

		echo $output;
		
	}
	
	public function cc_form($output)
	{
		ob_start();
		require_once(plugin_dir_path( __DIR__  ) . 'gateways/partials/cc-form.php');
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	
	public function coupon_confirmation()
	{
		if(isset($_GET['coupon_code']) && is_booking_page())
		{
			if(!empty($_GET['coupon_code']))
			{
				if(dy_validators::validate_coupon())
				{
					$expiration = dy_utilities::get_coupon('expiration');
					
					echo '<p class="minimal_success strong">'.esc_html(sprintf(__('Coupon %s activated. %s off applied on the rate.', 'dynamicpackages'), dy_utilities::get_coupon('code'), dy_utilities::get_coupon('discount').'%')).'</p>';
					
					if($expiration)
					{
						$expiration = date_i18n(get_option('date_format' ), strtotime($expiration));
						echo '<p class="minimal_alert strong">'.esc_html(sprintf(__('This coupon expires on %s.', 'dynamicpackages'), $expiration)).'</p>';
					}
					
				}
				else
				{
					echo '<p class="minimal_alert">'.esc_html(__('Invalid or expired coupon', 'dynamicpackages')).'</p>';
				}
			}
		}
	}
	
	public function invalid_min_duration()
	{
		if(isset($_GET['booking_extra']) && is_booking_page())
		{
			$booking_extra = intval(sanitize_text_field($_GET['booking_extra']));
			$min_duration = dy_utilities::get_min_nights();
			$duration_unit = package_field('package_length_unit');
			
			if($booking_extra < $min_duration)
			{
				echo '<p class="minimal_alert strong">'.esc_html(sprintf(__('You have chosen %s %s, but the minimum duration is %s %s.', 'dynamicpackages'), $booking_extra, dy_utilities::duration_label($duration_unit, $booking_extra), $min_duration, dy_utilities::duration_label($duration_unit, $min_duration))).'</p>';
			}
		}
	}

	public function crypto_form()
	{
		if(is_booking_page())
		{
			ob_start();
			?>
				<h3><?php echo (esc_html__('Choose Network', 'dynamicpackages')); ?></h3>
				<p>
					<select name="dy_network">
						<option value="">--</option>
					</select>
				</p>
				<p id="dy_crypto_alert" class="minimal_alert hidden">
					<?php echo (esc_html__('The network you selected is', 'dynamicpackages')); ?>&nbsp;
					<strong id="dy_crypto_network_code"></strong>.&nbsp;
					<?php echo (esc_html__('make sure you use', 'dynamicpackages')); ?>&nbsp;
					<strong id="dy_crypto_network_name"></strong>&nbsp;
					<?php esc_html_e( __('at the time of sending the funds. If the other network does not support it, your assets may be lost.', 'dynamicpackages')); ?>
				</p>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
		}
	}

	public function cc_warning()
	{
		if(is_booking_page())
		{
			ob_start();
			?>
					<hr/>
					
					<h3><?php echo (esc_html__('Before Booking', 'dynamicpackages')); ?></h3>
					<p class="minimal_warning"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('It is not allowed to book for third parties.', 'dynamicpackages'); ?></p>
					<p class="minimal_warning"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('To complete this reservation we require images of the passports (foreigners) or valid Identity Documents (nationals) of each participant. The documents you send will be compared against the originals at the meeting point.', 'dynamicpackages'); ?></p>
					<p class="minimal_warning"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('All card payments may be subject to a verification process where we charge a random amount less than $5. To complete the reservation you must indicate the exact amount of this charge. You can call your card support line or online banking for this.', 'dynamicpackages'); ?></p>	
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
		}
	}
	

	public function whatsapp_button()
	{
		$label = __('Support via Whatsapp', 'dynamicpackages');
		$total = dy_utilities::total();
		$text = apply_filters('dy_description', null);

		if($total > 0)
		{
			$text .= ' $'.money($total);
		}

		echo whatsapp_button($label, $text);
	}
}

?>