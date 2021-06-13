<?php

class dy_validators
{
	public static function validate_booking_date()
	{
		$output = false;
		
		if(isset($_GET['booking_date']))
		{
			$booking_date = dy_utilities::booking_date();
			$min_range = dy_utilities::min_range();
			$max_range = dy_utilities::max_range();
			$event_date = strtotime(package_field('package_event_date'));
			
			if($booking_date)
			{
				if($event_date == '')
				{
					if($booking_date >= $min_range && $booking_date <= $max_range)
					{
						$output = true;
					}				
				}
				else
				{
					if($booking_date == $event_date)
					{
						$output = true;
					}				
				}				
			}
		}
		return $output;
	}
	public static function is_has_package()
	{
		$output = false;
		global $is_has_package;
		
		if(isset($is_has_package))
		{
			$output = true;
		}
		else
		{
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

			if($output === true)
			{
				$GLOBALS['is_has_package'] = $output;
			}	
		}
		
		return $output;
	}	
	public static function is_booking_page()
	{
		$output = false;
		global $is_booking_page;
		
		if(isset($is_booking_page))
		{
			$output = true;
		}
		else
		{
			if(self::validate_booking_date() && isset($_GET['pax_regular']) && self::validate_hash())
			{
				$pax_regular = intval(sanitize_text_field($_GET['pax_regular']));			
				
				if($pax_regular >= package_field('package_min_persons'))
				{
					$output = true;
					$GLOBALS['is_booking_page'] = $output;
				}
				else
				{
					$output = false;
				}
			}
			else
			{
				$output = false;
			}			
		}
		return $output;
	}	
	
	public static function is_request_valid()
	{
		$output = false;
		global $dy_is_request_valid;
		
		if(isset($dy_is_request_valid))
		{
			$output = true;
		}
		else
		{
			if(self::contact_details())
			{
				if(isset($_POST['booking_date']))
				{
					if(self::is_date($_POST['booking_date']))
					{
						if(self::booking_details())
						{
							$output = true;
						}
					}
					else
					{
						$output = true;
					}
				}
				
				if($output)
				{
					$GLOBALS['dy_is_request_valid'] = $output;
				}
			}		
		}
		return $output;
	}
	
	public static function contact_details()
	{
		$output = false;
		$invalids = array();
		global $dy_contact_details;
		
		if(isset($dy_contact_details))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_request']))
			{
				if(isset($_POST['first_name']) && isset($_POST['lastname']) && isset($_POST['phone']) && isset($_POST['email']) && isset($_POST['repeat_email']))
				{
					if(!is_email($_POST['email']))
					{
						$invalids[] = __('Invalid email.', 'dynamicpackages');
					}
					if(!is_email($_POST['repeat_email']))
					{
						$invalids[] = __('Invalid repeated email.', 'dynamicpackages');
					}
					if($_POST['email'] != $_POST['repeat_email'])
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
				}
				else
				{
					$invalids[] = __('Invalid Request.', 'dynamicpackages');
				}				
			}
		}
		
		if(is_array($invalids))
		{
			if(count($invalids) === 0)
			{
				$output = true;
				$GLOBALS['dy_contact_details'] = $output;						
			}
			else
			{
				$GLOBALS['dy_request_invalids'] = $invalids;
			}
		}
		
