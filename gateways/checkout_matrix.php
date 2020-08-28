<?php

class dynamicpackages_Checkout
{
	function __construct()
	{
		if(is_admin())
		{
			if(dynamicpackages_Validators::is_gateway_active())
			{
				add_action('admin_init', array('dynamicpackages_Checkout', 'settings_init'));
				add_action('admin_menu', array('dynamicpackages_Checkout', 'add_primary_gateway_page'), 100);				
			}
		}
		else
		{
			add_action('wp_enqueue_scripts', array('dynamicpackages_Checkout', 'enqueue_scripts'));
			add_filter('gateway_buttons', array('dynamicpackages_Checkout', 'button'), 0);
			add_filter('list_gateways', array('dynamicpackages_Checkout', 'add_gateway'), 0);
		}
	}

	public static function enqueue_scripts()
	{
		if(is_singular('packages'))
		{
			if(is_booking_page())
			{
				wp_enqueue_script('checkout_script', plugin_dir_url( __FILE__ ) . 'checkout_script.js', array( 'jquery', 'dynamicpackages'), time(), true);
				wp_add_inline_script('checkout_script', self::checkout_vars(), 'before');		
			}
		}
	}
	
	public static function add_primary_gateway_page()
	{
		$gateway = dy_utilities::get_this_gateway();	
		add_submenu_page('edit.php?post_type=packages', $gateway['name'], $gateway['name'], 'manage_options', 'gateway', array('dynamicpackages_Checkout', 'primary_gateway_page'));
	}
	public static function primary_gateway_page()
	{ 
		$gateway = dy_utilities::get_this_gateway();
	
		?><div class="wrap">
		<form action='options.php' method='post'>
			
			<h1><?php echo esc_html($gateway['name']); ?></h1>	
			<?php
			settings_fields( 'gateway_settings' );
			do_settings_sections( 'gateway_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	
	public static function settings_init(  ) { 

		add_settings_section(
			'gateway_settings-section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'gateway_settings'
		);
				
		$gateway = dy_utilities::get_this_gateway();
		$name = $gateway['name'];
		$fields = $gateway['custom_fields'];
				
		for($c = 0; $c < count($fields); $c++)
		{
			$key_id = sanitize_title($name.'-'.$fields[$c]);
			register_setting( 'gateway_settings', esc_html($key_id), 'esc_html');

			add_settings_field( 
				esc_html($key_id), 
				esc_html($fields[$c]), 
				array('dynamicpackages_Checkout', 'field_render_text'), 
				'gateway_settings', 
				'gateway_settings-section',
				$key_id
			);
		}			
	}
	
	public static function field_render_text($key_id) { 
		$options = get_option($key_id);
		?>
		<input type="text" name="<?php echo esc_html($key_id); ?>" value='<?php echo esc_html($options); ?>'>
		<?php
	}
	
	public static function field_render_number($key_id) { 
		$options = get_option($key_id);
		?>
		<input type="number" min="0" name="<?php echo esc_html($key_id); ?>" value='<?php echo intval($options); ?>'>
		<?php
	}	
	
	public static function field_render_select($key_id) { 
		$options = get_option($key_id[0]);
		?>
		<select name="<?php echo esc_html($key_id[0]); ?>">
			<?php for($x = 0; $x  < count($key_id[1]); $x++)
			{
				$selected = '';
				
				if($options == $key_id[1][$x][0])
				{
					$selected = 'selected';
				}
				
				echo '<option '.esc_html($selected).' value="'.esc_html($key_id[1][$x][0]).'">'.esc_html($key_id[1][$x][1]).'</option>';
			}
			?>
		</select>
		<?php
	}	
	
	
	public static function checkout()
	{
		if(dynamicpackages_Validators::validate_checkout())
		{
			if(dynamicpackages_Validators::validate_recaptcha())
			{	
				$data = array();
				$gateway = dy_utilities::get_this_gateway();
				$custom = $gateway['custom_fields'];
				$post_fields = $gateway['post_fields'];
				$sanitized_fields = array();
				$webhook_fields = $gateway['webhook'];
				$webhook_data = array();
				
				
				for($c = 0; $c < count($custom); $c++)
				{
					$data[$custom[$c]] = get_option(sanitize_title($gateway['name'].'-'.$custom[$c]));
				}				
				
				for($p = 0; $p < count($post_fields); $p++)
				{
					if(isset($_POST[$post_fields[$p]]))
					{
						$sanitized_fields[$post_fields[$p]] = sanitize_text_field($_POST[$post_fields[$p]]);
					}
				}
				for($w = 0; $w < count($webhook_fields); $w++)
				{
					foreach($webhook_fields[$w] as $k => $v)
					{
						$eval = base64_encode($v);
						$webhook_data[$k] = eval(base64_decode($eval));						
					}
				}	

				$webhook_data['agreements'] = self::agreements();
					
				if(array_key_exists('hidden_fields', $gateway))
				{
					$hidden = $gateway['hidden_fields'];
					
					for($h = 0; $h < count($hidden); $h++)
					{
						foreach($hidden[$h] as $k => $v)
						{
							$data[$k] = $v;
						}
					}					
				}

				if(array_key_exists('checkout', $gateway))
				{
					$checkout = $gateway['checkout'];
					
					for($e = 0; $e < count($checkout); $e++)
					{
						foreach($checkout[$e] as $k => $v)
						{
							$eval = base64_encode($v);
							$data[$k] = eval(base64_decode($eval));
						}
					}					
				}			
				
				if(array_key_exists('content_type', $gateway))
				{
					if($gateway['content_type'] == 'x-www-form-urlencoded')
					{
						$data = http_build_query($data);
					}
					else
					{
						$data = json_encode($data);
					}
				}
				else
				{
					$data = json_encode($data);
				}
				
				//write_log($data);
				
				
				$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL, $gateway['url']);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $gateway['headers']);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				$result = curl_exec($ch);				
				//write_log($result);
				$result = json_decode($result, true);		
		
				return self::confirmation_page($result, $webhook_data);	
			}
			else
			{
				return __('Invalid Recaptcha', 'dynamicpackages');
			}
		}
		else
		{
			return __('Missing Fields', 'dynamicpackages');
		}
	}
	
