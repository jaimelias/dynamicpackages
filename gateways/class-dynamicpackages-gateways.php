<?php

class Dynamicpackages_Gateways
{
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
		add_filter('list_gateways', array(&$this, 'coupon'), 9);
		add_action('dy_checkout_area', array(&$this, 'checkout_area'), 1);
		add_filter('the_content', array(&$this, 'the_content'), 102);
		
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'), 100);
		add_action('init', array(&$this, 'set_post_on_checkout_page'));
		add_action('dy_terms_conditions', array(&$this, 'terms_conditions'));
		add_filter('dy_has_gateway', array(&$this, 'has_gateway'));
		add_filter('dy_join_gateways', array(&$this, 'join_gateways'));
		add_action('dy_invalid_min_duration', array(&$this, 'invalid_min_duration'));
		add_action('dy_coupon_confirmation', array(&$this, 'coupon_confirmation'));
		add_action('dy_cc_warning', array(&$this, 'cc_warning'));
		add_action('dy_crypto_form', array(&$this, 'crypto_form'));
		
	}
	
	public function set_post_on_checkout_page()
	{
		global $post;
		
		if(is_checkout_page() && !isset($post))
		{
			$this_id = intval(sanitize_text_field($_POST['post_id']));
			$GLOBALS['post'] = get_post($this_id);
		}
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
		return apply_filters('list_gateways', array());
	}
	
	public function join_gateways()
	{
		$array = $this->list_gateways_cb();
		return join(' '.__('or', 'dynamicpackages').' ', array_filter(array_merge(array(join(', ', array_slice($array, 0, -1))), array_slice($array, -1)), 'strlen'));
	}
	
	public function has_gateway()
	{
		$output = false;
		$gateways = $this->list_gateways_cb();

		if(is_array($gateways) && dy_utilities::pax_num() <= package_field('package_max_persons'))
		{
			if(count($gateways) > 1)
			{
				$GLOBALS['has_gateway'] = true;
				$output = true;
			}

		}
		
		return $output;
	}
	public function choose_gateway()
	{
		$output = null;
		
		if(count($this->list_gateways_cb()) > 0)
		{
			$output = __('Pay', 'dynamicpackages');
			
			if(dy_validators::has_deposit())
			{
				$output .= ' '.__('the deposit', 'dynamicpackages');
			}
			
			$output .= ' ('.dy_utilities::currency_symbol().'<span class="dy_calc dy_calc_total">'.number_format(dy_utilities::payment_amount(), 2, '.', ',').'</span>';
			
			$output .= ') '.__('with', 'dynamicpackages');
			
			if(count($this->list_gateways_cb()) == 1)
			{
				$this_gateway = $this->list_gateways_cb();
				$output .= ' '.$this_gateway[0];
			}
			else
			{
				$output .= ' '.$this->join_gateways();
			}
		}
		return $output;		
	}
	public function coupon($array)
	{
		if(is_singular('packages') && package_field('package_auto_booking') > 0)
		{	
			if(is_booking_page() && dy_validators::validate_coupon())
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
	
	public function checkout_area()
	{
		$output = null;
		
		if($this->has_gateway())
		{
			$output .= '<p class="text-center bottom-20 large">'.$this->choose_gateway().'.</p><div id="dy_payment_buttons" class="text-center bottom-20">'.$this->gateway_buttons().'</div>';
		}
		
		$output .= apply_filters('dy_booking_sidebar', null);	
		echo $output;	
	}
	
	public function terms_conditions()
	{
		$terms_conditions = dy_utilities::get_taxonomies('package_terms_conditions');
		$output = null;
		
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
	
	public function enqueue_scripts()
	{
		global $post;
		$show_checkout_vars = false;
		
		
		if(isset($post))
		{
			if(is_singular('packages'))
			{
				if(is_booking_page() )
				{
					$show_checkout_vars = true;	
				}
			}
			else if(is_page())
			{
				if(has_shortcode( $post->post_content, 'package_contact'))
				{
					$show_checkout_vars = true;	
				}
			}
		}
		
		if($show_checkout_vars)
		{
			wp_add_inline_script('dynamicpackages', $this->checkout_vars(), 'before');	
		}
		
	}


	public function checkout_vars()
	{
		global $post;

		$description = $this->get_description();
		$booking_coupon = null;
		$coupon_discount = null;
		
		if(dy_validators::validate_coupon())
		{
			$booking_coupon = dy_utilities::get_coupon('code');
			$coupon_discount = dy_utilities::get_coupon('discount');
			$description = $description.'. '.__('Coupon', 'dynamicpackages').' '.$booking_coupon.' '.'. '.$coupon_discount.'% '.__('off', 'dynamicpackages');
		}
		
		$add_ons = apply_filters('dy_get_add_ons', null);
		
		$checkout_vars = array(
			'post_id' => intval($post->ID),
			'description' => esc_html($description),
			'booking_coupon' => esc_html($booking_coupon),
			'coupon_discount' => esc_html($coupon_discount),
			'total' => floatval(dy_utilities::currency_format(dy_utilities::payment_amount())),
			'booking_date' => (isset($_GET['booking_date'])) ? sanitize_text_field($_GET['booking_date']) : null,
			'booking_extra' => (isset($_GET['booking_extra'])) ? sanitize_text_field($_GET['booking_extra']) : null,
			'booking_hour' => esc_html(dy_utilities::hour()),
			'end_date' => (isset($_GET['end_date'])) ? $_GET['end_date'] : null,
			'return_hour' => esc_html(dy_utilities::return_hour()),
			'duration' => esc_html(dy_utilities::show_duration()),
			'pax_num' => intval(dy_utilities::pax_num()),
			'pax_regular' => (isset($_GET['pax_regular']) ? intval($_GET['pax_regular']) : 0),
			'pax_discount' => (isset($_GET['pax_discount']) ? intval($_GET['pax_discount']) : 0),
			'pax_free' => (isset($_GET['pax_free']) ? intval($_GET['pax_free']) : 0),
			'package_code' => esc_html(package_field('package_trip_code')),
			'title' => esc_html($post->post_title),
			'package_type' => esc_html($this->get_type()),
			'package_categories' => esc_html(dy_utilities::implode_taxo_names('package_category')),
			'package_locations' => esc_html(dy_utilities::implode_taxo_names('package_location')),
			'package_not_included' => esc_html(dy_utilities::implode_taxo_names('package_not_included')),
			'package_included' => esc_html(dy_utilities::implode_taxo_names('package_included')),
			'TRANSLATIONS' => array('submit_error' => __('Error: please correct the invalid fields in color red.', 'dynamicpackages')),
			'TERMS_CONDITIONS' => $this->accept(),
			'package_url' => esc_url(get_permalink()),
			'hash' => (isset($_GET['hash'])) ? sanitize_text_field($_GET['hash']) : null,
			'currency_name' => dy_utilities::currency_name(),
			'currency_symbol' => dy_utilities::currency_symbol(),
			'outstanding' => floatval(dy_utilities::currency_format($this->outstanding())),
			'amount' => floatval(dy_utilities::currency_format(dy_utilities::total())),
			'regular_amount' => floatval(dy_utilities::currency_format(dy_utilities::subtotal(null, $post->ID))),
			'payment_type' => esc_html($this->payment_type()),
			'deposit' => floatval(dy_utilities::get_deposit()),
			'add_ons' => $add_ons
		);
		
		$checkout_vars = json_encode($checkout_vars);
		$script = 'function checkout_vars(){return ';
		$script .= $checkout_vars;
		$script .= '}';
		return $script;			
	}
	
	public function get_description()
	{
		$output = apply_filters('dy_description', null);
		
		if(dy_validators::has_deposit())
		{
			$deposit = dy_utilities::payment_amount();
			$total = dy_utilities::total();
			$outstanding = $total-$deposit;
			$output .= ' - '.__('deposit', 'dynamicpackages').' '.dy_utilities::currency_symbol().dy_utilities::currency_format($deposit).' - '.__('outstanding balance', 'dynamicpackages').' '.dy_utilities::currency_symbol().dy_utilities::currency_format($outstanding);					
		}
		return $output;
	}
	
	
	public function get_type()
	{
		$output = 'one day';
		
		if(package_field( 'package_package_type' ) == 1)
		{
			$output = 'multi-day';
		}
		else if(package_field( 'package_package_type' ) == 2)
		{
			$output = 'per day';
		}
		else if(package_field( 'package_package_type' ) == 3)
		{
			$output = 'per hour';
		}
		return $output;
	}
	
	public function accept()
	{
		$output = array();
		$terms = dy_utilities::get_taxonomies('package_terms_conditions');
		
		if(is_array($terms))
		{
			if(count($terms) > 0)
			{
				$terms_conditions = $terms;
				$terms_conditions_clean = array();

				for($x = 0; $x < count($terms_conditions); $x++ )
				{
					$terms_conditions_item = array();
					$terms_conditions_item['term_taxonomy_id'] = $terms_conditions[$x]->term_taxonomy_id;
					$terms_conditions_item['name'] = $terms_conditions[$x]->name;
					$terms_conditions_item['url'] = get_term_link($terms_conditions[$x]->term_taxonomy_id);
					array_push($terms_conditions_clean, $terms_conditions_item);
				}
				$output = $terms_conditions_clean;
			}			
		}

		return $output;
	}
	
	public function outstanding()
	{
		global $dy_outstanding;
		$output = 0;
		
		if(isset($dy_outstanding))
		{
			$output = $dy_outstanding;
		}
		else
		{
			//outstanding balance
			$total = dy_utilities::total();
			$amount = $total;
			
			
			if(package_field('package_payment' ) == 1)
			{
				$deposit = floatval(dy_utilities::get_deposit());
				
				if($deposit > 0)
				{
					$amount = floatval(dy_utilities::total())*(floatval($deposit)*0.01);
					$output = floatval($total)-$amount;					
				}					
			}

			$GLOBALS['dy_outstanding'] = $output;
		}
		return $output;
	}
	
	public function payment_type()
	{
		global $dy_payment_type;
		$output = 'full';
		
		if(isset($dy_payment_type))
		{
			$output = $dy_payment_type;
		}
		else
		{
			if(package_field('package_payment' ) == 1 && intval(package_field('package_auto_booking')) == 1)
			{
				$output = 'deposit';
			}
			
			$GLOBALS['dy_payment_type'] = $dy_payment_type;
		}
		return $output;
	}
	
	public function coupon_confirmation()
	{
		if(isset($_GET['booking_coupon']) && is_booking_page())
		{
			if(!empty($_GET['booking_coupon']))
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
					<p class="minimal_warning"><i class="fas fa-exclamation-triangle"></i> <?php esc_html_e('It is not allowed to book for third parties.', 'dynamicpackages'); ?></p>
					<p class="minimal_warning"><i class="fas fa-exclamation-triangle"></i> <?php esc_html_e('To complete this reservation we require images of the passports (foreigners) or valid Identity Documents (nationals) of each participant. The documents you send will be compared against the originals at the meeting point.', 'dynamicpackages'); ?></p>
					<p class="minimal_warning"><i class="fas fa-exclamation-triangle"></i> <?php esc_html_e('All card payments may be subject to a verification process where we charge a random amount less than $5. To complete the reservation you must indicate the exact amount of this charge. You can call your card support line or online banking for this.', 'dynamicpackages'); ?></p>	
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
		}
	}
	
}

?>