		return $output;
	}
	
	public static function validate_recaptcha()
	{
		global $dy_valid_recaptcha;
		$invalids = array();
		
		if(!isset($dy_valid_recaptcha))
		{
			if(isset($_POST['dy_recaptcha']) && get_option('captcha_secret_key'))
			{
				$data = array();
				$data['secret'] = get_option('captcha_secret_key');
				$data['remoteip'] = $_SERVER['REMOTE_ADDR'];
				$data['response'] = sanitize_text_field($_POST['dy_recaptcha']);
				$url = 'https://www.google.com/recaptcha/api/siteverify';			
				$verify = curl_init();
				curl_setopt($verify, CURLOPT_URL, $url);
				curl_setopt($verify, CURLOPT_POST, true);
				curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
				$verify_response = json_decode(curl_exec($verify), true);

				if($verify_response['success'] === true)
				{
					$GLOBALS['dy_valid_recaptcha'] = true;
				}
				if(array_key_exists('error-codes', $verify_response))
				{
					$GLOBALS['dy_request_invalids'] = array(__('Invalid Recaptcha', 'dynamicpackages'));
					$debug_output = array(
						'error' => $verify_response['error-codes']
					);
					$post_debug = array_map('sanitize_text_field', $_POST);
					
					if(array_key_exists('first_name', $post_debug)){
						$debug_output['name'] = $post_debug['first_name'];
					}
					if(array_key_exists('email', $post_debug)){
						$debug_output['email'] = $post_debug['email'];
					}
					if(array_key_exists('phone', $post_debug)){
						$debug_output['phone'] = $post_debug['phone'];
					}
					if(array_key_exists('description', $post_debug)){
						$debug_output['description'] = $post_debug['description'];
					}
					if(array_key_exists('add_ons', $post_debug)){
						$debug_output['add_ons'] = $post_debug['add_ons'];
					}
					if(array_key_exists('total', $post_debug)){
						$debug_output['total'] = $post_debug['total'];
					}					
					
					write_log(json_encode($debug_output));
				}
			}
		}
	}
	
	public static function validate_checkout($gateway_name)
	{
		$output = false;

		if(isset($_POST['dy_request']) && self::contact_details() && self::booking_details())
		{
			if($gateway_name == $_POST['dy_request'] && self::credit_card() && self::validate_terms_conditions($_POST))
			{
				$output = true;
			}
		}	
		
		return $output;
	}
	
	public static function validate_terms_conditions($fields)
	{
		$output = true;
				
		if(is_array($fields))
		{
			if(count($fields) > 0)
			{
				foreach($fields as $k => $v)
				{
					$string = 'terms_conditions_';
					$string_length = strlen($string);
					
					if(substr($k, 0, $string_length) === $string)
					{
						if($v != 'true')
						{
							$output = false;
						}
					}
				}
			}
		}
		
		if($output === false)
		{
			$GLOBALS['dy_request_invalids'] = array(__('Please you must accept our Terms & Conditions before booking', 'dynamicpackages'));
		}
		
		return $output;
	}
	
	public static function booking_details()
	{
		$output = false;
		global $booking_details;
		
		if(isset($booking_details))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['booking_date']) && isset($_POST['booking_hour']) && isset($_POST['duration']) && isset($_POST['pax_num']))
			{	
				$output = true;
				$GLOBALS['booking_details'] = $output;
			}	
		}
		return $output;		
	}
	public static function credit_card()
	{
		$invalids = array();
		$output = false;
		global $credit_card;
		
		if(isset($credit_card))
		{
			return true;
		}
		else
		{
			if(isset($_POST['CCNum']) && isset($_POST['ExpMonth']) && isset($_POST['ExpYear']) && isset($_POST['CVV2']) && isset($_POST['country']) && isset($_POST['address']) && isset($_POST['city']))
			{
				if(!self::luhn_check($_POST['CCNum']))
				{
					$invalids[] = __('Invalid Credit Card. Please return to the previous page to correct the numbers.', 'dynamicpackages');
				}
				else
				{					
					if( self::american_express_check($_POST['CCNum']) )
					{
						$invalids[] = __('American Express is not accepted. At the moment we only accept Visa or Mastercard.', 'dynamicpackages');
					}
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
		}
		
		if(is_array($invalids))
		{
			if(count($invalids) === 0)
			{
				$output = true;
				$GLOBALS['credit_card'] = $output;				
			}
			else
			{
				$GLOBALS['dy_request_invalids'] = $invalids;
			}
		}
		
		return $output;
	}

	
	public static function validate_hash()
	{
		$output = false;
		global $validate_hash;
		
		if(isset($validate_hash))
		{
			return true;
		}
		else
		{
			$hash = hash('sha512', dy_utilities::pax_num().$_GET['booking_date']);
			
			if(isset($_GET['hash']))
			{
				if($hash == $_GET['hash'])
				{
					$output = true;
					$GLOBALS['validate_hash'] = $output;
				}				
			}
		}
		return $output;
	}
	
	public static function has_coupon()
	{
		$output = false;
		global $has_coupon;
		
		if(isset($has_coupon))
		{
			$output = true;
		}
		else
		{
			if(package_field( 'package_max_coupons' ) && package_field( 'package_coupons' ))
			{
				$coupons = json_decode(html_entity_decode(package_field( 'package_coupons' )), true);
				
				if(is_array($coupons))
				{
					if(array_key_exists('coupons', $coupons) && package_field( 'package_max_coupons' ) > 0)
					{
						if(isset($coupons['coupons'][0]))
						{
							$coupons = $coupons['coupons'][0];
							
							if($coupons[0] != '' && $coupons[1] != '')
							{
								$output = true;
								$GLOBALS['has_coupon'] = $output;
							}						
						}
					}					
				}
			}			
		}
		
		return $output;
	}
	public static function is_date($str)
	{
		$output = false;
		$regex = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
		
		if(preg_match($regex, $str))
		{
			$output = true;
		}
		
		return $output;
	}
	public static function valid_coupon()
	{
		$output = false;
		global $valid_coupon;
		
		if(isset($valid_coupon))
		{
			$output = true;
		}
		else
		{
			if(self::has_coupon() && isset($_REQUEST['booking_coupon']))
			{
				if($_REQUEST['booking_coupon'] != '')
				{
					$booking_coupon = strtolower(sanitize_text_field($_REQUEST['booking_coupon']));
					$booking_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $booking_coupon);
					$get_coupon = strtolower(dy_utilities::get_coupon('code'));
					$get_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $get_coupon);
					$duration = dy_utilities::get_min_nights();
					$booking_date = sanitize_text_field($_REQUEST['booking_date']);
					$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
					$booking_dates_range = dy_utilities::get_date_range($booking_date, $booking_date_to, false);
					
					if($get_coupon == $booking_coupon)
					{
						$expiration = dy_utilities::get_coupon('expiration');
						$min_duration = (is_numeric(dy_utilities::get_coupon('min_duration'))) ? dy_utilities::get_coupon('min_duration') : 0;
						$valid_expiration = false;
						$valid_duration = false;

						if($expiration == '')
						{
							$valid_expiration = true;
						}
						else
						{
							$expiration_stamp = new DateTime($expiration);
							$expiration_stamp->setTime(0,0,0);
							$expiration_stamp = $expiration_stamp->getTimestamp();							
							
							if($expiration_stamp >= dy_strtotime('today midnight'))
							{
								if(!self::is_package_transport() && !self::is_package_single_day())
								{
									for($x = 0; $x < count($booking_dates_range); $x++)
									{
										$range_date = new DateTime($booking_dates_range[$x]);
										$range_date->setTime(0,0,0);
										$range_date = $range_date->getTimestamp();
										
										if($expiration_stamp > $range_date)
										{
											$valid_expiration = true;
										}
										else
										{
											$valid_expiration = false;
										}
									}
								}
								else
								{
									$valid_expiration = true;
								}	
							}							
						}
						
						if($min_duration)
						{
							$valid_duration = true;
						}
						else
						{
							if($duration >= $min_duration)
							{
								$valid_duration = true;
							}
							else
							{
								$valid_duration = false;
							}
						}
						
						if($valid_expiration && $valid_duration)
						{
							$output = true;
						}
					}				
				}
				
				if($output)
				{
					$GLOBALS['valid_coupon'] = $output;
				}
			}
		}
		return $output;
	}
	
	
	public static function validate_category_location()
	{
		$package_location = '';
		$package_category = '';
		$location = '';
		$category = '';
		$sort_by = '';
		$search = '';
		

		if(isset($_GET['package_location']))
		{
			$package_location = sanitize_text_field($_GET['package_location']);
			
			if($package_location != '')
			{
				$location = get_term_by('slug', $package_location, 'package_location');
			}
		}
		
		if(isset($_GET['package_category']))
		{
			$package_category = sanitize_text_field($_GET['package_category']);
			
			if($package_category != '')
			{
				$category = get_term_by('slug', $package_category, 'package_category');
			}				
		}
		if(isset($_GET['package_sort']))
		{
			if($_GET['package_sort'] == 'new' || $_GET['package_sort'] == 'low' || $_GET['package_sort'] == 'high' || $_GET['package_sort'] == 'today' || $_GET['package_sort'] == 'tomorrow' || $_GET['package_sort'] == 'week' || $_GET['package_sort'] == 'month')
			{
				$sort_by = true;
			}
		}	

		if(isset($_GET['package_search']))
		{
			if($_GET['package_search'] != '')
			{
				$search = true;
			}
		}
				
		

		if($location != '' || $category != '' || $sort_by != '' || $search != '')
		{
			remove_action('wp_head', 'rel_canonical');
			return true;
		}
			
	}	
	
	public static function has_deposit()
	{
		$output = false;
		global $dy_has_deposit;
		
		if($dy_has_deposit)
		{
			$output = true;
		}
		else
		{
			if(package_field('package_auto_booking'))
			{
				if(package_field( 'package_payment' ) == 1 && package_field('package_deposit') > 0 && dy_utilities::total() > 0)
				{
					$output = true;
					$GLOBALS['dy_has_deposit'] = $output;
				}			
			}
		}
		
		return $output;
	}
	
	public static function is_child()
	{
		$output = false;
		global $post;
		global $dy_is_child;
				
		if(isset($dy_is_child))
		{
			$output = true;
		}
		else
		{
			if(isset($post))
			{
				if(property_exists($post, 'post_parent'))
				{
					if($post->post_parent > 0)
					{
						$output = true;
						$GLOBALS['dy_is_child'] = $output;
					}					
				}
			}
		}
		
		return $output;
	}
	public static function has_children() {
		
		
		$output = false;
		$name = 'dy_has_children';
		$the_id = get_the_ID();
		$which_var = $name.'_'.$the_id;
		global $$which_var;	
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$args = array(
				'post_type'      => 'packages',
				'post_parent'    => $the_id
			 );
			 
			 $children = get_posts($args);
			 
			 if(is_array($children))
			 {
				 if(count($children) > 0)
				 {
					 $output = $children;
					 $GLOBALS[$which_var] = $output;
				 }
			 }		
		}
		
		return $output;
	}

	public static function is_parent_with_no_child()
	{
		$output = false;
		global $dy_is_parent_with_no_child;
		
		if(isset($dy_is_parent_with_no_child))
		{
			$output = $dy_is_parent_with_no_child;
		}
		else
		{
			if(!self::has_children() && !self::is_child())
			{
				$output = true;
				$GLOBALS['dy_is_parent_with_no_child'] = $output;
			}			
		}
		
		return $output;
	}
	
	public static function event()
	{
		$output = array();
		global $dy_event;
		
		if(isset($dy_event))
		{
			$output = $dy_event;
		}
		else
		{		
			$package_start_address = package_field('package_start_address');
			$package_start_hour = package_field('package_start_hour');
			
			if($package_start_address != '' && $package_start_hour != '')
			{
				$package_event_date = package_field('package_event_date');
				
				if($package_event_date != '')
				{
					$today = strtotime(dy_date('Y-m-d'));
					$event_date = strtotime(dy_date($package_event_date));
					
					if($event_date > $today)
					{
						array_push($output, $event_date);
					}
				}
				else
				{
					$from = package_field('package_booking_from');
					$to = package_field('package_booking_to');
					
					if(intval($from) > 0 && intval($to) > 0)
					{
						$new_range = array();
						$today = date('Y-m-d', strtotime("+ {$from} days", dy_strtotime('now')));
						$last_day = date('Y-m-d', strtotime("+ {$to} days", dy_strtotime('now')));
						$range = dy_utilities::get_date_range($today, $last_day);
						$disabled_range = dy_utilities::get_disabled_range();
						$week_days = dy_utilities::get_week_days_list();				
						
						for($x = 0; $x < count($range); $x++)
						{
							if(!in_array($range[$x], $disabled_range))
							{
								$day = dy_date('N', dy_strtotime($range[$x]));
								
								if(!in_array($day, $week_days))
								{
									array_push($new_range, $range[$x]);
								}
							}
						}
						
						if(is_array($new_range))
						{
							if(count($new_range) > 0)
							{
								$output = $new_range;
								$GLOBALS['dy_event'] = $output;
							}
						}
					}
				}
			}
		}
		
		return $output;
	}
	
	public static function is_valid_schema($id = '')
	{
		$output = false;
		$the_id = $id;
		
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}

		$name = 'dy_is_valid_schema';
		$which_var = $name.'_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(get_comments_number() > 0)
			{
				if(is_singular('package'))
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
			
			$GLOBALS[$which_var] = $output;
		}
		
		return $output;
	}

	
	public static function american_express_check($number)
	{
		if(substr($number, 0, 2 ) === '34' || substr($number, 0, 2 ) === '37' && strlen($number) === 15)
		{
			return true;
		}
		else
		{
			return false;
		}
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
	
	public static function is_package_single_day()
	{
		$output = false;
		
		if(package_field( 'package_package_type' ) == 0)
		{
			$output = true;
		}
		
		return $output;		
	}
	
	public static function is_package_transport()
	{
		$output = false;
		
		if(package_field( 'package_package_type' ) == 4)
		{
			$output = true;
		}
		
		return $output;
	}

	
}


?>