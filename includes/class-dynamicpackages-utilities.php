<?php

class dy_utilities {
	
	public static function currency_format($amount)
	{
		return number_format(floatval($amount), 2, '.', '');
	}

	public static function currency_symbol()
	{
		return '$';
	}
	
	public static function currency_name()
	{
		return 'USD';
	}	
	
	public static function booking_date()
	{
		if(isset($_REQUEST['booking_date']))
		{
			return strtotime(sanitize_text_field($_REQUEST['booking_date']));
		}
	}
	
	public static function return_date()
	{
		if(isset($_REQUEST['return_date']))
		{
			return strtotime(sanitize_text_field($_REQUEST['return_date']));
		}
	}

	public static function min_range()
	{
		global $date_from;
		$min_range = strtotime("+ {$date_from} days", strtotime('today midnight'));
		//fix first day
		return strtotime("- 1 days", $min_range);	
	}	

	public static function max_range()
	{
		global $date_to;
		return strtotime("+ {$date_to} days", strtotime('today midnight'));		
	}
	
	public static function pax_num()
	{
		$output = 0;
		
		if(isset($_REQUEST['pax_regular']))
		{
			$output = intval(sanitize_text_field($_REQUEST['pax_regular']));
		}
		
		if(isset($_REQUEST['pax_discount']))
		{
			$output = $output + intval(sanitize_text_field($_REQUEST['pax_discount']));
		}
		
		if(isset($_REQUEST['pax_free']))
		{
			$output = $output + intval(sanitize_text_field($_REQUEST['pax_free']));
		}		
		
		return $output;
	}
	
	public static function get_coupon($option)
	{
		$which_var = $option.'_get_coupon';
		global $$which_var;
		$output = null;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$option = strtolower($option);
			$coupons = json_decode(html_entity_decode(package_field('package_coupons' )), true);
			$output = 'option not selected';
			$booking_coupon = strtolower(sanitize_text_field($_REQUEST['booking_coupon']));
			$booking_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $booking_coupon);
			
			if(is_array($coupons))
			{
				if(array_key_exists('coupons', $coupons))
				{
					$coupons = $coupons['coupons'];
					
					for($x = 0; $x < count($coupons); $x++)
					{
						if($booking_coupon == preg_replace("/[^A-Za-z0-9 ]/", '', strtolower($coupons[$x][0])))
						{
							if($option == 'code')
							{
								$output = $coupons[$x][0];
							}
							else if($option == 'discount')
							{
								$output = $coupons[$x][1];
							}	
							else if($option == 'expiration')
							{
								$output = $coupons[$x][2];
							}
							else if($option == 'from')
							{
								$output = $coupons[$x][3];
							}
							else if($option == 'to')
							{
								$output = $coupons[$x][4];
							}					
						}
					}
				}				
			}
			