	public static function confirmation_page($result, $data)
	{
		//0 = error, 1 = declined, 2 = approved
		$status = 0;
		$gateway = dy_utilities::get_this_gateway();
		$admin_email = get_option( 'admin_email' );
		$time = current_time('timestamp', $gmt = 0 );
		$time = date_i18n(get_option('date_format'), $time);
		$headers = array('Content-type: text/html');

		if(is_array($result) && is_array($data))
		{
			$checkout = array_merge($result, $data);
			
			for($s = 0; $s < count($gateway['declined']); $s++)
			{
				foreach($gateway['declined'][$s] as $k => $v)
				{
					if(array_key_exists($k, $checkout))
					{
						if($checkout['Status'] == $v)
						{
							$status = 1;
						}
					}
				}
			}
			
			for($s = 0; $s < count($gateway['approved']); $s++)
			{
				foreach($gateway['approved'][$s] as $k => $v)
				{
					if(array_key_exists($k, $checkout))
					{
						if($checkout['Status'] == $v)
						{
							$status = 2;
						}
					}
				}
			}			
		}
				
		if($status == 0)
		{
			wp_mail($admin_email, __('Undefined Checkout Error', 'dynamicpackages').' - '.$time, self::implode_result($result), $headers);	
			return '<div class="minimal_alert padding-10"><h2><span class="large"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span> '.esc_html(__('Undefined Checkout Error', 'dynamicpackages')).'</h2></div>';
		}
		else if($status == 1)
		{
			self::declined_mail($checkout);			
			self::webhook('dy_webhook', json_encode($checkout));
			$output = '<div class="minimal_alert padding-10"><h2><span class="large"><i class="fa fa-phone" aria-hidden="true"></i></span> '.esc_html(__('Payment Declined. Please contact your bank to authorize the transaction.', 'dynamicpackages')).'</h2></div>';
			return $output;
		}
		else
		{
			self::webhook('dy_webhook', json_encode($checkout));
			
			if(dynamicpackages_Affiliates::valid_affiliate_hash($checkout))
			{
				dynamicpackages_Affiliates::affiliate_mail($checkout);
			}			
			
			$output = '<div class="minimal_success padding-10 bottom-20"><h2><span class="large"><i class="fas fa-thumbs-up"></i></span> '.esc_html(__('Hello', 'dynamicpackages').' '.$checkout['Name'].' '.__('Payment approved. Thank you for order! You will receive and email confirmation shortly at', 'dynamicpackages').' '.$checkout['Email']).'</h2></div>';
			$output .= '<div class="text-center">'.add_to_calendar::show().'</div>';
			$output .= self::google_ads_tracker();
			$output .= self::facebook_pixel();
			return $output;				
		}

	}
	
	
	
