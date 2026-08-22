<?php

if ( !defined( 'WPINC' ) ) exit;

function is_booking_page()
{
	return dy_validators::is_booking_page();
}

function is_confirmation_page()
{
	return dy_validators::is_confirmation_page();
}

function has_package()
{
	return dy_validators::has_package();
}

#[AllowDynamicProperties]
class dy_validators
{
	private static $cache = [];

public static function validate_quote()
	{

		$output = false;
		$the_id = get_dy_id();
		$cache_key = 'dy_validate_quote_' . $the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(!self::validate_the_id($the_id)) {
			write_log('invalid the_id');
			return self::$cache[$cache_key] = false;
		}

		$total        = (float) dy_utilities::total();
		$min_persons  = (int) package_field('package_min_persons', $the_id);
		$max_persons  = (int) package_field('package_max_persons', $the_id);
		$pax_regular  = (int) secure_request('pax_regular', 0, 'absint');
		$pax_discount = (int) secure_request('pax_discount', 0, 'absint');
		$pax_free     = (int) secure_request('pax_free', 0, 'absint');

		// Enforce documented constraints on package config
		if ($min_persons <= 0 || $max_persons <= 0 || $max_persons <= $min_persons) {
			return self::$cache[$cache_key] = false;
		}

		// Enforce documented constraint: pax_regular must be > 0
		if ($pax_regular <= 0) {
			return self::$cache[$cache_key] = false;
		}

		$sum_people = $pax_regular;

		if ($pax_discount > 0) {
			$sum_people += $pax_discount;
		}
		if ($pax_free > 0) {
			$sum_people += $pax_free;
		}

		if (
			$total > 0
			&& $pax_regular >= $min_persons
			&& $sum_people <= $max_persons
		) {
			$output = true;
		}

		self::$cache[$cache_key] = $output;

		return $output;
	}
	
	public static function validate_booking_date($the_id = null)
	{
		$output = false;
		$cache_key = 'dy_validate_booking_date_' . $the_id;
		

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}

