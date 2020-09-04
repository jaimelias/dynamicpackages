<?php

class dy_Validators
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
			if(self::contact_details() && self::booking_details())
			{
				$output = true;
				$GLOBALS['dy_is_request_valid'] = $output;
			}		
		}
		return $output;
	}
	
	public static function contact_details()
	{
		$output = false;
		global $dy_contact_details;
		
		if(isset($dy_contact_details))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['first_name']) && isset($_POST['lastname']) && isset($_POST['phone']) && isset($_POST['email']))
			{
				if(is_email($_POST['email']) && !empty($_POST['first_name']) && !empty($_POST['lastname']) && !empty($_POST['phone']))
				{
					$output = true;
					$GLOBALS['dy_contact_details'] = $output;
				}
			}		
		}
		return $output;
	}
	
	public static function validate_recaptcha()
	{
		global $dy_valid_recaptcha;
		
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
					write_log(json_encode($verify_response['error-codes']));
				}
			}
		}
	}
	
	public static function validate_checkout()
	{
		$output = false;

		if(self::contact_details() && isset($_POST['country']) && isset($_POST['address']) && self::booking_details() && self::credit_card())
		{
			$output = true;
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
			if(isset($_POST['departure_date']) && isset($_POST['check_in_hour']) && isset($_POST['booking_hour']) && isset($_POST['duration']) && isset($_POST['description']) && isset($_POST['pax_num']) && isset($_POST['total']))
			{	
				$output = true;
				$GLOBALS['booking_details'] = $output;
			}	
		}
		return $output;		
	}
	public static function credit_card()
	{
		$output = false;
		global $credit_card;
		
		if(isset($credit_card))
		{
			return true;
		}
		else
		{
			if(isset($_POST['CCNum']) && isset($_POST['ExpMonth']) && isset($_POST['ExpYear']) && isset($_POST['CVV2']))
			{
				$output = true;
				$GLOBALS['credit_card'] = $output;
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
			if(self::has_coupon() && isset($_GET['booking_coupon']))
			{
				if($_GET['booking_coupon'] != '')
				{
					$booking_coupon = strtolower(sanitize_text_field($_GET['booking_coupon']));
					$booking_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $booking_coupon);
					$get_coupon = strtolower(dy_utilities::get_coupon('code'));
					$get_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $get_coupon);
					
					if($get_coupon == $booking_coupon)
					{
						$expiration = dy_utilities::get_coupon('expiration');

						if($expiration == '')
						{
							$output = true;
						}
						else
						{
							$expiration_stamp = new DateTime($expiration);
							$expiration_stamp->setTime(0,0,0);
							$expiration_stamp = $expiration_stamp->getTimestamp();							
							
							if($expiration_stamp >= dy_strtotime('today midnight'))
							{
									$output = true;
							}							
						}
					}					
				}
				
				if($output === true)
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
			if(!isset($_GET['quote']))
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
			if(isset($_POST['deposit']))
			{
				if($_POST['deposit'] > 0)
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
			$package_departure_address = package_field('package_departure_address');
			$package_departure_hour = package_field('package_departure_hour');
			
			if($package_departure_address != '' && $package_departure_hour != '')
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
}


?>