			if($output != null)
			{
				$GLOBALS[$which_var] = $output;
			}
		}
		
		return $output;
	}	


	public static function total($regular = '')
	{ 
		$which_var = 'dy_total_'.$regular;
		global $$which_var; 
		$total = 0;
		
		if(isset($$which_var))
		{
			$total = $$which_var;
		}
		else
		{
			if(is_booking_page() || is_checkout_page())
			{	
				if($regular == 'regular')
				{
					$total = self::subtotal_regular();
				}
				else
				{
					$total = self::subtotal();
				}			
			}
			else
			{
				$total = self::starting_at();
			}
			
			if($total != 0)
			{
				$GLOBALS[$which_var] = $total;
			}
		}
		
		return $total;
	}

	public static function subtotal()
	{
		$sum = self::subtotal_regular();
		
		if(dy_Validators::valid_coupon())
		{
			$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
		}
		
		return $sum;
	}
	
	public static function subtotal_regular()
	{
		$price_chart = self::get_price_chart();	
		$sum = 0;
		$sum_adults = 0;
		$each_adult = 0;
		$sum_children = 0;
		$pax_num = self::pax_num();
		$each_child = 0;
		$length_unit = package_field('package_length_unit');
	
		if(is_array($price_chart))
		{
			for($a = 0;  $a < count($price_chart); $a++)
			{
				if(floatval(sanitize_text_field($_REQUEST['pax_regular'])) == ($a+1))
				{
					if($price_chart[$a][0] != '')
					{
						$each_adult = floatval($price_chart[$a][0]);
					}
					
					$sum_adults = $each_adult*floatval(sanitize_text_field($_REQUEST['pax_regular']));
					$sum = $sum + $sum_adults;		
				}
				if(isset($_REQUEST['pax_discount']))
				{
					if(floatval(sanitize_text_field($_REQUEST['pax_discount'])) == floatval(($a+1)))
					{
						if(floatval($price_chart[$a][1]) > 0 && $price_chart[$a][1] != 0)
						{
							$each_child = floatval($price_chart[$a][1]);
							$sum_children = $each_child*floatval(sanitize_text_field($_REQUEST['pax_discount']));
							$sum = $sum + $sum_children;
						}			
					}			
				}		
			}			
		}
		
		if(intval($length_unit) == 2 || intval($length_unit) == 3)
		{
			$sum = $sum + floatval(self::get_price_per_night());
		}

		if(self::increase_by_hour() || self::increase_by_day())
		{
			$sum = $sum * intval(sanitize_text_field($_REQUEST['booking_extra']));
		}
		
		if(dy_Validators::is_package_transport() && isset($_REQUEST['return_date']))
		{
			if(strlen($_REQUEST['return_date']) >= 5)
			{
				$sum = $sum * 2;
			}
		}
		
		if(dy_Tax_Mod::has_add_ons() && isset($_POST['add_ons']))
		{
			$add_ons = dy_Tax_Mod::get_add_ons();
			$add_ons_included = explode(',', sanitize_text_field($_POST['add_ons']));
			$add_ons_price = 0;
			$add_ons_count = count($add_ons);
			
			if(is_array($add_ons) && is_array($add_ons_included))
			{
				for($x = 0; $x < $add_ons_count; $x++)
				{
					if(in_array($add_ons[$x]['id'], $add_ons_included))
					{
						$add_ons_price = floatval($pax_num) * floatval($add_ons[$x]['price']);
					}
				}
				
				$sum = $sum + $add_ons_price;			
			}			
		}
						
		return $sum;
	}

	public static function starting_at_archive($id = '')
	{
		$the_id = $id;
		
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}
		
		$name = 'dy_start_archive';
		$which_var = $name.'_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$output = self::starting_at();
			
			if(dy_Validators::has_children() && in_the_loop())
			{
				$prices = array();
				$children = dy_Validators::has_children();
				
				foreach ( $children as $child )
				{
					array_push($prices, self::starting_at($child->ID));
				}

				if(is_array($prices))
				 {
					if(count($prices) > 0)
					{
						 $output = min($prices);
					}
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}
		
		return $output;
	}
	public static function starting_at($id = '')
	{
		$the_id = $id;
		
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}
		
		$output = 0;
		$name = 'dy_starting_at';
		$which_var = $name.'_'.$the_id;
		global $$which_var;		
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$prices = array();
			$max = intval(package_field('package_max_persons', $the_id));
			$min = intval(package_field('package_min_persons', $the_id));
			$duration = floatval(package_field('package_duration'));
			$price_chart = self::get_price_chart($the_id);
			$occupancy_chart = self::get_occupancy_chart($the_id);	
			$occupancy_chart = (is_array($occupancy_chart)) ? $occupancy_chart['occupancy_chart'] : null;
			$price_type = package_field('package_starting_at_unit');
			$package_type = package_field('package_package_type');
			$mix = array();
					
			for($t = 0; $t < intval($max); $t++)
			{
				if($t >= ($min-1))
				{
					$base_price = 0;
					$occupancy_price = 0;
					
					if(is_array($price_chart))
					{
						if(isset($price_chart[$t][0]))
						{
							if($price_chart[$t][0] != '')
							{
								$base_price = floatval($price_chart[$t][0]);
							}
						}
					}
					if(is_array($occupancy_chart))
					{
						if(isset($occupancy_chart[$t][0]))
						{
							if($occupancy_chart[$t][0] != '')
							{
								$occupancy_price = floatval($occupancy_chart[$t][0]) * $duration;
							}
						}
					}
					
					$price = $base_price + $occupancy_price;
					
					if($price_type == 1)
					{
						$price = $price * intval($t+1);
					}
								
					array_push($prices, $price);				
				}
			}
							
			if(is_array($prices))
			{
				if(count($prices) > 0)
				{
					$output = floatval(min($prices));
				}
			}
			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}


	public static function get_price_chart($the_id = '')
	{
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}
		
		$output = null;
		$which_var = '$price_chart_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$price_chart = json_decode(html_entity_decode(package_field('package_price_chart', $the_id)), true);
		
			if(is_array($price_chart))
			{
				if(array_key_exists('price_chart', $price_chart))
				{
					$GLOBALS[$which_var] = $price_chart['price_chart'];
					$output = $price_chart['price_chart'];
				}
			}			
		}
		return $output;
	}

	public static function increase_by_hour()
	{
		$package_type = intval(package_field('package_package_type' ));
		$min_duration = intval(package_field('package_duration' ));
		$max_duration = intval(package_field('package_duration_max' ));	
		$length_unit = package_field('package_length_unit');
		
		
		if($package_type == 3 && $min_duration == 1 && $length_unit == 1 && $max_duration > $min_duration)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function increase_by_day()
	{
		$package_type = intval(package_field('package_package_type' ));
		$min_duration = intval(package_field('package_duration' ));
		$max_duration = intval(package_field('package_duration_max' ));	
		$length_unit = package_field('package_length_unit');
		
		
		if($package_type == 2 && $min_duration == 1 && $length_unit == 2 && $max_duration > $min_duration)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public static function get_occupancy_chart($the_id = '')
	{
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}		
		
		$output = null;
		$which_var = '$occupancy_chart_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$chart = json_decode(html_entity_decode(package_field('package_occupancy_chart', $the_id)), true);
			$GLOBALS[$which_var] = $chart;
			$output = $chart;
		}
		
		return $output;
	}
	public static function get_season_chart()
	{
		//package_seasons_chart
		$output = null;
		$which_var = '$seasons_chart_'.get_the_ID();
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$chart = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			$GLOBALS[$which_var] = $chart;
			$output = $chart;
		}
		
		return $output;
	}	

	public static function get_date_range($from, $to)
	{
		$output = array();
		$from = new DateTime($from);
		$to = new DateTime($to);
		$to = $to->modify('+1 day');
		
		$range = new DatePeriod($from, new DateInterval('P1D'), $to);

		foreach ($range as $key => $value)
		{
			array_push($output, $value->format('Y-m-d'));
		}
		
		return $output;
	}

	public static function get_disabled_range()
	{
		$output = array();
		$disabled = json_decode(html_entity_decode(package_field('package_disabled_dates' )), true);
		
		if(array_key_exists('disabled_dates', $disabled))
		{		
			$disabled_dates = $disabled['disabled_dates'];
					
			for($x = 0; $x < count($disabled_dates); $x++)
			{
				$from = $disabled_dates[$x][0];
				$to = $disabled_dates[$x][1];
				array_push($output, self::get_date_range($from, $to));
			}
		}
		
		return self::arrayFlatten($output);
	}	

	static function arrayFlatten($array) { 
		$output = array();
		
		for($x = 0; $x < count($array); $x++)
		{
			for($y = 0; $y < count($array[$x]); $y++)
			{
				array_push($output, $array[$x][$y]);
			}
		}
		return array_unique($output);
	}	


	public static function get_min_nights()
	{
		if((is_booking_page() || is_checkout_page()) && isset($_REQUEST['booking_date']))
		{
			$duration = intval(package_field('package_duration'));
			
			if(isset($_REQUEST['booking_extra']))
			{
				if($_REQUEST['booking_extra'] > $duration)
				{
					$duration = intval(sanitize_text_field($_REQUEST['booking_extra']));
				}
			}
			
			$seasons = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			
			if(is_array($seasons))
			{
				if(array_key_exists('seasons_chart', $seasons))
				{
					$seasons = $seasons['seasons_chart'];
					
					for($d = 1; $d < $duration; $d++)
					{
						$booking_date = sanitize_text_field($_REQUEST['booking_date']);
						$new_date = strtotime($booking_date . " +$d days");
						
						for($x = 0; $x < count($seasons); $x++)
						{
							$from_season = strtotime($seasons[$x][1]);
							$to_season = strtotime($seasons[$x][2]);
					
							if($new_date >= $from_season && $new_date <= $to_season)
							{
								if($seasons[$x][3] > $duration)
								{
									$duration = $seasons[$x][3];
								}
							}			
						}
					}
				}
			}
			$output = $duration;
			return $output;	
		}
	}	
	public static function get_season($booking_date)
	{
		if(is_booking_page() || is_checkout_page())
		{
			$season = 'price_chart';
			$seasons = self::get_season_chart();
					
			if($seasons != null)
			{
				if(array_key_exists('seasons_chart', $seasons))
				{
					$seasons = $seasons['seasons_chart'];

					$booking_date = strtotime(sanitize_text_field($booking_date));
						
					for($x = 0; $x < count($seasons); $x++)
					{
						$from_season = strtotime($seasons[$x][1]);
						$to_season = strtotime($seasons[$x][2]);
				
						if($booking_date >= $from_season && $booking_date <= $to_season)
						{
							$last_cell = count($seasons[$x]) - 1;
							$season = $seasons[$x][$last_cell];
						}			
					}
				}
			}	
			$output = $season;
			return $output;			
		}
	}
	

	public static function get_price_per_night()
	{
		if(isset($_REQUEST['booking_date']))
		{
			$sum = 0;
			$package_occupancy_chart = json_decode(html_entity_decode(package_field('package_occupancy_chart' )), true);
			$duration = self::get_min_nights();
			$seasons = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			$booking_date = sanitize_text_field($_REQUEST['booking_date']);

			$seasons_array = array();
						
			for($d = 0; $d < $duration; $d++)
			{
				$new_date = date('Y-m-d', strtotime($booking_date . " +$d days"));
				$is_season = self::get_season($new_date);
				
				if($is_season == 'price_chart')
				{
					$occupancy_key = 'occupancy_chart';
				}
				else
				{
					$occupancy_key = 'price_chart'.$is_season;
				}
				
				array_push($seasons_array, $occupancy_key);	
			}
			
			
			for($s = 0; $s < count($seasons_array); $s++)
			{
				if(array_key_exists($s, $seasons_array) && $package_occupancy_chart != '')
				{
					if(array_key_exists($seasons_array[$s], $package_occupancy_chart))
					{
						for($a = 0;  $a < count($package_occupancy_chart[$seasons_array[$s]]); $a++)
						{
							if(floatval(sanitize_text_field($_REQUEST['pax_regular'])) == ($a+1))
							{	
								if($package_occupancy_chart[$seasons_array[$s]][$a][0] != '')
								{
									//total occupancy price
									$each_adult = floatval($package_occupancy_chart[$seasons_array[$s]][$a][0]);
									$sum = $each_adult * floatval(sanitize_text_field($_REQUEST['pax_regular']));
									
									//total children discounts
									if(isset($_REQUEST['pax_discount']))
									{
										if($_REQUEST['pax_discount'] > 0 && $package_occupancy_chart[$seasons_array[$s]][$a][1] != '')
										{
											$each_child = floatval($package_occupancy_chart[$seasons_array[$s]][$a][1]);
											$sum = $sum + ($each_child * floatval(sanitize_text_field($_REQUEST['pax_discount'])));										}
									}
									
									$sum = $sum * $duration;
								}
							}	
						}						
					}
				}
			}
			

			return $sum;			
		}
	}

	public static function get_price_adults()
	{
		if(is_booking_page() || is_checkout_page())
		{
			$sum = 0;
			$base_price = 0;
			$occupancy_price = 0;			
			$price_chart = self::get_price_chart();
			$occupancy_chart = self::get_occupancy_chart();	
			$duration = floatval(self::get_min_nights());
			$seasons = self::get_season_chart();
			$booking_date = sanitize_text_field($_REQUEST['booking_date']);
			$seasons_array = array();
						
			for($d = 0; $d < $duration; $d++)
			{
				$new_date = date('Y-m-d', strtotime($booking_date . " +$d days"));
				$is_season = self::get_season($new_date);
				
				if($is_season == 'price_chart')
				{
					$occupancy_key = 'occupancy_chart';
				}
				else
				{
					$occupancy_key = 'price_chart'.$is_season;
				}
				
				if(package_field('package_package_type' ) == 1)
				{
					array_push($seasons_array, $occupancy_key);	
				}
			}

			for ($x = 0; $x < count($price_chart); $x++)
			{
				if(floatval(sanitize_text_field($_REQUEST['pax_regular'])) == ($x+1))
				{
					if($price_chart[$x][0] != '')
					{
						$base_price = floatval($price_chart[$x][0]);
					}
				}
			}
			
			
			if(is_array($seasons_array) && is_array($occupancy_chart))
			{
				for($s = 0; $s < count($seasons_array); $s++)
				{
					if(array_key_exists($s, $seasons_array))
					{
						if(array_key_exists($seasons_array[$s], $occupancy_chart))
						{
							for($a = 0;  $a < count($occupancy_chart[$seasons_array[$s]]); $a++)
							{
								if(floatval(sanitize_text_field($_REQUEST['pax_regular'])) == ($a+1))
								{
									if($occupancy_chart[$seasons_array[$s]][$a][0] != '')
									{
										$occupancy_price = floatval($occupancy_chart[$seasons_array[$s]][$a][0]);
										$occupancy_price = $occupancy_price * $duration;
									}
								}		
							}						
						}
					}
				}				
			}
			
			$sum = $base_price + $occupancy_price;

			if((self::increase_by_hour() || self::increase_by_day())  && isset($_REQUEST['booking_extra']))
			{
				$sum = $sum * intval(sanitize_text_field($_REQUEST['booking_extra']));
			}

			if(dy_Validators::is_package_transport() && isset($_REQUEST['return_date']))
			{
				if(strlen($_REQUEST['return_date']) >= 5)
				{
					$sum = $sum * 2;
				}
			}
			
			if(dy_Validators::valid_coupon())
			{
				$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
			}
			
			return $sum;			
		}
	}	


	
	public static function get_price_discount()
	{
		if(is_booking_page() || is_checkout_page())
		{
			$sum = 0;
			$base_price = 0;
			$occupancy_price = 0;			
			$price_chart = self::get_price_chart();
			$package_occupancy_chart = self::get_occupancy_chart();	
			$duration = self::get_min_nights();
			$seasons = self::get_season_chart();
			$booking_date = sanitize_text_field($_REQUEST['booking_date']);
			$seasons_array = array();
		
			for($d = 0; $d < $duration; $d++)
			{
				$new_date = date('Y-m-d', strtotime($booking_date . " +$d days"));
				$is_season = self::get_season($new_date);
				
				if($is_season == 'price_chart')
				{
					$occupancy_key = 'occupancy_chart';
				}
				else
				{
					$occupancy_key = 'price_chart'.$is_season;
				}
				
				array_push($seasons_array, $occupancy_key);	
			}
			
			for($x = 0; $x < count($price_chart); $x++)
			{
				if(isset($_REQUEST['pax_discount']))
				{
					if(floatval(sanitize_text_field($_REQUEST['pax_discount'])) == floatval(($x+1)))
					{
						$base_price = 0;
						
						if($price_chart[$x][1] != '')
						{
							$base_price = floatval($price_chart[$x][1]);
						}
					}					
				}
			}
			
			for($s = 0; $s < count($seasons_array); $s++)
			{
				if(array_key_exists($s, $seasons_array))
				{
					if($seasons_array[$s] != '' && $package_occupancy_chart != '')
					{
						if(array_key_exists($seasons_array[$s], $package_occupancy_chart))
						{
							for($a = 0;  $a < count($package_occupancy_chart[$seasons_array[$s]]); $a++)
							{
								if(isset($_REQUEST['pax_discount']))
								{
									if(floatval(sanitize_text_field($_REQUEST['pax_discount'])) == floatval(($a+1)))
									{
										$occupancy_price = 0;
										
										if($package_occupancy_chart[$seasons_array[$s]][$a][1] != '')
										{
											$occupancy_price = floatval($package_occupancy_chart[$seasons_array[$s]][$a][1]);
											$occupancy_price = $occupancy_price * $duration;
										}		
									}			
								}		
							}						
						}						
					}
				}				
			}
			
			$sum = $base_price + $occupancy_price;
			
			if((self::increase_by_hour() || self::increase_by_day())  && isset($_REQUEST['booking_extra']))
			{
				$sum = $sum * intval(sanitize_text_field($_REQUEST['booking_extra']));
			}

			if(dy_Validators::is_package_transport() && isset($_REQUEST['return_date']))
			{
				if(strlen($_REQUEST['return_date']) >= 5)
				{
					$sum = $sum * 2;
				}
			}			
			
			if(dy_Validators::valid_coupon())
			{
				$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
			}			
			
			return $sum;			
		}
	}

	public static function get_deposit()
	{
		global $dy_get_deposit;
		$output =  25;
		
		if(isset($dy_get_deposit))
		{
			$output = $dy_get_deposit;
		}
		else
		{
			if(package_field('package_payment' ) == 1 && intval(package_field('package_auto_booking')) == 1)
			{
				if(floatval(package_field('package_deposit' )) > 0)
				{
					$output = package_field('package_deposit');
					
					if(isset($_GET['quote']))
					{
						$output = 0;
					}
				}
			}
			else
			{
				$output = 0;
			}
			
			$GLOBALS['dy_get_deposit'] = $output;
		}
		return $output;
	}

	public static function get_week_days_list()
	{
		$output = array();
		$days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
		
		for($x = 0; $x < count($days); $x++)
		{
			if(intval(package_field('package_day_'.$days[$x] )) == 1)
			{
				array_push($output, $x+1);
			}
		}
		return $output;
	}	
	
	public static function tax()
	{
		return floatval(get_option('dy_tax'));
	}	
	
	public static function hour()
	{
		$hour = null;

		if(package_field('package_departure_hour' ))
		{
			if(package_field('package_departure_hour' ) != '')
			{
				$hour = package_field('package_departure_hour');
			}
		}
		
		if(isset($_REQUEST['booking_hour']))
		{
			$hour = sanitize_text_field($_REQUEST['booking_hour']);
		}
		
		return $hour;
	}	
	
	public static function return_hour()
	{
		$hour = null;

		if(package_field('package_return_hour' ))
		{
			if(package_field('package_return_hour' ) != '')
			{
				$hour = package_field('package_return_hour');
			}
		}
		
		if(isset($_REQUEST['return_hour']))
		{
			$hour = sanitize_text_field($_REQUEST['return_hour']);
		}
		
		return $hour;
	}


	public static function webhook($option, $data)
	{
		$webhook = get_option($option);
		
		if($webhook)
		{
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

	}
	
	public static function implode_taxo_names($tax)
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
	
	public static function payment_amount()
	{
		$total = floatval(dy_utilities::total());
		
		if(dy_Validators::has_deposit())
		{
			$deposit = floatval(dy_utilities::get_deposit());
			$total = $total*($deposit*0.01);			
		}
		
		return $total;
	}	

	public static function outstanding_amount()
	{
		$total = (self::total()) ? floatval(self::total()) : 0;
		$payment_amount = (self::payment_amount()) ? floatval(self::payment_amount()) : 0;
		return $total - $payment_amount;
	}
	public static function format_date($date)
	{
		return date_i18n(get_option('date_format'), strtotime(sanitize_text_field($date)));
	}
}