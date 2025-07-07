<?php

if ( !defined( 'WPINC' ) ) exit;


#[AllowDynamicProperties]
class dy_utilities {

	private static $cache = [];

	public static function sort_by_arr()
	{
		return array('new', 'low', 'high', 'today', 'tomorrow', 'week', 'month');
	}

	public static function booking_date()
	{
		if(isset($_REQUEST['booking_date']))
		{
			return ($_REQUEST['booking_date']) ? strtotime(sanitize_text_field($_REQUEST['booking_date'].' 00:00:00')) : null;
		}
	}
	public static function end_date()
	{
		$output = '';
		
		if(isset($_REQUEST['end_date']))
		{
			$output = ($_REQUEST['end_date']) ? strtotime(sanitize_text_field($_REQUEST['end_date'].' 00:00:00')) : null;	
		}
		
		return $output;
	}

	public static function get_multi_day_duration($strtotime_start, $strtotime_end)
	{
		// If no end date provided, minimum is 0
		if (!isset($strtotime_end) || is_null($strtotime_end)) {
			return 0;
		}

		// If start is missing or end is before start, also return minimum of 0
		if (is_null($strtotime_start) || $strtotime_end < $strtotime_start) {
			return 0;
		}

		// Calculate inclusive full days difference
		$secondsPerDay = 24 * 60 * 60;
		$dayDiff = floor(($strtotime_end - $strtotime_start) / $secondsPerDay) + 1;

		// Enforce minimum of 0
		return max(0, (int) $dayDiff);
	}


