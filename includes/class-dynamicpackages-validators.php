<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class dy_validators
{
	private static $cache = [];

	public static function validate_quote()
	{
		$output = false;
		$cache_key = 'dy_validate_quote';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$total = dy_utilities::total();
		$min_persons = intval(package_field('package_min_persons'));
		$max_persons = intval(package_field('package_max_persons'));
		$pax_regular = intval(sanitize_text_field($_REQUEST['pax_regular']));
		$sum_people = $pax_regular;	
		
		if(isset($_REQUEST['pax_discount']))
		{
			$sum_people = $sum_people + intval(sanitize_text_field($_REQUEST['pax_discount']));
		}
		if(isset($_REQUEST['pax_free']))
		{
			$sum_people = $sum_people + intval(sanitize_text_field($_REQUEST['pax_free']));
		}
		
		if($total > 0 && $pax_regular >= $min_persons && $sum_people <= $max_persons)
		{
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
		
	}
	
	public static function validate_booking_date($the_id = null)
	{
		$output = false;
		$cache_key = 'dy_validate_booking_date';
		

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(isset($_GET['booking_date']))
		{
			$booking_date = dy_utilities::booking_date();
			$min_range = dy_utilities::min_range($the_id);
			$max_range = dy_utilities::max_range($the_id);
			$event_date = strtotime(package_field('package_event_date', $the_id));
			
			if($booking_date)
			{
				if(empty($event_date))
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

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	public static function has_package()
	{
		$output = false;
		$cache_key = 'dy_has_package';
		
        if (isset(self::$cache[$cache_key])) {
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
	public static function is_booking_page()
	{
		$output = false;
		$cache_key = 'dy_is_booking_page';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$the_id = get_dy_id();

		if(self::validate_booking_date($the_id) && isset($_GET['pax_regular']) && self::validate_hash())
		{
			$pax_regular = intval(sanitize_text_field($_GET['pax_regular']));			
			
			if($pax_regular >= package_field('package_min_persons', $the_id))
			{
				$output = true;
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
		
        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}	
	

	public static function is_checkout_page()
	{
		$output = false;
		$cache_key = 'dy_is_checkout_page';
	
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(isset($_POST['dy_request']) && isset($_POST['post_id']))
		{
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}

	public static function validate_request()
	{
		$output = false;
		$cache_key = 'dy_validate_request';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(self::is_checkout_page())
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
	
	public static function validate_contact_details()
	{
		$output = false;
		$invalids = array();
		$cache_key = 'dy_validate_contact_details';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(self::is_checkout_page())
		{
			if(isset($_POST['first_name']) && isset($_POST['lastname']) && isset($_POST['phone']) && isset($_POST['country_calling_code']) && isset($_POST['email']) && isset($_POST['repeat_email']))
			{
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
							//cloudflare_ban_ip_address();
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
		$cache_key = 'dy_validate_checkout';


        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(self::is_checkout_page() && self::validate_contact_details() && self::validate_booking_details())
		{
			if($gateway_name == $_POST['dy_request'] && self::validate_card())
			{
				$output = true;
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	
	public static function validate_terms_conditions($fields)
	{
		$output = true;
		$cache_key = 'dy_validate_terms_conditions';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$auto_booking = intval(package_field('package_auto_booking'));

		if(is_array($fields) && $auto_booking === 1)
		{
			if(count($fields) > 0)
			{
				$terms = dy_utilities::get_taxonomies('package_terms_conditions');
				
				if(is_array($terms))
				{
					$count_terms = count($terms);

					for($x = 0; $x < $count_terms; $x++)
					{
						$term_id = $terms[$x]->term_id;
						$term_name = 'terms_conditions_'.$term_id;

						if(array_key_exists($term_name, $fields))
						{
							$value = $fields[$term_name];

							if(filter_var($value, FILTER_VALIDATE_BOOLEAN) === false)
							{
								$output = false;
							}
						}
						else
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

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}
	
	public static function validate_booking_details()
	{
		$output = false;
		$cache_key = 'dy_validate_booking_details';		
		

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(isset($_POST['booking_date']) && isset($_POST['booking_hour']) && isset($_POST['duration']) && isset($_POST['pax_num']) && self::validate_terms_conditions($_POST))
		{	
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
        
		return $output;		
	}
	public static function validate_card()
	{
		$invalids = array();
		$output = false;
		$cache_key = 'dy_validate_card';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(isset($_POST['CCNum']) && isset($_POST['ExpMonth']) && isset($_POST['ExpYear']) && isset($_POST['CVV2']) && isset($_POST['country']) && isset($_POST['address']) && isset($_POST['city']))
		{
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

        if (isset(self::$cache[$cache_key])) {
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
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(package_field( 'package_max_coupons' ) && package_field( 'package_coupons' ))
		{
			$coupons = dy_utilities::get_hot_chat('package_coupons');
			
			if(is_array($coupons))
			{
				if(array_key_exists('coupons', $coupons) && package_field( 'package_max_coupons' ) > 0)
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

		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if(self::has_coupon() && isset($_REQUEST['coupon_code']))
		{
			if(!empty($_REQUEST['coupon_code']))
			{
				$coupon_code = strtolower(sanitize_text_field($_REQUEST['coupon_code']));
				$coupon_code = preg_replace("/[^A-Za-z0-9 ]/", '', $coupon_code);
				$get_coupon = strtolower(dy_utilities::get_coupon('code'));
				$get_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $get_coupon);
				$duration = dy_utilities::get_min_nights();
				$booking_date = sanitize_text_field($_REQUEST['booking_date']);
				$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
				$booking_dates_range = dy_utilities::get_date_range($booking_date, $booking_date_to, false);
				
				if($get_coupon === $coupon_code)
				{
					$expiration = dy_utilities::get_coupon('expiration');
					$min_duration = dy_utilities::get_coupon('min_duration');
					$max_duration = dy_utilities::get_coupon('max_duration');
					$bookings_after_expires = dy_utilities::get_coupon('bookings_after_expires') || false;
					$valid_expiration = false;
					$valid_duration = false;

					if(empty($expiration))
					{
						$valid_expiration = true;
					}
					else
					{

						//expiration
						$expiration_stamp = new DateTime($expiration);
						$expiration_stamp->setTime(0,0,0);
						$expiration_stamp = $expiration_stamp->getTimestamp();

						//booking
						$booking_date_stamp = new DateTime($_REQUEST['booking_date']);
						$booking_date_stamp->setTime(0,0,0);
						$booking_date_stamp = $booking_date_stamp->getTimestamp();

						if($expiration_stamp > dy_strtotime('today midnight'))
						{
							if(!self::package_type_transport() && !self::is_package_single_day())
							{
								$arr_valid_expiration = array();

								for ($x = 0; $x < count($booking_dates_range); $x++) {
									$range_date = new DateTime($booking_dates_range[$x]);
									$range_date->setTime(0, 0, 0);
									$range_date = $range_date->getTimestamp();
									
									if ($range_date > $expiration_stamp && $bookings_after_expires === false) 
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
									if($bookings_after_expires === true)
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
					}
											
					if($min_duration > 0)
					{
						if($duration >= $min_duration)
						{
							$valid_duration = true;
						}							
					}
					else
					{
						$valid_duration = true;
					}
					
					if($valid_duration && $max_duration > 0)
					{
						if($duration > $max_duration)
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
			
			//store output in $cache
			self::$cache[$cache_key] = $output;
		}

		return $output;
	}
	
	
	public static function validate_category_location()
	{
		
		$cache_key = 'dy_validate_category_location';

        if (isset(self::$cache[$cache_key])) {
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

        if (isset(self::$cache[$cache_key])) {
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
	
	public static function is_child($post_id = null)
	{
		$output = false;

		if($post_id)
		{
			$post = get_post($post_id);
		}
		else
		{
			global $post;
		}

		if(isset($post))
		{
			if(property_exists($post, 'ID'))
			{
				$cache_key = $post->ID.'_is_child';

				if (isset(self::$cache[$cache_key])) {
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
	public static function has_children($post_id = null) {
		
		$output = false;

		if($post_id)
		{
			$post = get_post($post_id);
		}
		else
		{
			global $post;
		}

		if(isset($post))
		{
			if(property_exists($post, 'ID'))
			{
				$cache_key = $post->ID.'_has_children';
				
				if (isset(self::$cache[$cache_key])) {
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

	public static function is_parent_with_no_child($post_id = null)
	{
		$output = false;
		$cache_key = 'dy_is_parent_with_no_child';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$has_children = ($post_id) ? self::has_children($post_id) : self::has_children();
		$is_child = ($post_id) ? self::is_child($post_id) : self::is_child();

		if(!$has_children && !$is_child)
		{
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}
	
	public static function is_valid_schema($the_id = '')
	{
		$output = false;

		if($the_id === '')
		{
			$the_id = get_the_ID();
		}

		$cache_key = $the_id.'_is_valid_schema';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

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
	
	public static function is_package_single_day()
	{
		$output = false;
		global $post;

		if(isset($post))
		{
			$cache_key = $post->ID.'_is_package_single_day';

			if (isset(self::$cache[$cache_key])) {
				return self::$cache[$cache_key];
			}

			if(package_field('package_package_type') == 0)
			{
				$output = true;
			}
			
			//store output in $cache
			self::$cache[$cache_key] = $output;
		}

		return $output;		
	}
	
	public static function package_type_transport()
	{
		$output = false;
		global $post;

		if(isset($post))
		{
			$cache_key = $post->ID.'_package_type_transport';

			if (isset(self::$cache[$cache_key])) {
				return self::$cache[$cache_key];
			}
	
			$the_id = $post->ID;

			if($post->post_parent > 0)
			{
				$the_id = $post->post_parent;
			}
			
			$output = (intval(get_post_meta($the_id, 'package_package_type', true)) === 4) ? true : false;
			
			//store output in $cache
			self::$cache[$cache_key] = $output;
		}
		
		return $output;
	}

	public static function package_type_multi_day()
	{
		$output = false;
		global $post;

		if(isset($post))
		{
			$cache_key = $post->ID.'_package_type_multi_day';

			if (isset(self::$cache[$cache_key])) {
				return self::$cache[$cache_key];
			}
	
			if(package_field('package_package_type') == 1)
			{
				$output = true;
			}
			
			//store output in $cache
			self::$cache[$cache_key] = $output;
		}
		
		return $output;
	}

	public static function package_type_one_day()
	{
		$output = false;
		global $post;

		if(isset($post))
		{
			$cache_key = $post->ID.'_package_type_one_day';

			if (isset(self::$cache[$cache_key])) {
				return self::$cache[$cache_key];
			}
	
			if(package_field('package_package_type') == 0)
			{
				$output = true;
			}
			
			//store output in $cache
			self::$cache[$cache_key] = $output;

		}
		
		return $output;
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