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
			return ($_REQUEST['booking_date']) ? strtotime(sanitize_text_field($_REQUEST['booking_date'])) : null;
		}
	}
	public static function handsontable($args)
	{
		$output = null;
		
		if(is_array($args))
		{
			if(array_key_exists('container', $args) && array_key_exists('textarea', $args) && array_key_exists('headers', $args) && array_key_exists('type', $args) && array_key_exists('max', $args) && array_key_exists('value', $args))
			{				
				if(!array_key_exists('min', $args))
				{
					$args['min'] = $args['max'];
				}
				
				$default = array();
				
				for($x = 0; $x < count($args['headers']); $x++)
				{
					$default[] = 'null';
				}

				$decoded_value = json_decode(html_entity_decode($args['value']), true);
				
				
				$args['value'] = (is_array($decoded_value)) ? $args['value'] : '["'.$args['container'].'":[['.implode(',', $default).']]]';
				
				$dropdown = (array_key_exists('dropdown', $args)) ? 'data-sensei-dropdown="'.implode(',', $args['dropdown']).'"' : null;
				
				$disabled = (array_key_exists('disabled', $args)) ? $args['disabled'] : null;
				
				ob_start();
				?>
					<div class="hot-container">
						<div id="<?php esc_html_e($args['container']); ?>" class="hot" data-sensei-min="<?php esc_html_e($args['min']); ?>" data-sensei-max="<?php esc_html_e($args['max']); ?>" data-sensei-container="<?php esc_html_e($args['container']); ?>" data-sensei-textarea="<?php esc_html_e($args['textarea']); ?>" data-sensei-headers="<?php esc_html_e(implode(',', $args['headers'])); ?>" data-sensei-type="<?php esc_html_e(implode(',', $args['type'])); ?>" <?php echo $dropdown; ?> data-sensei-disabled="<?php esc_html_e($disabled); ?>"></div>
					</div>
					<div class="hidden"><textarea name="<?php esc_html_e($args['textarea']); ?>" id="<?php esc_html_e($args['textarea']); ?>"><?php echo esc_attr($args['value']); ?></textarea></div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			}
		}
		
		return $output;
	}
	public static function end_date()
	{
		$output = null;
		
		if(isset($_REQUEST['end_date']))
		{
			$output = ($_REQUEST['end_date']) ? strtotime(sanitize_text_field($_REQUEST['end_date'])) : null;	
		}
		
		return $output;
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
								$output = (isset($coupons[$x][0])) ? $coupons[$x][0] : null;
							}
							else if($option == 'discount')
							{
								$output = (isset($coupons[$x][1])) ? $coupons[$x][1] : null;
								$output = (is_numeric($output)) ? $output : null;
							}	
							else if($option == 'expiration')
							{
								$output = (isset($coupons[$x][2])) ? $coupons[$x][2] : null;
								$output = (dy_validators::is_date($output)) ? $output : null;
							}
							else if($option == 'publish')
							{
								$output = (isset($coupons[$x][3])) ? $coupons[$x][3] : null;
							}
							else if($option == 'min_duration')
							{
								$output = (isset($coupons[$x][4])) ? $coupons[$x][4] : null;
								$output = (is_numeric($output)) ? intval($output) : 0;
							}
							else if($option == 'max_duration')
							{
								$output = (isset($coupons[$x][5])) ? $coupons[$x][5] : null;
								$output = (is_numeric($output)) ? intval($output) : 0;
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


	public static function total($regular = null)
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
				$total = self::subtotal($regular);
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

	public static function subtotal($regular = null)
	{
		
		$price_chart = self::get_price_chart();	
		$sum = 0;
		$sum = floatval(self::get_price_regular($regular, 'total')) + $sum;
		$sum = floatval(self::get_price_discount($regular, 'total')) + $sum;
		$pax_num = self::pax_num();
		
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
						$add_ons_price += floatval($pax_num) * floatval($add_ons[$x]['price']);
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
			
			if(dy_validators::has_children() && (in_the_loop() || is_singular('packages')))
			{
				$prices = array();
				$children = dy_validators::has_children();
		
				foreach ( $children as $child )
				{
					array_push($prices, self::starting_at($child->ID, $the_id));
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
	public static function starting_at($id = null, $parent_id = null)
	{
		$the_id = $id;
		
		if($the_id === null)
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
			$price_type = ($parent_id) ? package_field('package_fixed_price', $parent_id) : package_field('package_fixed_price', $the_id);
			$duration_unit = package_field('package_length_unit');
			$duration_max = package_field('package_duration_max');
			$package_type = package_field('package_package_type');
					
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
								
								$occupancy_price = floatval($occupancy_chart[$t][0]);
								
								if(intval($duration_max) == 0 && $package_type != 1)
								{
									$occupancy_price = $occupancy_price * $duration;
								}
							}
						}
					}
					
					if($base_price > 0 && $occupancy_price > 0 && $duration > 1 && $package_type == 1)
					{
						$price = ($base_price + ($occupancy_price * $duration)) / $duration;
					}
					else
					{
						$price = $base_price + $occupancy_price;
					}
					
					
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
					if($min > 1)
					{
						array_slice($prices, ($min - 1), count($prices));
					}
					
					$output = floatval(min($prices));
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}


	public static function get_price_chart($the_id = null)
	{
		if($the_id === null)
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
		$which_var = 'seasons_chart_'.get_the_ID();
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

	public static function get_date_range($from, $to, $add_extra = true)
	{
		$output = array();
		$from = new DateTime($from);
		$to = new DateTime($to);
		
		if($add_extra === true)
		{
			$to = $to->modify('+1 day');
		}
			
		
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
		
		if(is_array($disabled))
		{
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
		if(is_booking_page() || is_checkout_page())
		{
			$duration = intval(package_field('package_duration'));
			$booking_date = sanitize_text_field($_REQUEST['booking_date']);
			$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
			$booking_dates_range = self::get_date_range($booking_date, $booking_date_to, false);
			$seasons = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			$duration_arr = [];
			
			if(isset($_REQUEST['booking_extra']))
			{
				if($_REQUEST['booking_extra'] > $duration)
				{
					$duration = intval(sanitize_text_field($_REQUEST['booking_extra']));
				}
			}
			
			if(is_array($seasons))
			{
				if(array_key_exists('seasons_chart', $seasons))
				{
					$seasons = $seasons['seasons_chart'];
					
					for($s = 0; $s < count($seasons); $s++)
					{
						$from_season = $seasons[$s][1];
						$to_season = $seasons[$s][2];
						$duration_season = $seasons[$s][3];
						$seasons_dates_range = self::get_date_range($from_season, $to_season, false);
						
						if(is_array($seasons_dates_range))
						{
							for($x = 0; $x < count($seasons_dates_range); $x++)
							{
								for($d = 0; $d < count($booking_dates_range); $d++)
								{
									if(in_array($booking_dates_range[$d], $seasons_dates_range))
									{
										array_push($duration_arr, intval($duration_season));
									}
								}
							}							
						}
					}
				}
			}
			
			if(is_array($duration_arr))
			{
				if(count($duration_arr) > 0)
				{
					$max_duration = max($duration_arr);
					
					if(count($duration_arr) > 0 && $max_duration > $duration)
					{
						$duration = $max_duration;
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
	

	public static function get_price_occupancy($type = null)
	{
		if(isset($_REQUEST['booking_date']))
		{
			$sum = 0;
			$occupancy_chart = json_decode(html_entity_decode(package_field('package_occupancy_chart' )), true);
			$duration = self::get_min_nights();
			$seasons = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			$booking_date = sanitize_text_field($_REQUEST['booking_date']);
			$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
			$booking_dates_range = self::get_date_range($booking_date, $booking_date_to, false);
			$seasons_array = array();

			if(is_array($occupancy_chart) && is_array($seasons) && is_array($occupancy_chart) && is_array($booking_dates_range))
			{
				if($duration == count($booking_dates_range))
				{
					for($d = 0; $d < $duration; $d++)
					{
						$new_date = date('Y-m-d', strtotime($booking_date . " +$d days"));
						$season = self::get_season($booking_dates_range[$d]);
						
						if($season == 'price_chart')
						{
							$occupancy_key = 'occupancy_chart';
						}
						else
						{
							$occupancy_key = 'occupancy_chart'.$season;
						}
						
						array_push($seasons_array, $occupancy_key);
					}
								
					for($s = 0; $s < count($seasons_array); $s++)
					{
						if(array_key_exists($s, $seasons_array) && $occupancy_chart != '')
						{
							if(array_key_exists($seasons_array[$s], $occupancy_chart))
							{
								for($a = 0;  $a < count($occupancy_chart[$seasons_array[$s]]); $a++)
								{
									if(floatval(sanitize_text_field($_REQUEST['pax_regular'])) == ($a+1))
									{	
										if($occupancy_chart[$seasons_array[$s]][$a][0] != '')
										{									
											//total occupancy price
											if($type == 'regular')
											{
												$sum = $sum + floatval($occupancy_chart[$seasons_array[$s]][$a][0]);
											}
											
											//total children discounts
											if(isset($_REQUEST['pax_discount']) && $type == 'discount')
											{
												if($_REQUEST['pax_discount'] > 0 && $occupancy_chart[$seasons_array[$s]][$a][1] != '')
												{
													$sum = $sum + floatval($occupancy_chart[$seasons_array[$s]][$a][1]);
												}
											}
										}
									}	
								}						
							}
						}
					}					
				}				
			}

			return $sum;			
		}
	}

	public static function get_price_regular($regular = null, $type = null)
	{
		$sum = 0;
		
		if(is_booking_page() || is_checkout_page())
		{			
			$base_price = 0;
			$price_chart = self::get_price_chart();
			$pax_regular = (isset($_REQUEST['pax_regular'])) ? floatval(sanitize_text_field($_REQUEST['pax_regular'])) : 0;

			if(is_array($price_chart))
			{
				for ($x = 0; $x < count($price_chart); $x++)
				{
					if($pax_regular == ($x+1))
					{
						if($price_chart[$x][0] != '')
						{
							$base_price = floatval($price_chart[$x][0]);
						}
					}
				}
				
				$sum = self::get_price_calc($base_price, $regular, 'regular');
				
				if($type == 'total' && $pax_regular > 0)
				{
					$sum = $sum * $pax_regular;
				}				
			}
		}
		return $sum;
	}	


	
	public static function get_price_discount($regular = null, $type = null)
	{
		$sum = 0;
		
		if(is_booking_page() || is_checkout_page())
		{
			$base_price = 0;
			$price_chart = self::get_price_chart();
			$pax_discount = (isset($_REQUEST['pax_discount'])) ? floatval(sanitize_text_field($_REQUEST['pax_discount'])) : 0;

			if(is_array($price_chart))
			{
				for($x = 0; $x < count($price_chart); $x++)
				{
						if($pax_discount == floatval(($x+1)))
						{
							$base_price = 0;
							
							if($price_chart[$x][1] != '')
							{
								$base_price = floatval($price_chart[$x][1]);
							}
						}
				}
				
				$sum = self::get_price_calc($base_price, $regular, 'discount');
				
				if($type == 'total' && $pax_discount > 0)
				{
					$sum = $sum * $pax_discount;
				}				
			}
		}
		
		return $sum;
	}
	
	public static function get_price_calc($sum, $regular, $type)
	{
		$length_unit = package_field('package_length_unit');
		$occupancy_price = ($length_unit == 2 || $length_unit == 3) ? self::get_price_occupancy($type) : 0;
		$sum = $sum + $occupancy_price;
		
		if((self::increase_by_hour() || self::increase_by_day())  && isset($_REQUEST['booking_extra']))
		{
			$sum = $sum * intval(sanitize_text_field($_REQUEST['booking_extra']));
		}

		if(dy_validators::is_package_transport() && isset($_REQUEST['end_date']))
		{
			if(strlen($_REQUEST['end_date']) >= 5)
			{
				$sum = $sum * 2;
			}
		}		
		
		if(dy_validators::valid_coupon() && $regular === null)
		{
			$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
		}

		return $sum;
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

		if(package_field('package_start_hour' ))
		{
			if(package_field('package_start_hour' ) != '')
			{
				$hour = package_field('package_start_hour');
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
		
		if(dy_validators::has_deposit())
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
		return date_i18n(get_option('date_format'), $date);
	}
	public static function remove_emoji($text){
		  return preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]/u', '', $text);
	}
}