	public static function handsontable($args)
	{
		$output = '';
		
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
						<div id="<?php echo esc_attr($args['container']); ?>" class="hot" data-sensei-max="<?php echo esc_attr($args['max']); ?>" data-sensei-container="<?php echo esc_attr($args['container']); ?>" data-sensei-textarea="<?php echo esc_attr($args['textarea']); ?>" data-sensei-headers="<?php echo esc_attr(implode(',', $args['headers'])); ?>" data-sensei-type="<?php echo esc_attr(implode(',', $args['type'])); ?>" <?php echo $dropdown; ?> data-sensei-disabled="<?php echo esc_attr($disabled); ?>"></div>
					</div>
					<div class="hidden">
						<textarea cols="100" rows="20" name="<?php echo esc_attr($args['textarea']); ?>" id="<?php echo esc_attr($args['textarea']); ?>"><?php echo esc_textarea($args['value']); ?></textarea>
					</div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			}
		}
		
		return $output;
	}


	public static function min_range($the_id = null)
	{
		// get number of days offset (default to 0)
		$date_from = (int) package_field('package_booking_from', $the_id);

		// today at midnight as a base timestamp
		$base = strtotime('today 00:00');

		if($date_from === 0)
		{
			return $base;
		}

		$min_range = strtotime("+{$date_from} days", $base);
		//$min_range = strtotime("-1 day", $min_range);

		// return the final timestamp
		return $min_range;
	}

	public static function max_range($the_id = null)
	{
		$date_to = package_field('package_booking_to', $the_id);
		$date_to  = ($date_to) ? $date_to : 365;;
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
		$cache_key = $option.'_get_coupon';
		$output = '';

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}
		
		$option = strtolower($option);
		$coupons = dy_utilities::get_package_hot_chart('package_coupons');
		$output = 'option not selected';
		$coupon_code = strtolower(sanitize_text_field($_REQUEST['coupon_code']));
		$coupon_code = preg_replace("/[^A-Za-z0-9 ]/", '', $coupon_code);
		
		if(is_array($coupons))
		{
			if(array_key_exists('coupons', $coupons))
			{
				$coupons = $coupons['coupons'];
				
				for($x = 0; $x < count($coupons); $x++)
				{
					if(!empty($coupons[$x][0]))
					{
						if($coupon_code == preg_replace("/[^A-Za-z0-9 ]/", '', strtolower($coupons[$x][0])))
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
								$output = (is_valid_date($output)) ? $output : null;
							}
							else if($option == 'publish')
							{
								$output = (isset($coupons[$x][3])) ? $coupons[$x][3] : false;
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
							else if($option == 'bookings_after_expires')
							{
								$output = (isset($coupons[$x][6])) ? $coupons[$x][6] : false;
							}							
						}
					}
				}
			}				
		}
		
		if(!empty($output))
		{
			self::$cache[$cache_key] = $output;
		}
		
		return $output;
	}	


	public static function checkout_package_ID()
	{
		$cache_key = 'checkout_package_ID';
		$the_id = 0;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		if(isset($_POST['post_id']))
		{
			if(intval($_POST['post_id']) > 0)
			{
				$the_id = sanitize_text_field($_POST['post_id']);
			}
		}

		if(!isset($the_id))
		{
			$the_id = get_dy_id();
		}

		//store output in $cache
		self::$cache[$cache_key] = $the_id;

		return $the_id;
	}


	public static function total($regular = null)
	{
		$the_id = self::checkout_package_ID();
		$cache_key = 'dy_total_'.$regular.'_'.$the_id;
		$total = 0;
		
		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		if(is_booking_page() || is_checkout_page())
		{	
			$total = self::subtotal($regular, $the_id) + self::get_add_ons_total();
		}
		else
		{
			$total = self::starting_at();
		}
					
		//store output in $cache
		self::$cache[$cache_key] = $total;
		
		return $total;
	}

	public static function subtotal($regular = null, $the_id = 0)
	{
		$cache_key = 'dy_subtotal_'.$regular.'_'.$the_id;
		$subtotal = 0;
		
		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		$subtotal = 0;
		//sums regular price
		$subtotal += self::get_price_regular($regular, 'total');

		//sums discount price
		$discount = self::get_price_discount($regular, 'total');
		$subtotal += $discount;
		
		//store output in $cache
		self::$cache[$cache_key] = $subtotal;

		return $subtotal;
	}

	public static function duration_label($unit, $value)
	{
		//duration_label(unit number, duration value, $translate);
		
		$singular = array(__('Minute', 'dynamicpackages'), __('Hour', 'dynamicpackages'), __('Day', 'dynamicpackages'), __('Night', 'dynamicpackages'), __('Week', 'dynamicpackages'));
		$plural = array(__('Minutes', 'dynamicpackages'), __('Hours', 'dynamicpackages'), __('Days', 'dynamicpackages'), __('Nights', 'dynamicpackages'), __('Weeks', 'dynamicpackages'));
		
		$output = '';
		$label = $singular;
		
		if($value > 1)
		{
			$label = $plural;
		}
		
		
		return $label[$unit];
	}

	public static function show_duration($max = false)
	{
		$duration_label = '';
		$the_id = get_dy_id();
		$cache_key = 'dy_show_duration_'.$the_id.'_'.$max;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		$duration = intval(package_field('package_duration'));
		$duration_label = floatval(package_field('package_duration'));
		$duration_unit = intval(package_field('package_length_unit'));
		$duration_max = floatval(package_field('package_duration_max'));	
		
		if(!empty($duration))
		{
			$min_nights = self::get_min_nights();

			if(self::package_type_by_hour() || self::package_type_by_day() || $duration_unit === 2 || $duration_unit === 3)
			{
				if($min_nights)
				{
					$duration = $min_nights;
				}
			}
				
			if(!is_booking_page())
			{
				if($duration_max > $duration)
				{
					$duration_label = $duration;
					
					if($max === true)
					{
						$duration_label .= ' - '.$duration_max;
					}
				}			
			}
			else
			{
				$duration = $min_nights;
				$duration_label = $duration;
			}
			
			
			$duration_label_max = ($duration_max > $duration) ? $duration_max : $duration;
			$duration_label .= ' '.self::duration_label($duration_unit, $duration_label_max);
		}
		
		//store output in $cache
		self::$cache[$cache_key] = $duration_label;

		return $duration_label;
	}


	public static function starting_at_archive($the_id = null)
	{	
		if(!$the_id)
		{
			$the_id = get_dy_id();
		}
		
		$name = 'dy_start_archive';
		$cache_key = $name.'_'.$the_id;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}		

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
		
		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function starting_at($the_id = null, $parent_id = null)
	{		
		if(!$the_id)
		{
			$the_id = get_dy_id();
		}
		
		$output = 0;
		$name = 'dy_starting_at';
		$cache_key = $name.'_'.$the_id;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}
	
		$prices = array();
		$max = intval(package_field('package_max_persons', $the_id));
		$min = intval(package_field('package_min_persons', $the_id));
		$duration = floatval(package_field('package_duration'));
		$price_chart = self::get_price_chart($the_id);
		$occupancy_chart = self::get_occupancy_chart($the_id);	
		$occupancy_chart = (is_array($occupancy_chart)) 
			? (array_key_exists('occupancy_chart', $occupancy_chart)) 
			? $occupancy_chart['occupancy_chart'] 
			: null 
			: null;
		$price_type = ($parent_id) ? package_field('package_fixed_price', $parent_id) : package_field('package_fixed_price', $the_id);
		$duration_unit = intval(package_field('package_length_unit'));
		$duration_max = intval(package_field('package_duration_max'));
		$package_type = intval(package_field('package_package_type'));
				
		for($t = 0; $t < $max; $t++)
		{
			if($t >= ($min-1))
			{
				$base_price = 0;
				$occupancy_price = 0;
				
				if(is_array($price_chart))
				{
					if(isset($price_chart[$t][0]))
					{
						if(!empty($price_chart[$t][0]))
						{
							$base_price = floatval($price_chart[$t][0]);
						}
					}
				}
				if(is_array($occupancy_chart))
				{
					if(isset($occupancy_chart[$t][0]))
					{
						if(!empty($occupancy_chart[$t][0]))
						{
							
							$occupancy_price = floatval($occupancy_chart[$t][0]);
							
							if($duration_max === 0 && $package_type !== 1)
							{
								$occupancy_price = $occupancy_price * $duration;
							}
						}
					}
				}
				
				if($base_price > 0 && $occupancy_price > 0 && $duration > 1 && $package_type === 1)
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
		
		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}


	public static function get_price_chart($the_id = null)
	{
		if($the_id === null)
		{
			$the_id = get_dy_id();
		}
		
		$output = [];
		$cache_key = 'price_chart_'.$the_id;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}
		
		$price_chart = dy_utilities::get_package_hot_chart('package_price_chart', $the_id);
	
		if(is_array($price_chart))
		{
			if(array_key_exists('price_chart', $price_chart))
			{
				$output = $price_chart['price_chart'];
			}
		}

		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function package_type_by_hour()
	{
		$package_type = intval(package_field('package_package_type' ));
		$min_duration = intval(package_field('package_duration' ));
		$max_duration = intval(package_field('package_duration_max' ));	
		$length_unit = package_field('package_length_unit');
		
		
		if($package_type == 3 && $length_unit == 1 && $max_duration > $min_duration)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function package_type_by_day()
	{
		$package_type = intval(package_field('package_package_type' ));
		$min_duration = intval(package_field('package_duration' ));
		$max_duration = intval(package_field('package_duration_max' ));	
		$length_unit = package_field('package_length_unit');
		
		
		if($package_type == 2 && $length_unit == 2 && $max_duration > $min_duration)
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
		if($the_id === '')
		{
			$the_id = get_dy_id();
		}		
		
		$cache_key = 'occupancy_chart_'.$the_id;
		
		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		$chart = dy_utilities::get_package_hot_chart('package_occupancy_chart', $the_id);
		
		//store output in $cache
		self::$cache[$cache_key] = $chart;

		return $chart;
	}
	public static function get_season_chart()
	{
		//package_seasons_chart
		$output = [];
		$cache_key = 'seasons_chart_'.get_dy_id();

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		$output = dy_utilities::get_package_hot_chart('package_seasons_chart');	
		
		
		//store output in $cache
		self::$cache[$cache_key] = $output;
		
		return $output;
	}	

	public static function get_date_range($from, $to, $add_extra = true)
	{
		$output = array();

		if(!empty($from) && !empty($to))
		{
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
		}
		
		return $output;
	}

	public static function get_disabled_range()
	{
		$output = array();
		$disabled = dy_utilities::get_package_hot_chart('package_disabled_dates');
		
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
			$seasons = dy_utilities::get_package_hot_chart('package_seasons_chart');
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
						$from_season = (!empty($seasons[$x][1])) ? strtotime($seasons[$x][1]) : 0;
						$to_season = (!empty($seasons[$x][2])) ? strtotime($seasons[$x][2]) : 0;
				
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
	
	public static function get_range_week_day_surcharges($days) {
		
		$output = array();
		$surcharges = self::get_week_day_surcharges();

		if(is_array($days))
		{
			$count_days = count($days);
			
			for($x = 0; $x < $count_days; $x++)
			{
				$week_day = intval(date('w', strtotime($days[$x])));
				$week_day = ($week_day === 0) ?  6 : $week_day - 1;
				array_push($output, $surcharges[$week_day]);
			}
		}

		return $output;
	}	

	public static function get_price_occupancy($type = null)
	{
		if(isset($_REQUEST['booking_date']))
		{
			$sum = 0;

			
			$occupancy_chart = dy_utilities::get_package_hot_chart('package_occupancy_chart');
			$duration = self::get_min_nights();
			$seasons = dy_utilities::get_package_hot_chart('package_seasons_chart');
			$booking_date = sanitize_text_field($_REQUEST['booking_date']);
			$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
			$booking_dates_range = self::get_date_range($booking_date, $booking_date_to, false);
			$booking_dates_surcharges = self::get_range_week_day_surcharges($booking_dates_range);
			$seasons_array = array();

			if(is_array($occupancy_chart) && is_array($seasons) && is_array($occupancy_chart) && is_array($booking_dates_range))
			{
				if($duration == count($booking_dates_range))
				{
					for($d = 0; $d < $duration; $d++)
					{
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
						if(array_key_exists($s, $seasons_array) && !empty($occupancy_chart))
						{
							if(array_key_exists($seasons_array[$s], $occupancy_chart))
							{
								$occupancy_surcharge = floatval($booking_dates_surcharges[$s]);
								$occupancy_surcharge_percent = ($occupancy_surcharge > 0) ? ($occupancy_surcharge + 100) / 100 : 1;
								$price_row = $occupancy_chart[$seasons_array[$s]];
								
								if(is_array($price_row))
								{
									$count_price_row = count($price_row);
									
									for($a = 0;  $a < $count_price_row; $a++)
									{
										if(floatval(sanitize_text_field($_REQUEST['pax_regular'])) == ($a+1))
										{	
											if(!empty($price_row[$a][0]))
											{
												$price_col = 0;
												
												//total occupancy price
												if($type == 'regular')
												{
													$price_col = floatval($price_row[$a][0]) * $occupancy_surcharge_percent;
													$sum = $sum + $price_col;
												}
												
												//total children discounts
												if(isset($_REQUEST['pax_discount']) && $type == 'discount')
												{
													if($_REQUEST['pax_discount'] > 0 && !empty($price_row[$a][1]))
													{
														$price_col = floatval($price_row[$a][1]) * $occupancy_surcharge_percent;
														$sum = $sum + $price_col;
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
			$package_type = intval(package_field('package_package_type'));

			if(is_array($price_chart))
			{
				for ($x = 0; $x < count($price_chart); $x++)
				{
					if($pax_regular == ($x+1))
					{
						if(!empty($price_chart[$x][0]))
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
		
		if((is_booking_page() || is_checkout_page()) && isset($_REQUEST['pax_discount']))
		{

			$pax_discount = intval(sanitize_text_field($_REQUEST['pax_discount']));

			if($pax_discount > 0)
			{
				$base_price = 0;
				$price_chart = self::get_price_chart();
				$pax_discount = (isset($_REQUEST['pax_discount'])) ? floatval(sanitize_text_field($_REQUEST['pax_discount'])) : 0;
				$package_type = intval(package_field('package_package_type'));

				if(is_array($price_chart))
				{
					for($x = 0; $x < count($price_chart); $x++)
					{
							if($pax_discount == floatval(($x+1)))
							{
								$base_price = 0;
								
								if(!empty($price_chart[$x][1]))
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
		}
		
		return $sum;
	}
	
	public static function get_price_calc($sum, $regular, $type)
	{

		$cache_key ='get_price_calc_'.$sum.'_'.$regular.'_'.$type.'_'.get_dy_id();

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$package_type = intval(package_field('package_package_type'));
		$occupancy_price = (dy_validators::package_type_multi_day()) ? self::get_price_occupancy($type) : 0;
		$sum = $sum + $occupancy_price;
		$booking_date = sanitize_text_field($_REQUEST['booking_date']);
		$week_days_to_surcharge = array($booking_date);
		$one_way_surcharge = intval(package_field('package_one_way_surcharge'));

		if(dy_validators::package_type_transport())
		{
			$sum_arr = [$sum];

			$end_date = (isset($_REQUEST['end_date'])) ? sanitize_text_field($_REQUEST['end_date']) : '';

			if(is_valid_date($booking_date))
			{
				if(is_valid_date($end_date))
				{
					$sum_arr[] = $sum;
					$week_days_to_surcharge[] = $end_date;
				}

				$surcharges_arr = self::get_range_week_day_surcharges($week_days_to_surcharge);

				if(is_array($surcharges_arr))
				{
					if(count($surcharges_arr) > 0)
					{
						for($x = 0; $x < count($surcharges_arr); $x++)
						{
							$surcharges = (floatval($surcharges_arr[$x]) > 0) ? floatval($surcharges_arr[$x]) : 0;
							$surcharge_percentage = ($surcharges > 0) ? (($surcharges_arr[$x]/100) * $sum_arr[$x]) : 0;
							$sum_arr[$x] =  $sum_arr[$x] + $surcharge_percentage;
						}

						$sum = array_sum($sum_arr);
					}
				}

				if(!is_valid_date($end_date) && $one_way_surcharge > 0)
				{
					$one_way_surcharge = ($one_way_surcharge / 100) * $sum;
					$sum += $one_way_surcharge;
				}
			}
		}
		else
		{
			if((self::package_type_by_hour() || self::package_type_by_day()) && isset($_REQUEST['booking_extra']))
			{
				$sum = $sum * intval(sanitize_text_field($_REQUEST['booking_extra']));
			}

			if(!dy_validators::package_type_multi_day())
			{
				$surcharges_arr = self::get_range_week_day_surcharges($week_days_to_surcharge);

				if(is_array($surcharges_arr))
				{
					if(count($surcharges_arr) === 1)
					{
						$surcharge = floatval($surcharges_arr[0]);
						$surcharge_percentage = ($surcharge > 0) ? (($surcharge/100) * $sum) : 0;
						$sum = $sum + $surcharge_percentage;
					}
				}
			}
		}
		
		if(dy_validators::validate_coupon() && $regular === null)
		{
			$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
		}

        //store output in $cache
        self::$cache[$cache_key] = $sum;


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
			if(package_field('package_payment' ) == 1)
			{
				$deposit = floatval(package_field('package_deposit'));

				if($deposit > 0)
				{
					$output = $deposit;
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
			$deposit = floatval(package_field('package_deposit'));
			
			if(package_field('package_payment' ) == 1 && $deposit > 0)
			{
				$output = 'deposit';
			}
			
			$GLOBALS['dy_payment_type'] = $dy_payment_type;
		}
		return $output;
	}

	public static function get_week_days_list()
	{
		$output = array();
		$days = self::get_week_days_abbr();
		
		for($x = 0; $x < count($days); $x++)
		{
			if(intval(package_field('package_day_'.$days[$x] )) === 1)
			{
				array_push($output, $x+1);
			}
		}
		return $output;
	}
	
	public static function hour()
	{
		$output = '';
		$field = package_field('package_start_hour' );

		if($field)
		{
			if(!empty($field))
			{
				$output = $field;
			}
		}
		
		if(isset($_REQUEST['booking_hour']))
		{
			$output = sanitize_text_field($_REQUEST['booking_hour']);
		}
		
		return $output;
	}	
	
	public static function return_hour()
	{
		$output = '';
		$field = package_field('package_return_hour' );

		if(!empty($field))
		{
			$output = $field;
		}
		
		if(isset($_REQUEST['return_hour']))
		{
			if(!empty($_REQUEST['return_hour']))
			{
				$output = sanitize_text_field($_REQUEST['return_hour']);
			}
			
		}
		
		return $output;
	}


	public static function webhook($option, $data)
	{
		$webhook = get_option($option);
		
		if($webhook)
		{
			if(!filter_var($webhook, FILTER_VALIDATE_URL) === false)
			{
				$ch = curl_init();

				$endpoint = str_replace('&#038;', '&', $webhook);
				curl_setopt($ch, CURLOPT_URL, $endpoint);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch,CURLOPT_TIMEOUT, 20);
				$result = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
	
				if ($httpCode !== 200) {
					write_log($result);
				} else {

					write_log($result);
				}
			}
		}

		return true;

	}

	public static function get_taxonomies($term_name)
	{
		global $post;
		$terms_conditions = array();

		if(!isset($post))
		{
			return $terms_conditions;
		}

		$cache_key = 'dy_get_taxonomies_'.$term_name.'_'.$post->ID;

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }


		if(isset($post))
		{
			$the_id = $post->ID;
			
			if(property_exists($post, 'post_parent'))
			{
				$the_id = $post->post_parent;
			}		
			
			$terms = get_the_terms($the_id, $term_name);

			
			if($terms)
			{
				for($x = 0; $x < count($terms); $x++)
				{
					array_push($terms_conditions, $terms[$x]);
				}			
			}		
		}

        //store output in $cache
        self::$cache[$cache_key] = $terms_conditions;

		return $terms_conditions;
	}

	public static function get_taxo_names($tax)
	{
		global $post;

		if(isset($post))
		{
			$termid = $post->ID;
			$output = array();
			
			if(property_exists($post, 'post_parent') && !has_term('', $tax, $termid))
			{
				$termid = $post->post_parent;
			}
			
			$terms = get_the_terms($termid, $tax);	

			if($terms)
			{					
				for($x = 0; $x < count($terms); $x++)
				{
					array_push($output, $terms[$x]->name);
				}
			}	
		}
	

		return $output;
	}
	
	public static function implode_taxo_names($tax)
	{
		$output = '';
		$names = self::get_taxo_names($tax);

		if(count($names) > 0)
		{
			$output = implode(', ', $names);
		}

		return $output;
	}
	
	public static function get_add_ons_total() {
		$total = 0;
		$pax_num = self::pax_num();

		if(apply_filters('dy_has_add_ons', null) && isset($_POST['add_ons']))
		{
			$add_ons = apply_filters('dy_get_add_ons', null);
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
				
				$total = $total + $add_ons_price;			
			}			
		}
		
		return $total;
	}
	
	public static function payment_amount($service_fee = 0)
	{
		$the_id = self::checkout_package_ID();
		$total = floatval(self::subtotal(null, $the_id));
		
		if(dy_validators::has_deposit())
		{
			$deposit = floatval(self::get_deposit());
			$total = $total*($deposit*0.01);			
		}
		
		$total = $total + self::get_add_ons_total();

		$service_fee = floatval($service_fee);

		if($service_fee > 0)
		{
			$total = $total * (1 + ($service_fee / 100));
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
	
	public static function get_week_days_abbr()
	{
		return array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
	}

	public static function get_week_day_names_long()
	{
		return array(
			
			__('Monday', 'dynamicpackages'), 
			__('Tuesday', 'dynamicpackages'), 
			__('Wednesday', 'dynamicpackages'), 
			__('Thursday', 'dynamicpackages'), 
			__('Friday', 'dynamicpackages'), 
			__('Saturday', 'dynamicpackages'),
			__('Sunday', 'dynamicpackages'),
		);
	}

	public static function get_week_day_names_short()
	{
		return array(
			__('Mon', 'dynamicpackages'),
			 __('Tue', 'dynamicpackages'), 
			 __('Wed', 'dynamicpackages'),
			 __('Thu', 'dynamicpackages'), 
			 __('Fri', 'dynamicpackages'), 
			 __('Sat', 'dynamicpackages'), 
			 __('Sun', 'dynamicpackages')
		);
	}
	
	
	public static function get_week_day_surcharges()
	{
		$days = self::get_week_days_abbr();
		
		return array_map(function($day){
			return intval(package_field('package_week_day_surcharge_' . $day));
		}, $days);
	}

	public static function event_date_update($the_id)
	{
		$output = '';
		global $polylang;
		global $post;
		
		if(isset($polylang))
		{
			if(pll_current_language($post->post_name) != pll_default_language())
			{
				$the_id = pll_get_post(get_dy_id(), pll_default_language());
			}
		}
		
		if(!empty(package_field('package_event_date')))
		{
			$output = package_field('package_event_date');
		}
		else
		{
			$today = strtotime('today');
			$last_day = strtotime("+365 days", $today);
			$from = package_field('package_booking_from');
			$to = package_field('package_booking_to');
			$week_days = self::get_week_days_list();
			
			if(intval($from) > 0)
			{
				$today = strtotime("+ {$from} days", $today);
			}
			if(intval($to) > 0)
			{
				$last_day = strtotime("+ {$to} days", $today);
			}
			
			$today = date('Y-m-d', $today);
			$last_day = date('Y-m-d', $last_day);
			
			$new_range = array();
			$range = self::get_date_range($today, $last_day);
			$disabled_range = self::get_disabled_range();
			
			for($x = 0; $x < count($range); $x++)
			{
				if(!in_array($range[$x], $disabled_range))
				{
					$day = date('N', strtotime($range[$x]));
					
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
					$output = $new_range[0];
				}
			}
			
			if(!empty($output))
			{
				update_post_meta($the_id, 'package_date', $output);
			}	
		}
		
		return $output;
	}


	public static function get_tax_list($term_name = '', $label = '', $is_link = true, $icon_class = null)
	{
		$output = '';
		$is_link_str = ($is_link) ? 1 : 0;
		$icon_class_str = (!empty($icon_class)) ? 1 : 0;
		$cache_key = 'dy_get_tax_list_'.$term_name.'_'.strlen($label).'_'.$is_link_str.'_'.$icon_class_str;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}


		$terms_array = array();

		if(in_the_loop())
		{
			global $post;

			$parent_terms = array();
			$current_terms = get_the_terms($post->ID, $term_name);
			$current_terms = (is_array($current_terms)) ? $current_terms : array();

			if(property_exists($post, 'post_parent'))
			{
				$parent_terms = get_the_terms($post->post_parent, $term_name, array('depth' => 0));
				$parent_terms = (is_array($parent_terms)) ? $parent_terms : array();
			}
			
			$terms = array_unique(array_merge($current_terms, $parent_terms), SORT_REGULAR );
		
		}
		else
		{
			$terms = get_terms(array('taxonomy' => $term_name));
		}

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) )
		{
			foreach ( $terms as $t )
			{
				$url = get_term_link($t);
				$title_modifier = get_term_meta($t->term_id, 'tax_title_modifier', true);

				$title = (strlen($title_modifier) > strlen($t->name)) ? $title_modifier : $t->name;

				$item = ($is_link) 
					? '<a href="'.esc_url($url).'" title="'.esc_attr($title).'" >'.esc_html($t->name).'</a>' 
					: esc_html($t->name);
					
				array_push($terms_array, $item);
			}
		}
		
		
		if(count($terms_array) > 0)
		{
			if($label)
			{
				$output .= '<p class="strong">'.esc_html($label).'</p>';
			}
			
			$icon = ($icon_class) ? '<span class="'.esc_attr($icon_class).'" ></span>' : '';
			$output .= '<ul class="dy-list-'.esc_attr($term_name).' bottom-20 dy-list"><li>'.$icon.' ';
			$output .= implode('</li><li>'.$icon.' ', $terms_array);
			$output .= '</li></ul>';
		}	
		
		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function get_option_hot_chart($key_name) {


		$cache_key = 'dy_get_option_hot_chart' . $key_name;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		// Retrieve and decode the JSON data
		$raw_data = get_option($key_name);

		// Check if $raw_data is empty or not a string
		if (empty($raw_data) || !is_string($raw_data)) {
			return [];
		}

		// Decode the JSON data
		$output = json_decode(html_entity_decode($raw_data), true);

		// Validate the JSON decoding
		if (json_last_error() !== JSON_ERROR_NONE) {
			return [];
		}

		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}


	public static function get_package_hot_chart($key_name, $the_id = null) {

		if(!$the_id)
		{
			$the_id = get_dy_id();
		}

		$cache_key = 'dy_get_package_hot_chart' . $key_name . '_' . $the_id;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		// Retrieve and decode the JSON data
		$raw_data = (empty($the_id)) ? package_field($key_name) : package_field($key_name, $the_id);

		// Check if $raw_data is empty or not a string
		if (empty($raw_data) || !is_string($raw_data)) {
			return [];
		}

		// Decode the JSON data
		$output = json_decode(html_entity_decode($raw_data), true);

		// Validate the JSON decoding
		if (json_last_error() !== JSON_ERROR_NONE) {
			return [];
		}

		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}
}