		if(isset($_GET['booking_date']))
		{
			$booking_date = dy_utilities::booking_date();
			$min_range = dy_utilities::min_range($the_id);
			$max_range = dy_utilities::max_range($the_id);
						
			if($booking_date)
			{
				if($booking_date >= $min_range && $booking_date <= $max_range)
				{
					$output = true;
				}
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	public static function has_package()
	{
		$output = false;
		$cache_key = 'dy_has_package';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(is_singular('packages'))
		{
			$output = true;
		}
		else if(is_tax('package_category') ||is_tax('package_location') || is_post_type_archive('packages'))
		{
			$output = true;
		}
		else if(is_page())
		{
			global $post;
			
			if(is_object($post))
			{
				if(has_shortcode($post->post_content, 'packages'))
				{
					$output = true;
				}					
			}
		}	

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function validate_the_id($the_id = 0) {

		$cache_key = 'validate_the_id_' . $the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if (!is_numeric($the_id) || (int) $the_id <= 0 || (int) $the_id != $the_id) {
			return self::$cache[$cache_key] = false;
		}

		return self::$cache[$cache_key] = true;
	}

	public static function is_booking_page()
	{
		$output = false;
		$cache_key = 'dy_is_booking_page';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if (
			is_admin()
			|| wp_doing_ajax()
			|| wp_doing_cron()
			|| (defined('REST_REQUEST') && REST_REQUEST)
		) {
			return self::$cache[$cache_key] = false;
		}

		$the_id = get_dy_id();

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}

		$post   = $the_id ? get_post($the_id) : null;

		if (!($post instanceof WP_Post) || $post->post_type !== 'packages') {
			return self::$cache[$cache_key] = false;
		}

		if(self::validate_booking_date($the_id) && self::validate_pax_regular($the_id) && self::validate_hash())
		{
			$output = true;
		}
		else
		{
			$output = false;
		}

        //store output in $cache
        

		return self::$cache[$cache_key] = $output;
	}	
	
	public static function validate_pax_regular($the_id = 0) {


		$cache_key = 'dy_validate_pax_regular_' . $the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}

		$pax_regular_request = secure_request('pax_regular', 0, 'absint');

		if (!is_int($pax_regular_request) || $pax_regular_request <= 0) {
			return self::$cache[$cache_key] = false;
		}

		$min_persons = (int) package_field('package_min_persons', $the_id);

		if (!is_int($min_persons) || $min_persons <= 0) {
			// No valid minimum configured — treat as invalid rather than auto-passing
			return self::$cache[$cache_key] = false;
		}

		$output = $pax_regular_request >= $min_persons;

		return self::$cache[$cache_key] = $output;
	}

	public static function is_confirmation_page()
	{
		$output = false;
		$cache_key = 'dy_is_checkout_page';
	
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if (
			is_admin()
			|| wp_doing_ajax()
			|| wp_doing_cron()
			|| (defined('REST_REQUEST') && REST_REQUEST)
		) {
			return self::$cache[$cache_key] = false;
		}

		$post_id = secure_post('post_id', 0);

		if(!empty(secure_post('dy_request')) && !empty($post_id))
		{
			$post = get_post($post_id);

			if(($post instanceof WP_Post) && ($post->post_type === 'packages' || has_shortcode( $post->post_content, 'package_contact'))) {
				$output = true;
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}

	public static function validate_request()
	{
		$output = false;
		$cache_key = 'dy_validate_request';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(self::is_confirmation_page())
		{
			if(self::validate_contact_details() && self::validate_booking_details())
			{
				$output = true;
			}
			else
			{
				$GLOBALS['dy_request_invalids'] = array('invalid_request');
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	
	public static function validate_unique_tx_id($txt = '') {
		if (!is_string($txt) || $txt === '') return false;

		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
			$txt
		);
	}

	public static function validate_contact_details()
	{
		$output = false;
		$invalids = [];
		$cache_key = 'dy_validate_contact_details';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(self::is_confirmation_page())
		{
			if(
				isset($_POST['unique_tx_id'])
				&& isset($_POST['first_name'])
				&& isset($_POST['lastname'])
				&& isset($_POST['phone'])
				&& isset($_POST['country_calling_code'])
				&& isset($_POST['email'])
				&& isset($_POST['repeat_email'])
				
			)
			{
				$unique_tx_id = secure_post('unique_tx_id');

				if(!self::validate_unique_tx_id($unique_tx_id))
				{
					$invalid_transaction_id_message = __('Invalid unique transaction ID.', 'dynamicpackages');
					$invalids[] = $invalid_transaction_id_message;
				}
				if(!is_email($_POST['email']))
				{
					$invalids[] = __('Invalid email.', 'dynamicpackages');
				}
				if(!is_email($_POST['repeat_email']))
				{
					$invalids[] = __('Invalid repeated email.', 'dynamicpackages');
				}
				if($_POST['email'] !== $_POST['repeat_email'])
				{
					$invalids[] = __('Email and repeated email are not equal.', 'dynamicpackages');
				}
				if(empty($_POST['first_name']))
				{
					$invalids[] = __('First name is empty.', 'dynamicpackages');
				}
				if(empty($_POST['lastname']))
				{
					$invalids[] = __('Lastname is empty.', 'dynamicpackages');
				}
				if(empty($_POST['phone']))
				{
					$invalids[] = __('Phone is empty.', 'dynamicpackages');
				}
				if(empty($_POST['country_calling_code']))
				{
					$invalids[] = __('Country Calling Code is empty.', 'dynamicpackages');
				}
				if(isset($_POST['inquiry']))
				{
					if(empty($_POST['inquiry']))
					{
						$invalids[] = __('Inquiry is empty.', 'dynamicpackages');
					}
					else
					{
						if(self::is_spam($_POST['inquiry']))
						{
							$invalids[] = __('Inquiry is empty.', 'dynamicpackages');
						}
					}
				}
			}
			else
			{
				$invalids[] = __('Invalid Request.', 'dynamicpackages');
			}				
		}

		if(count($invalids) === 0)
		{
			$output = true;
		}
		else
		{
			$GLOBALS['dy_request_invalids'] = $invalids;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}
	
	public static function validate_checkout($gateway_name)
	{
		$output = false;
		$cache_key = 'dy_validate_checkout_' . sanitize_key($gateway_name);


        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(self::is_confirmation_page() && self::validate_contact_details() && self::validate_booking_details())
		{
			if($gateway_name === secure_post('dy_request') && self::validate_card())
			{
				$output = true;
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	
public static function validate_terms_conditions()
	{
		$output = true;
		$cache_key = 'dy_validate_terms_conditions';

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$auto_booking = intval(package_field('package_auto_booking'));

		if ($auto_booking === 1) {
			$terms = dy_utilities::get_taxonomies('package_terms_conditions');

			if (is_array($terms) && count($terms) > 0) {
				foreach ($terms as $term) {
					$term_name = 'terms_conditions_' . $term->term_id;
					$accepted = secure_post($term_name, false);

					if (filter_var($accepted, FILTER_VALIDATE_BOOLEAN) === false) {
						$output = false;
						break;
					}
				}
			}
		}

		if ($output === false) {
			$GLOBALS['dy_request_invalids'] = array(__('Please you must accept our Terms & Conditions before booking', 'dynamicpackages'));
		}

		return self::$cache[$cache_key] = $output;
	}
	
	public static function validate_booking_details()
	{
		$output = false;
		$cache_key = 'dy_validate_booking_details';		
		

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(isset($_POST['booking_date']) && isset($_POST['booking_hour']) && isset($_POST['duration']) && isset($_POST['pax_num']) && self::validate_terms_conditions())
		{	
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
        
		return $output;		
	}
	public static function validate_card()
	{
		$invalids = [];
		$output = false;
		$cache_key = 'dy_validate_card';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$required_params = ['CCNum', 'ExpMonth', 'ExpYear', 'CVV2', 'country', 'address', 'city'];

		for($x = 0; $x < count($required_params); $x++)
		{
			if(!array_key_exists($required_params[$x], $_POST))
			{
				$invalids[] = sprintf(__('Required param %s not found.', 'dynamicpackages'), $required_params[$x]);
			}
		}

		if(count($invalids) === 0) {
			
			if(!self::luhn_check($_POST['CCNum']))
			{
				$invalids[] = __('Invalid Credit Card. Please return to the previous page to correct the numbers.', 'dynamicpackages');
			}
			if(empty($_POST['ExpMonth']))
			{
				$invalids[] = __('Invalid expiration month.', 'dynamicpackages');
			}
			if(empty($_POST['ExpYear']))
			{
				$invalids[] = __('Invalid expiration year.', 'dynamicpackages');
			}
			if(empty($_POST['CVV2']))
			{
				$invalids[] = __('Invalid CVV (security code on the back of the card).', 'dynamicpackages');
			}
			if(empty($_POST['country']))
			{
				$invalids[] = __('Invalid country.', 'dynamicpackages');
			}
			if(empty($_POST['city']))
			{
				$invalids[] = __('Invalid city.', 'dynamicpackages');
			}
			if(empty($_POST['address']))
			{
				$invalids[] = __('Invalid address.', 'dynamicpackages');
			}
		}
		
		if(is_array($invalids))
		{
			if(count($invalids) === 0)
			{
				$output = true;			
			}
			else
			{
				$GLOBALS['dy_request_invalids'] = $invalids;
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	
	public static function validate_hash()
	{
		$output = false;
		$cache_key = 'dy_validate_hash';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$hash = hash('sha512', dy_utilities::pax_num().$_GET['booking_date']);
		
		if(isset($_GET['hash']))
		{
			if($hash == $_GET['hash'])
			{
				$output = true;
			}				
		}
		
        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	
	public static function has_coupon()
	{
		$output = false;
		$cache_key = 'dy_has_coupon';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$max_coupons = (int) package_field( 'package_max_coupons');

		if($max_coupons > 0)
		{
			$coupons = dy_utilities::get_package_hot_chart('package_coupons');
			
			if(is_array($coupons))
			{
				if(array_key_exists('coupons', $coupons))
				{
					if(isset($coupons['coupons'][0]))
					{
						$coupons = $coupons['coupons'][0];
						
						if(!empty($coupons[0]) && !empty($coupons[1]))
						{
							$output = true;
						}						
					}
				}					
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}

	public static function validate_coupon()
	{
		$output = false;
		$cache_key = 'dy_validate_coupon';

		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$coupon_code = secure_request('coupon_code');
		
		if(!self::has_coupon() || empty($coupon_code)) {
			return self::$cache[$cache_key] = false;
		}

		$coupon_code = (string) strtolower(sanitize_text_field($_REQUEST['coupon_code']));
		$coupon_code = (string) preg_replace("/[^A-Za-z0-9 ]/", '', $coupon_code);

		$coupon_params = (object) dy_utilities::get_active_coupon_params();
		$stored_coupon_code = (string) $coupon_params->code;
		$package_type = (string) dy_utilities::get_package_type();

		$duration = (int) dy_utilities::get_min_nights();
		$booking_date = secure_request('booking_date');
		$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
		$booking_dates_range = (array) dy_utilities::get_date_range($booking_date, $booking_date_to, false);
		
		if($stored_coupon_code === $coupon_code)
		{
			$coupon_expiration = (string) $coupon_params->expiration;
			$coupon_min_duration = (int) $coupon_params->min_duration;
			$coupon_max_duration = (int) $coupon_params->max_duration;
			$coupon_bookings_after_expires = (bool) $coupon_params->bookings_after_expires;
			$valid_expiration = false;

			if(($duration < $coupon_min_duration && $coupon_min_duration > 0) || ($duration > $coupon_max_duration && $coupon_max_duration > 0) )
			{
				self::$cache[$cache_key] = false;
				return false;
			}
			if(empty($coupon_expiration))
			{
				self::$cache[$cache_key] = true;
				return true;
			}

			//expiration
			$expiration_stamp = new DateTime($coupon_expiration);
			$expiration_stamp->setTime(0,0,0);
			$expiration_stamp = $expiration_stamp->getTimestamp();

			//booking
			$booking_date_stamp = new DateTime($booking_date);
			$booking_date_stamp->setTime(0,0,0);
			$booking_date_stamp = $booking_date_stamp->getTimestamp();

			if($expiration_stamp > dy_strtotime('today midnight'))
			{
				if($package_type !== 'transport' && $package_type !== 'one-day')
				{
					$arr_valid_expiration = [];

					for ($x = 0; $x < count($booking_dates_range); $x++) {
						$range_date = new DateTime($booking_dates_range[$x]);
						$range_date->setTime(0, 0, 0);
						$range_date = $range_date->getTimestamp();
						
						if ($range_date > $expiration_stamp && $coupon_bookings_after_expires === false) 
						{
							$arr_valid_expiration[] = false;
						} 
						else
						{
							$arr_valid_expiration[] = true;
						}
					}

					if (!in_array(false, $arr_valid_expiration)) {
						$valid_expiration = true;
					}
				}
				else
				{
					if($booking_date_stamp >= $expiration_stamp)
					{
						if($coupon_bookings_after_expires === true)
						{
							$valid_expiration = true;
						}
					}
					else
					{
						$valid_expiration = true;
					}									
				}	
			}	
									
			$output = $valid_expiration;
		}

		return self::$cache[$cache_key] = $output;
	}
	
	
	public static function validate_category_location()
	{
		
		$cache_key = 'dy_validate_category_location';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$output = false;
		$package_location = '';
		$package_category = '';
		$location = '';
		$category = '';
		$sort_by = '';
		$search = '';
		
		if(isset($_GET['location']))
		{
			$package_location = sanitize_text_field($_GET['location']);
			
			if(!empty($package_location))
			{
				$location = get_term_by('slug', $package_location, 'package_location');
			}
		}
		
		if(isset($_GET['category']))
		{
			$package_category = sanitize_text_field($_GET['category']);
			
			if(!empty($package_category))
			{
				$category = get_term_by('slug', $package_category, 'package_category');
			}				
		}
		if(isset($_GET['sort']))
		{
			$sort_by_arr = dy_utilities::sort_by_arr();
			$sort_by_value = sanitize_text_field($_GET['sort']);

			if(!empty($sort_by_value) || $sort_by_value !== 'any')
			{
				if(in_array($sort_by_value, $sort_by_arr))
				{
					$sort_by = true;
				}
			}
		}	

		if(isset($_GET['keywords']))
		{
			if(!empty($_GET['keywords']))
			{
				$search = true;
			}
		}
				

		if(!empty($location) || !empty($category) || !empty($sort_by) || !empty($search))
		{
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}	
	
	public static function has_deposit()
	{
		$output = false;
		$cache_key = 'dy_has_deposit';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(package_field('package_auto_booking'))
		{
			if(package_field('package_payment') == 1 && package_field('package_deposit') > 0 && dy_utilities::total() > 0)
			{
				$output = true;
			}			
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}
	
	public static function is_child($the_id = null)
	{
		$output = false;

		if($the_id)
		{
			$post = get_post($the_id);
		}
		else
		{
			global $post;
		}

		if($post instanceof WP_Post)
		{
			if(property_exists($post, 'ID') && self::validate_the_id($post->ID))
			{
				$cache_key = $post->ID.'_is_child';

				if (array_key_exists($cache_key, self::$cache)) {
					return self::$cache[$cache_key];
				}

				if(property_exists($post, 'post_parent'))
				{
					if($post->post_parent > 0)
					{
						$output = true;
					}					
				}

				//store output in $cache
				self::$cache[$cache_key] = $output;
			}
		}
		
		return $output;
	}
	public static function has_children($the_id = 0) {
		
		$output = false;

		if($the_id)
		{
			$post = get_post($the_id);
		}
		else
		{
			global $post;
		}

		if($post instanceof WP_Post)
		{
			if(property_exists($post, 'ID') && self::validate_the_id($post->ID))
			{
				$cache_key = 'dy_has_children_' . $post->ID;
				
				if (array_key_exists($cache_key, self::$cache)) {
					return self::$cache[$cache_key];
				}

				$args = array(
					'post_type' => 'packages',
					'post_parent' => $post->ID,
					'posts_per_page' => -1
				);
				
				$children = get_posts($args);
				
				if(is_array($children))
				{
					if(count($children) > 0)
					{
						$output = $children;
					}
				}
				
				//store output in $cache
				self::$cache[$cache_key] = $output;
			}
		}

		
		return $output;
	}

	public static function is_parent_with_no_child($the_id = null)
	{
		$output = false;

		if(!$the_id) {
			$the_id = get_dy_id();
		}
		
		$cache_key = 'dy_is_parent_with_no_child_' . $the_id;
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}
		
		if(!self::has_children($the_id) && !self::is_child($the_id))
		{
			$output = true;
		}        
		
		return self::$cache[$cache_key] = $output;
	}
	
	public static function is_valid_schema($the_id = 0)
	{
		$output = false;
		$cache_key = 'dy_is_valid_schema_' . $the_id;
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}

		if(get_comments_number() > 0)
		{
			if(is_singular('packages'))
			{
				if(dy_utilities::starting_at() > 0)
				{
					$output = true;
				}
			}
			else
			{
				if(dy_utilities::starting_at_archive() > 0)
				{
					$output = true;
				}
			}	
		}
		
        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}

	
	public static function luhn_check($number) 
	{
	  $number = preg_replace('/\D/', '', $number);
	  $number_length = strlen($number);
	  $parity = $number_length % 2;
	  $total = 0;
	  
	  for ($i=0; $i < $number_length; $i++)
	  {
		$digit = $number[$i];
		
		if ($i % 2 == $parity)
		{
		  $digit*=2;
		  
		  if ($digit > 9) 
		  {
			$digit-=9;
		  }
		}
	 
		$total+=$digit;
	  }

	  return ($total % 10 == 0) ? TRUE : FALSE;

	}

	public static function is_spam($str) {

		$str = html_entity_decode(sanitize_text_field($str));
		$emailRegex = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/';
		$domainRegex = '/\b(?:https?:\/\/)?(?:www\.)?([A-Za-z0-9.-]+\.[A-Za-z]{2,})\b/';
		$urlRegex = '/\bhttps?:\/\/[^\s]+\b/';

		return (
			preg_match($emailRegex, $str) ||
			preg_match($domainRegex, $str) ||
			preg_match($urlRegex, $str)
		);
	}

}




?>