	public static function declined_mail($vars)
	{
		$headers = array('Content-type: text/html');
		$admin_email = get_option( 'admin_email' );
		$time = current_time('timestamp', $gmt = 0 );
		$time = date_i18n(get_option('date_format'), $time);
		wp_mail($admin_email, __('Payment Declined', 'dynamicpackages').' - '.$time, self::implode_result($vars), $headers);		
	}
	
	public static function implode_result($result)
	{
		$output = '';
		
		foreach($result as $k => $v)
		{
			if($k == 'agreements')
			{
				$v = json_decode($v, true);
			}
			$output .= '<br/>';
			$output .= '<strong>'.$k.':</strong> '.$v;
		}
		return $output;
	}
	public static function outstanding()
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
				if(isset($_GET['quote']))
				{
					$output = $total;
				}					
			}

			$GLOBALS['dy_outstanding'] = $output;
		}
		return $output;
	}
	
	public static function payment_type()
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
				
				if(isset($_GET['quote']))
				{
					$output = 'full';
				}
			}
			
			$GLOBALS['dy_payment_type'] = $dy_payment_type;
		}
		return $output;
	}
	
	public static function checkout_vars()
	{
		global $post;
		
		$tax = floatval(dy_utilities::tax());
		$description = self::get_description();
		$coupon_code = null;
		$coupon_discount = null;
		$affiliate = null;
		$affiliate_hash = null;
		$affiliate_total = null;
		
		if(dynamicpackages_Validators::valid_coupon())
		{
			$coupon_code = dy_utilities::get_coupon('code');
			$coupon_discount = dy_utilities::get_coupon('discount');
			$description = $description.'. '.__('Coupon', 'dynamicpackages').' '.$coupon_code.' '.'. '.$coupon_discount.'% '.__('off', 'dynamicpackages');
		}
		
		if(dynamicpackages_Affiliates::valid_affiliate())
		{
			$affiliate = dynamicpackages_Affiliates::get_affiliate('id');
			$affiliate_hash = dynamicpackages_Affiliates::affiliate_hash();
			$affiliate_total = dy_utilities::currency_format(dy_utilities::subtotal());
		}
		
		$checkout_vars = array(
			'description' => esc_html($description),
			'coupon_code' => esc_html($coupon_code),
			'coupon_discount' => esc_html($coupon_discount),
			'total' =>dy_utilities::currency_format(dy_sum_tax(self::amount())),
			'departure_date' => sanitize_text_field($_GET['booking_date']),
			'departure_address' => esc_html(package_field('package_departure_address')),
			'check_in_hour' => esc_html(package_field('package_check_in_hour')),
			'booking_hour' => esc_html(dy_utilities::hour()),
			'duration' => esc_html(dynamicpackages_Public::show_duration()),
			'pax_num' => intval(dy_utilities::pax_num()),
			'pax_regular' => (isset($_GET['pax_regular']) ? intval($_GET['pax_regular']) : 0),
			'pax_discount' => (isset($_GET['pax_discount']) ? intval($_GET['pax_discount']) : 0),
			'pax_free' => (isset($_GET['pax_free']) ? intval($_GET['pax_free']) : 0),
			'package_code' => esc_html(package_field('package_trip_code')),
			'title' => esc_html($post->post_title),
			'package_type' => esc_html(self::get_type()),
			'package_categories' => esc_html(self::imp_taxo('package_category')),
			'package_locations' => esc_html(self::imp_taxo('package_location')),
			'package_not_included' => esc_html(self::imp_taxo('package_not_included')),
			'package_included' => esc_html(self::imp_taxo('package_included')),
			'message' => esc_html(self::get_notes()),
			'TRANSLATIONS' => array('i_accept' => __('I accept', 'dynamicpackages')),
			'TERMS_CONDITIONS' => self::accept(),
			'package_url' => get_permalink(),
			'hash' => sanitize_text_field($_GET['hash']),
			'currency' => 'USD',
			'affiliate' => esc_html($affiliate),
			'affiliate_hash' => esc_html($affiliate_hash),
			'affiliate_total' => esc_html($affiliate_total),
			'outstanding' =>dy_utilities::currency_format(dy_sum_tax(self::outstanding())),
			'amount' =>dy_utilities::currency_format(dy_sum_tax(dy_utilities::total())),
			'regular_amount' =>dy_utilities::currency_format(dy_sum_tax(dy_utilities::subtotal_regular())),
			'payment_type' => self::payment_type(),
			'deposit' => floatval(dy_utilities::get_deposit())
		);
		
		if($tax > 0)
		{
			$checkout_vars['tax'] = $tax;
			$checkout_vars['tax_amount'] = self::tax_amount();
		}		
		
		$add_ons = dynamicpackages_Taxmodifier::get_add_ons();
		
		if(is_array($add_ons))
		{
			if(count($add_ons) > 0)
			{
				$checkout_vars['add_ons'] = $add_ons;
			}
		}
		
		$checkout_vars = json_encode($checkout_vars);
		$script = 'function checkout_vars(){return ';
		$script .= $checkout_vars;
		$script .= '}';
		return $script;			
	}
	
	public static function webhook($option, $data)
	{
		$webhook = get_option($option);
		
		if(!filter_var($webhook, FILTER_VALIDATE_URL) === false)
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $webhook);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch,CURLOPT_TIMEOUT, 20);
			$result = curl_exec($ch);
			curl_close($ch);
		}
	}
	public static function tax_amount()
	{
		$output = 0;
		$tax = floatval(dy_utilities::tax());
		$total = floatval(dy_utilities::total());
		
		if($tax > 0 && $total > 0)
		{
			$output =dy_utilities::currency_format($total * ($tax / 100));
		}
		
		return $output;
	}
	public static function amount()
	{
		$total = floatval(dy_utilities::total());
		
		if(dynamicpackages_Validators::has_deposit())
		{
			$deposit = floatval(dy_utilities::get_deposit());
			$total = $total*($deposit*0.01);			
		}
		
		return $total;
	}
	public static function get_description()
	{
		$output = dynamicpackages_Public::description();
		
		if(dynamicpackages_Validators::has_deposit())
		{
			$deposit = dy_sum_tax(self::amount());
			$total = dy_sum_tax(dy_utilities::total());
			$outstanding = $total-$deposit;
			$output .= '- '.__('Paid', 'dynamicpackages').' $'.dy_utilities::currency_format($deposit).' - '.__('Outstanding Balance', 'dynamicpackages').' $'.dy_utilities::currency_format($outstanding);					
		}
		return $output;
	}
	
	public static function imp_taxo($tax)
	{
		global $post;
		$termid = $post->ID;
		
		if(property_exists($post, 'post_parent') && !has_term('', $tax, $termid))
		{
			$termid = $post->post_parent;
		}
		
		$terms = get_the_terms($termid, $tax);		
		
		if($terms)
		{

			$terms_array = array();
					
			for($x = 0; $x < count($terms); $x++)
			{
				array_push($terms_array, $terms[$x]->name);
			}
			
			return implode(', ', $terms_array);
		}
	}
	
	public static function get_type()
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
	public static function accept()
	{
		$output = array();
		$terms = dynamicpackages_Public::get_terms_conditions();
		
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
	public static function agreements()
	{
		$output = null;
		
		if(is_array(dynamicpackages_Public::get_terms_conditions()))
		{
			if(count(dynamicpackages_Public::get_terms_conditions()) > 0)
			{
				$terms_conditions = dynamicpackages_Public::get_terms_conditions();
				for($x = 0; $x < count($terms_conditions); $x++ )
				{
					$terms_conditions_item = '<h2>'.esc_html($terms_conditions[$x]->name).'</h2>';
					$terms_conditions_item .= wpautop($terms_conditions[$x]->description);
					$terms_conditions_item .= '<p><a href="'.esc_url(get_term_link($terms_conditions[$x]->term_taxonomy_id)).'">'.esc_url(get_term_link($terms_conditions[$x]->term_taxonomy_id)).'</a></p>';
					$output .= $terms_conditions_item;
				}
			}			
		}

		return json_encode($output);
	}	
	public static function get_notes()
	{
		global $polylang;
		global $post;
		
		$the_id = $post->ID;
		
		if(property_exists($post, 'post_parent'))
		{
			$the_id = $post->post_parent;
		}
		
		$language_list = array();
		$output = '';
		if(isset($polylang))
		{
			$languages = PLL()->model->get_languages_list();
			
			for($x = 0; $x < count($languages); $x++)
			{
				foreach($languages[$x] as $key => $value)
				{
					if($key == 'slug' && $value == substr(get_locale(), 0, -3))
					{
						$output = package_field( 'package_provider_message_'.$value, $the_id);
					}
				}	
			}
		}
		else
		{
			$output = package_field( 'package_provider_message', $the_id);
		}
		return $output;
	}
	
	public static function facebook_pixel()
	{
		if(get_theme_mod('facebook_pixel_id') != null)
		{
			ob_start();
			?>
			
			<script>
			//facebook pixel
			if(typeof fbq !== typeof undefined)
			{
				console.log('Purchase');
				fbq('track', 'Purchase', {value: <?php echo esc_html(sanitize_text_field($_POST['total']));?>, currency: 'USD'});
			}			
			</script>
			
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			return $output;	
		}
	}
	
	public static function google_ads_tracker()
	{
		if(get_theme_mod('analytics_tracking_id') != null)
		{
			$this_time = time();
			$product_category = 'one day';	
			if(package_field( 'package_package_type' ) == 1)
			{
				$product_category = 'multi-day';
			}
			ob_start();
		?>
			<script>
				ga('create', '<?php echo esc_html(get_theme_mod('analytics_tracking_id'));?>', 'auto', {'name': 'ecommerce_tracker'});
				ga('ecommerce_tracker.require', 'ecommerce');
				ga('ecommerce_tracker.ecommerce:addTransaction', {
				  'id': '<?php echo esc_html(sanitize_text_field($_POST['package_code']).'_'.$this_time);?>',
				  'affiliation': '<?php echo esc_html(get_bloginfo('name'));?>',
				  'revenue': '<?php echo esc_html(sanitize_text_field($_POST['total']));?>'
				});
				ga('ecommerce_tracker.ecommerce:addItem', {
				  'id': '<?php echo esc_html(sanitize_text_field($_POST['package_code']).'_'.$this_time); ?>',
				  'name': '<?php echo esc_html(sanitize_text_field($_POST['title']).' - '.sanitize_text_field($_POST['title'])); ?>',
				  'category': '<?php echo esc_html($product_category); ?>',
				  'price': '<?php echo esc_html(sanitize_text_field($_POST['total'])); ?>',
				  'quantity': '1'
				});	
				ga('ecommerce_tracker.ecommerce:send');
				console.log('ecommerce');
			</script>
		<?php
			$output = ob_get_contents();
			ob_end_clean();
			return $output;			
		}
	}
	public static function button($output)
	{
		
		if(dynamicpackages_Validators::is_gateway_active() && (in_array('Visa', self::list_gateways_cb()) || in_array('Mastercard', self::list_gateways_cb())))
		{
			$output .= '<button class="pure-button pure-button-primary bottom-20 bycard rounded" type="button"><i class="fas fa-credit-card"></i> '.esc_html(__('Pay by card', 'dynamicpackages')).'</button>';
		}
		return $output;
	}
	public static function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}	
	public static function add_gateway($array)
	{
		if(is_singular('packages') && dynamicpackages_Validators::is_gateway_active() && package_field('package_auto_booking') > 0)
		{
			$array[] = 'Visa';
			$array[] = 'Mastercard';		
		}
		return $array;	
	}	
}

?>