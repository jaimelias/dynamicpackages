<?php

if ( !defined( 'WPINC' ) ) exit;


#[AllowDynamicProperties]
class dy_utilities {

	private static $cache = [];


	public static function get_package_type($the_id = null)
	{

		$cache_key = $the_id.'_get_package_type';
		$output = '';

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$all_types = [
			'0' => 'one-day',
			'1' => 'multi-day',
			'2' => 'rental-per-day',
			'3' => 'rental-per-hour',
			'4' => 'transport',
		];

		if($the_id !== null)
		{
			$post = get_post($the_id);
		} else {
			
			global $post;

			if(property_exists($post, 'post_parent') && $post->post_parent > 0)
			{
				$the_id = $post->post_parent;
			}
		}

		$type = (string) package_field('package_package_type', $the_id);

		if(!array_key_exists($type, $all_types)) {
			return '';
		}

		$output = $all_types[$type];

		self::$cache[$cache_key] = $output;

		return $output;
		
	}

	public static function sort_by_arr()
	{
		return array('new', 'low', 'high', 'today', 'tomorrow', 'week', 'month');
	}

	public static function booking_date()
	{
		$cache_key = 'dy_booking_date';

		if( array_key_exists($cache_key, self::$cache) ) {
			return self::$cache[$cache_key];
		}

		$booking_date = secure_request('booking_date');

		return self::$cache[$cache_key] = is_valid_date($booking_date) 
			? strtotime($booking_date.' 00:00:00') 
			: null;
	}

	public static function end_date()
	{
		$cache_key = 'dy_end_date';

		if( array_key_exists($cache_key, self::$cache) ) {
			return self::$cache[$cache_key];
		}

		$end_date = secure_request('end_date');
		
		return self::$cache[$cache_key] = is_valid_date($end_date)
			? strtotime($end_date.' 00:00:00')
			: null;
	}

	public static function get_multi_day_duration($strtotime_start, $strtotime_end)
	{
		// No end date or end < start → 0 days
		if (is_null($strtotime_end) || is_null($strtotime_start) || $strtotime_end < $strtotime_start) {
			return 0;
		}

		$secondsPerDay = 24 * 60 * 60;
		// Exclusive difference in full days
		$dayDiff = floor(($strtotime_end - $strtotime_start) / $secondsPerDay);

		return (int) max(0, $dayDiff);
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
		return secure_request('pax_regular', 0, 'absint')
			+ secure_request('pax_discount', 0, 'absint')
			+ secure_request('pax_free', 0, 'absint');
	}

	public static function normalize_coupon_code($value) {
		if (!is_string($value)) {
			return null;
		}

		$value = preg_replace(
			'/[^A-Za-z0-9 ]/',
			'',
			strtolower($value)
		);

		if (!is_string($value)) {
			return null;
		}

		$value = trim($value);

		return preg_match('/[a-z0-9]/', $value) === 1
			? $value
			: null;
	}
	

	/**
	 * Return sanitized parameters for the submitted coupon.
	 *
	 * @return object|null Valid coupon parameters, or null when $coupons_data is malformed.
	 *                     
	 */

	public static function get_active_coupon_params()
	{
		$cache_key = 'dy_get_coupon';

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$coupons_data = self::get_package_hot_chart('package_coupons');

		if (!is_array($coupons_data) || !array_key_exists('coupons', $coupons_data) || !is_array($coupons_data['coupons'])) {
			return self::$cache[$cache_key] = null;
		}

		$coupons = $coupons_data['coupons'];

		if (count($coupons) === 0) {
			return self::$cache[$cache_key] = null;
		}

		$coupon_code = self::normalize_coupon_code(secure_request('coupon_code'));

		if ($coupon_code === null) {
			return self::$cache[$cache_key] = null;
		}

		$output = (object) [
			'code' => null,
			'discount' => 0,
			'expiration' => null,
			'publish' => false,
			'min_duration' => 0,
			'max_duration' => 0,
			'bookings_after_expires' => false,
		];

		$has_errors = false;

		foreach ($coupons as $coupon) {

			if (!is_array($coupon)) {
				continue;
			}

			$raw_code = $coupon[0] ?? null;

			if (!is_string($raw_code) || trim($raw_code) === '') {
				continue;
			}

			$stored_code = self::normalize_coupon_code($raw_code);

			if ($stored_code === null || $coupon_code !== $stored_code) {
				continue;
			}

			$output->code = $stored_code;

			$validate_discount = function ($value) {
				$discount_raw = $value;
				$discount = (float) $discount_raw;
				
				return is_numeric($discount_raw) && is_finite($discount) && $discount > 0.0 && $discount <= 100;
			};

			$output->discount = isset($coupon[1]) && $validate_discount($coupon[1]) ? (float) $coupon[1] : 0.0;

			if($output->discount === 0.0) {
				$has_errors = true;
				break;
			}

			$output->expiration = isset($coupon[2]) && is_valid_date($coupon[2]) ? $coupon[2] : '';
			$output->publish = isset($coupon[3]) ? (bool) $coupon[3] : false;
			$output->min_duration = isset($coupon[4]) && is_numeric($coupon[4]) ? absint($coupon[4]) : 0;
			$output->max_duration = isset($coupon[5]) && is_numeric($coupon[5]) ? absint($coupon[5]) : 0;
			$output->bookings_after_expires = isset($coupon[6]) ? (bool) $coupon[6] : false;

			break;
		}

		if($has_errors) {
			return self::$cache[$cache_key] = null;
		}

		return self::$cache[$cache_key] = $output;
	}

	public static function total($regular = null)
	{
		$the_id = get_dy_id();
		$cache_key = 'dy_total_'.$regular.'_'.$the_id;
		$total = 0;
		
		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(is_booking_page() || is_confirmation_page())
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
		
		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$subtotal = 0;
		//sums regular price
		$subtotal += self::get_price_regular($regular, 'total');

		//sums discount price
		$discount = self::get_price_discount($regular, 'total');

		$pax_discount = secure_request('pax_discount', 0, 'absint');

		if ($pax_discount > 0) {
			$subtotal = $discount > 0
				? $subtotal + $discount
				: 0;
		}
		
		//store output in $cache
		self::$cache[$cache_key] = $subtotal;

		return $subtotal;
	}

	public static function duration_label($unit, $value)
	{
		//duration_label(unit number, duration value, $translate);
		
		$singular = array(__('minute', 'dynamicpackages'), __('hour', 'dynamicpackages'), __('day', 'dynamicpackages'), __('night', 'dynamicpackages'), __('week', 'dynamicpackages'));
		$plural = array(__('minutes', 'dynamicpackages'), __('hours', 'dynamicpackages'), __('days', 'dynamicpackages'), __('nights', 'dynamicpackages'), __('weeks', 'dynamicpackages'));
		
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

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$duration = (float) package_field('package_duration');
		$duration_label = (string) $duration;
		$duration_unit = (int) package_field('package_length_unit');
		$duration_max = (float) package_field('package_duration_max');
		$package_type = self::get_package_type($the_id);
		
		if(!empty($duration))
		{
			$min_nights = self::get_min_nights();

			if($package_type === 'rental-per-hour' || $package_type === 'rental-per-day' || $duration_unit === 2 || $duration_unit === 3)
			{
				if($min_nights)
				{
					$duration = $min_nights;
				}
			}
				
			if(!is_booking_page() && !is_confirmation_page())
			{
				if($duration_max > $duration)
				{
					$duration_label = (string) $duration;
					
					if($max === true)
					{
						$duration_label .= ' - '.$duration_max;
					}
				}			
			}
			else
			{
				$duration = $min_nights;
				$duration_label = (string) $duration;
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

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}		

		$output = self::starting_at($the_id);
		
		if(dy_validators::has_children() && (in_the_loop() || is_singular('packages')))
		{
			$prices = [];
			$children = dy_validators::has_children();
	
			foreach ( $children as $child )
			{
				$child_starting_at = (float) self::starting_at($child->ID);

				if($child_starting_at > 0) {
					$prices[] = $child_starting_at;
				}
				
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

	public static function starting_at($the_id = null)
	{		
		if(!$the_id)
		{
			$the_id = get_dy_id();
		}

		$output = 0;
		$cache_key = 'dy_starting_at_'. $the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(!dy_validators::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = 0;
		}

		$post = get_post($the_id);

		if(!$post instanceof WP_Post || $post->post_status === 'trash')
		{
			return self::$cache[$cache_key] = 0;
		}

		$is_child = $post->post_parent > 0;
	
		$prices = [];
		$max = (int) package_field('package_max_persons', $the_id);
		$min = (int) package_field('package_min_persons', $the_id);

		if($min <= 0 || $max <= 0 || $max < $min || $max > 500) {
			return self::$cache[$cache_key] = 0;
		}
		
		$price_chart = self::get_price_chart($the_id);
		$occupancy_chart = self::get_occupancy_chart($the_id);

		$occupancy_chart = (is_array($occupancy_chart)) 
			? (array_key_exists('occupancy_chart', $occupancy_chart)) 
			? $occupancy_chart['occupancy_chart'] 
			: null 
			: null; // can be null

		if(!is_array($price_chart) || count($price_chart) < $min)
		{
			return self::$cache[$cache_key] = 0;
		}

		//configuration vars
		$price_type = $is_child 
			? intval(package_field('package_fixed_price', $post->post_parent)) 
			: intval(package_field('package_fixed_price', $the_id));

		$duration = $is_child 
			? floatval(package_field('package_duration', $post->post_parent)) 
			: floatval(package_field('package_duration', $the_id));

		if($duration <= 0) {
			return self::$cache[$cache_key] = 0;
		}
			
		$duration_max = $is_child 
			? intval(package_field('package_duration_max', $post->post_parent))
			: intval(package_field('package_duration_max', $the_id));
			
		$package_type = $is_child 
			? self::get_package_type($post->post_parent) 
			: self::get_package_type($the_id);
				
		for($t = 0; $t < $max; $t++)
		{
			$pax_row_number = $t + 1;

			if($pax_row_number < $min || $pax_row_number > $max) continue;

			$base_price = 0;
			$occupancy_price = 0;
			
			if(is_array($price_chart))
			{
				if(isset($price_chart[$t][0]))
				{
					if(!empty($price_chart[$t][0]))
					{
						$base_price = (float) $price_chart[$t][0];
					}
				}
			}
			if(is_array($occupancy_chart))
			{
				if(isset($occupancy_chart[$t][0]))
				{
					if(!empty($occupancy_chart[$t][0]))
					{
						
						$occupancy_price = (float) $occupancy_chart[$t][0];
						
						if($duration_max === 0 && $package_type !== 'multi-day')
						{
							$occupancy_price = $occupancy_price * $duration;
						}
					}
				}
			}
			
			if($base_price > 0 && $occupancy_price > 0 && $duration > 1 && $package_type === 'multi-day')
			{
				$price = ($base_price + ($occupancy_price * $duration)) / $duration;
			}
			else
			{
				$price = $base_price + $occupancy_price;
			}

			if($price_type === 1)
			{
				$price = $price * $pax_row_number;
			}

			$prices[] = $price;	
	}
						
		if(is_array($prices))
		{
			if(count($prices) > 0)
			{
				$output = min($prices);
			}
		}

		return self::$cache[$cache_key] = $output;
	}


	public static function get_price_chart($the_id = null)
	{
		if($the_id === null)
		{
			$the_id = get_dy_id();
		}
		
		$output = [];
		$cache_key = 'price_chart_'.$the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}
		
		$price_chart = self::get_package_hot_chart('package_price_chart', $the_id);
	
		if (
			is_array($price_chart)
			&& array_key_exists('price_chart', $price_chart)
			&& is_array($price_chart['price_chart'])
		) {
			$output = $price_chart['price_chart'];
		}

		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}
	
	public static function get_occupancy_chart($the_id = '')
	{
		if($the_id === '')
		{
			$the_id = get_dy_id();
		}		
		
		$cache_key = 'occupancy_chart_'.$the_id;
		
		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$chart = self::get_package_hot_chart('package_occupancy_chart', $the_id);
		
		//store output in $cache
		self::$cache[$cache_key] = $chart;

		return $chart;
	}
	public static function get_season_chart()
	{
		//package_seasons_chart
		$output = [];
		$cache_key = 'seasons_chart_'.get_dy_id();

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$output = self::get_package_hot_chart('package_seasons_chart');	
		
		
		//store output in $cache
		self::$cache[$cache_key] = $output;
		
		return $output;
	}	

	public static function get_date_range($from, $to, $add_extra = true)
	{
		$output = [];

		if(is_valid_date($from) && is_valid_date($to))
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
				$output[] = $value->format('Y-m-d');
			}
		}
		
		return $output;
	}

	public static function get_disabled_range()
	{
		$output = [];
		$disabled = self::get_package_hot_chart('package_disabled_dates');
		
		if(is_array($disabled))
		{
			if(array_key_exists('disabled_dates', $disabled))
			{		
				$disabled_dates = $disabled['disabled_dates'];
						
				for($x = 0; $x < count($disabled_dates); $x++)
				{
					$from = $disabled_dates[$x][0];
					$to = $disabled_dates[$x][1];
					$output[] = self::get_date_range($from, $to);
				}
			}			
		}
		
		return self::arrayFlatten($output);
	}	

	static function arrayFlatten($array) { 
		$output = [];
		
		for($x = 0; $x < count($array); $x++)
		{
			for($y = 0; $y < count($array[$x]); $y++)
			{
				$output[] =  $array[$x][$y];
			}
		}
		return array_unique($output);
	}	


	
	public static function get_min_nights()
	{
		//this will be used only for multi-day packages only

		$the_id = get_dy_id();
		$cache_key = 'dy_get_min_nights _' . $the_id;

		if(array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(!is_booking_page() && !is_confirmation_page()) {
			return null;
		}

		$duration = max(1, absint(package_field('package_duration')));
		$package_type = self::get_package_type($the_id);

		$has_max_duration = in_array(
			$package_type,
			['multi-day', 'rental-per-day', 'rental-per-hour'],
			true
		);

		$duration_max = $has_max_duration
			? absint(package_field('package_duration_max'))
			: 0;

		$booking_extra = secure_request('booking_extra', $duration, 'absint');

		if( $booking_extra > $duration && $duration_max > $duration)
		{
			$duration = min($booking_extra, $duration_max);
		}

		$booking_date = secure_request('booking_date');
		$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
		$booking_dates_range = self::get_date_range($booking_date, $booking_date_to, false); //clientes from and to array of dates e.g. ["2026-12-03"] or ["2026-12-03", "2026-12-04"]
		$seasons = self::get_package_hot_chart('package_seasons_chart');
		$duration_arr = [];

		
		if(is_array($seasons))
		{
			if(array_key_exists('seasons_chart', $seasons))
			{
				$seasons = $seasons['seasons_chart'];

				if(!is_array($seasons) || count($seasons) === 0) {
					return $duration;
				}
				
				for($s = 0; $s < count($seasons); $s++)
				{
					if(
						!is_array($seasons[$s]) ||
						!isset($seasons[$s][1]) ||
						!isset($seasons[$s][2]) ||
						!isset($seasons[$s][3])
					) continue;

					$from_season = $seasons[$s][1];
					$to_season = $seasons[$s][2];

					if(!is_valid_date($from_season) || !is_valid_date($to_season)) {
						continue;
					}

					$duration_season = (int) $seasons[$s][3];

					if($duration_season < $duration) {
						continue;
					}

					$seasons_dates_range = self::get_date_range($from_season, $to_season, false);
					
					if(!is_array($seasons_dates_range) || count($seasons_dates_range) === 0)
					{
						continue;
					}

					foreach($booking_dates_range as $date)
					{
						if(in_array($date, $seasons_dates_range))
						{
							$duration_arr[] = $duration_season;
							break;
						}
					}
				}
			}
		}
		
		//seasonal duration must override max_duration and booking_extra
		if(count($duration_arr) > 0)
		{
			$max_duration = max($duration_arr);
			
			if($max_duration > $duration)
			{
				$duration = $max_duration;
			}					
		}
		
		return self::$cache[$cache_key] = $duration;
	}


	public static function get_season($booking_date)
	{
		if(is_booking_page() || is_confirmation_page())
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
		
		$output = [];
		$surcharges = self::get_week_day_surcharges();

		if(is_array($days))
		{
			$count_days = count($days);
			
			for($x = 0; $x < $count_days; $x++)
			{
				$week_day = intval(date('w', strtotime($days[$x])));
				$week_day = ($week_day === 0) ?  6 : $week_day - 1;
				$output[] = $surcharges[$week_day];
			}
		}

		return $output;
	}	

	public static function get_price_occupancy($type = null)
	{
		if (!request_has('booking_date')) {
			// Preserve original behavior: no return if booking_date isn't present.
			return;
		}

		$sum = 0.0;

		// Fetch once
		$occupancy_chart = self::get_package_hot_chart('package_occupancy_chart'); // base occupancy rates
		$duration        = self::get_min_nights() ?? 1;                             // min nights required
		$seasons         = self::get_package_hot_chart('package_seasons_chart');    // seasons matrix (not used directly but kept)
		$booking_date    = secure_request('booking_date');          // selected date
		$booking_date_to = date('Y-m-d', strtotime($booking_date . " +{$duration} days"));

		// Precompute ranges/surcharges once
		$booking_dates_range     = self::get_date_range($booking_date, $booking_date_to, false);
		$booking_dates_surcharges = self::get_range_week_day_surcharges($booking_dates_range);

		// Validate arrays (mirror original intent but remove duplicates)
		if (!is_array($occupancy_chart) || !is_array($seasons) || !is_array($booking_dates_range)) {
			return $sum;
		}

		// Must match exact number of days
		if ($duration != count($booking_dates_range)) {
			return $sum;
		}

		// Build occupancy keys for each day (season -> chart key)
		$seasons_array = [];
		for ($d = 0; $d < $duration; $d++) {
			$season = self::get_season($booking_dates_range[$d]);
			$seasons_array[] = ($season === 'price_chart') ? 'occupancy_chart' : ('occupancy_chart' . $season);
		}

		$pax_regular_param  = secure_request('pax_regular', 0, 'absint');
		$pax_discount_param = secure_request('pax_discount', 0, 'absint');

		// Iterate seasons; keep indices to align with surcharges
		$should_break = false;
		$count_seasons = count($seasons_array);
		for ($s = 0; $s < $count_seasons; $s++) {

			if($should_break) {
				$sum = 0.0;
				break;
			}

			// Guard: occupancy chart must have the computed key
			$key = $seasons_array[$s];
			if (empty($occupancy_chart) || !array_key_exists($key, $occupancy_chart)) {
				continue;
			}

			// Surcharge factor
			$occupancy_surcharge         = (float) ($booking_dates_surcharges[$s] ?? 0);
			$occupancy_surcharge_percent = ($occupancy_surcharge > 0) ? ($occupancy_surcharge + 100) / 100 : 1.0;

			$price_row = $occupancy_chart[$key];
			if (!is_array($price_row)) {
				continue;
			}

			// Scan the row to the column matching pax count (index a = pax-1)
			$count_price_row = count($price_row);
			for ($a = 0; $a < $count_price_row; $a++) {
				if ($pax_regular_param !== ($a + 1)) {
					continue;
				}

				// Column values
				$pax_regular_price  = isset($price_row[$a][0]) ? (float) $price_row[$a][0] : 0.0;
				$pax_discount_price = isset($price_row[$a][1]) ? (float) $price_row[$a][1] : 0.0;

				// If base price is 0 => zero out sum and break inner loop (preserve behavior)
				if ($pax_regular_price == 0) {
					$sum = 0.0;
					$should_break = true;
					break;
				}

				// Regular total
				if ($type === 'regular') {
					$sum += $pax_regular_price * $occupancy_surcharge_percent;
				}

				if ($pax_discount_param > 0 && $type === 'discount') {
					if ($pax_discount_price == 0) {
						$sum = 0.0;
						$should_break = true;
						break;
					}
					$sum += $pax_discount_price * $occupancy_surcharge_percent;
				}
			}
			// Note: intentionally *not* breaking the outer loop (matches original logic)
		}

		return $sum;
	}


	public static function get_price_regular($regular = null, $type = null)
	{
		$sum = 0;
		
		if(is_booking_page() || is_confirmation_page())
		{

			$pax_regular = secure_request('pax_regular', 0, 'absint');

			if ($pax_regular === 0) {
				return 0;
			}

			$price_chart = self::get_price_chart();
			

			if(!is_array($price_chart)) {
				return 0;
			}

			$row = $price_chart[$pax_regular - 1] ?? [];

			$base_price = (is_array($row) && !empty($row[0]))
				? (float) $row[0]
				: 0.0;
			
			$sum = self::get_price_calc($base_price, $regular, 'regular');
			
			if($type === 'total' && $pax_regular > 0)
			{
				$sum = $sum * $pax_regular;
			}
		}
		return $sum;
	}	


	public static function get_price_discount(
		$regular = null,
		$type = null
	) {
		if (!is_booking_page() && !is_confirmation_page()) {
			return 0;
		}

		$pax_discount = secure_request(
			'pax_discount',
			0,
			'absint'
		);

		if ($pax_discount === 0) {
			return 0;
		}

		$price_chart = self::get_price_chart();

		if (!is_array($price_chart)) {
			return 0;
		}

		$row = $price_chart[$pax_discount - 1] ?? [];

		$base_price = (is_array($row) && !empty($row[1]))
			? (float) $row[1]
			: 0.0;

		$sum = self::get_price_calc(
			$base_price,
			$regular,
			'discount'
		);

		return $type === 'total'
			? $sum * $pax_discount
			: $sum;
	}
	
	public static function get_price_calc($sum, $regular, $type)
	{

		$cache_key ='get_price_calc_'.$sum.'_'.$regular.'_'.$type.'_'.get_dy_id();

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		
		$package_type = self::get_package_type();
		$occupancy_price = ($package_type === 'multi-day') ? self::get_price_occupancy($type) : 0;
		$sum = $sum + $occupancy_price;
		$booking_date = secure_request('booking_date');
		$week_days_to_surcharge = array($booking_date);
		$one_way_surcharge = (float) package_field('package_one_way_surcharge');

		if($package_type === 'transport')
		{
			$sum_arr = [$sum];

			$end_date = secure_request('end_date');

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

				if(!is_valid_date($end_date) && $one_way_surcharge > 0.0)
				{
					$one_way_surcharge = ($one_way_surcharge / 100) * $sum;
					$sum += $one_way_surcharge;
				}
			}
		}
		else
		{
			if(($package_type === 'rental-per-hour' || $package_type === 'rental-per-day'))
			{

				$effective_duration = max(1, absint(self::get_min_nights()) );

				$sum = $sum * $effective_duration;
			}

			if($package_type !== 'multi-day')
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
			$coupon_params = self::get_active_coupon_params();
			
			$sum = $sum * ((100 - $coupon_params->discount) /100);
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
		$output = [];
		$days = self::get_week_days_abbr();
		
		for($x = 0; $x < count($days); $x++)
		{
			if(intval(package_field('package_day_'.$days[$x] )) === 1)
			{
				$output[] = $x+1;
			}
		}
		return $output;
	}
	
	public static function hour()
	{

		$the_id = get_dy_id();
		$booking_hour = secure_request('booking_hour');
		$cache_key = 'dy_hour_' . $the_id . '_' . $booking_hour;

		if( array_key_exists($cache_key, self::$cache) ) {
			return self::$cache[$cache_key];
		}

		$package_by_hour = absint(package_field('package_by_hour'));


		if($package_by_hour === 1 && is_valid_time($booking_hour)) {
			return self::$cache[$cache_key] = $booking_hour;
		}

		$package_start_hour = package_field('package_start_hour');

		return self::$cache[$cache_key] = is_valid_time($package_start_hour) 
			? $package_start_hour 
			: '';
	}	
	
	public static function return_hour()
	{

		$the_id = get_dy_id();
		$return_hour = secure_request('return_hour');
		$cache_key = 'dy_return_hour_' . $the_id . '_' . $return_hour;

		if( array_key_exists($cache_key, self::$cache) ) {
			return self::$cache[$cache_key];
		}

		$package_by_hour = absint(package_field('package_by_hour'));

		//overrides package_return_hour
		if($package_by_hour === 1 && is_valid_time($return_hour)) {
			return self::$cache[$cache_key] = $return_hour;
		}

		$package_return_hour = package_field('package_return_hour');

		return self::$cache[$cache_key] = is_valid_time($package_return_hour) 
			? $package_return_hour 
			: '';

	}


	public static function webhook($url, $payload = '')
	{
		try {

			//silences the script termination when the user aborts the request (e.g., closes the browser)
			ignore_user_abort(true);

			$url = get_option($url);

			if (is_array($payload) || is_object($payload)) {
				$payload = wp_json_encode($payload);
			}

			if (!is_string($url)) {
				return false;
			}

			$url = str_replace('&#038;', '&', $url);

			if (filter_var($url, FILTER_VALIDATE_URL) === false) {
				return false;
			}

			$headers = [
				'Content-Type: application/json',
				'Content-Length: ' . strlen($payload)
			];

			//silences the curl_exec() output to the browser and flushes the output buffer
			while (ob_get_level() > 0) {
				ob_end_flush();
			}
			flush();

			$ch = curl_init();

			if ($ch === false) {
				throw new \RuntimeException('Failed to initialize cURL handle.');
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);

			$result = curl_exec($ch);

			if ($result === false) {
				$error = curl_error($ch);
				$errno  = curl_errno($ch);
				curl_close($ch);
				throw new \RuntimeException("cURL error ({$errno}): {$error}");
			}

			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpCode !== 200) {
				write_log(curl_error($ch) ?: $result);
				return false;
			}

			return true;

		} catch (\Throwable $e) {
			write_log($e->getMessage());
			return false;
		}
	}

	public static function get_taxonomies($term_name)
	{
		global $post;

		$output = [];
		$cache_key = 'dy_get_taxonomies_'.$term_name.'_'.$post->ID;

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$the_id = $post->ID;
		
		if(property_exists($post, 'post_parent') && $post->post_parent > 0)
		{
			$the_id = $post->post_parent;
		}		
		
		$terms = get_the_terms($the_id, $term_name);

		
		if($terms)
		{
			for($x = 0; $x < count($terms); $x++)
			{
				$output[] = $terms[$x];
			}			
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function get_taxo_names($term_name, $the_id = null) : array {
		
		$post = get_post($the_id);

		$cache_key = 'dy_get_taxo_names_'.$term_name.'_'.$post->ID;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$current_terms = get_the_terms($post->ID, $term_name);
		$current_terms = is_array($current_terms) ? $current_terms : [];

		$parent_terms = [];

		if(property_exists($post, 'post_parent') && $post->post_parent > 0)
		{
			$parent_terms = get_the_terms($post->post_parent, $term_name);
			$parent_terms = is_array($parent_terms) 
				? $parent_terms 
				: [];
		}

		$terms_by_id = [];

		foreach (array_merge($current_terms, $parent_terms) as $term) {
			if ($term instanceof WP_Term) {
				$terms_by_id[$term->term_id] = $term->name;
			}
		}

		$output = array_values($terms_by_id);

		return self::$cache[$cache_key] = $output;
	}
	
	public static function implode_taxo_names($tax, $last_separator = ',', $item_separator = '')
	{
		$output = '';
		$items_arr = self::get_taxo_names($tax, get_dy_id());

		if(is_array($items_arr) && count($items_arr) > 0)
		{
			$output = implode_last($items_arr, $last_separator, $item_separator);
		}

		return $output;
	}
	
	public static function get_add_ons_total() {
		$total = 0;
		$pax_num = self::pax_num();

		$add_ons_post = secure_post('add_ons');

		if (!apply_filters('dy_has_add_ons', null) || $add_ons_post === '') {
			return 0;
		}

		$add_ons = (array) apply_filters('dy_get_add_ons', []);
		$included_ids = array_map('absint', explode(',', $add_ons_post));

		foreach ($add_ons as $add_on) {
			$add_on_id = absint($add_on['id'] ?? 0);

			if (in_array($add_on_id, $included_ids, true)) {
				$total += $pax_num * (float) ($add_on['price'] ?? 0);
			}
		}
		
		return $total;
	}
	
	public static function payment_amount($service_fee = 0)
	{
		$the_id = get_dy_id();
		$total = (float) self::subtotal(null, $the_id);
		
		if(dy_validators::has_deposit())
		{
			$deposit = (float) self::get_deposit();
			$total = $total*($deposit*0.01);			
		}
		
		$total = $total + self::get_add_ons_total();

		$service_fee = (float) $service_fee;

		if($service_fee > 0)
		{
			$total = $total * (1 + ($service_fee / 100));
		}
				
		return $total;
	}	

	public static function outstanding_amount()
	{
		$total = self::total() ? (float) self::total() : 0;
		$payment_amount = self::payment_amount() ? (float) self::payment_amount() : 0;
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
			return (int) package_field('package_week_day_surcharge_' . $day);
		}, $days);
	}

	public static function get_tax_list($term_name = '', $label = '', $is_link = true, $icon_class = null)
	{
		$output = '';
		$is_link_str = $is_link ? 1 : 0;
		$icon_class_str = !empty($icon_class) ? 1 : 0;
		$cache_key = 'dy_get_tax_list_'.$term_name.'_'.strlen($label).'_'.$is_link_str.'_'.$icon_class_str;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}


		$terms_array = [];

		if(in_the_loop())
		{
			global $post;

			$parent_terms = [];
			$current_terms = get_the_terms($post->ID, $term_name);
			$current_terms = (is_array($current_terms)) ? $current_terms : array();

			if(property_exists($post, 'post_parent') && $post->post_parent > 0)
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
				
				$terms_array[] = $item;
			}
		}
		
		
		if (!empty($terms_array)) {
			if ($label) {
				$output .= sprintf('<p class="strong">%s</p>', esc_html($label));
			}

			$icon = $icon_class ? sprintf('<span class="%s"></span>', esc_attr($icon_class)) : '';

			$output .= sprintf(
				'<ul id="%1$s_list" class="dy-list-%1$s bottom-20 dy-list"><li>%2$s %3$s</li></ul>',
				esc_attr($term_name),
				$icon,
				implode(sprintf('</li><li>%s ', $icon), $terms_array)
			);
		}
	
		
		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function get_option_hot_chart($key_name) {


		$cache_key = 'dy_get_option_hot_chart' . $key_name;

		if (array_key_exists($cache_key, self::$cache)) {
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

		if (array_key_exists($cache_key, self::$cache)) {
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

	public static function enabled_days($force_long = false)
	{

		$the_id = get_dy_id();
		$cache_key = 'dy_enabled_days_' . $the_id . '_' . (int) $force_long;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}		

		$output = '';
		$days = dy_utilities::get_week_days_abbr();
		$labels = ($force_long === true) ? dy_utilities::get_week_day_names_long() : dy_utilities::get_week_day_names_short();
		$enabled_days = [];
		
		for($x = 0; $x < count($days); $x++)
		{
			$day = 'package_day_'.$days[$x];
			
			if(package_field($day) != 1)
			{
				$enabled_days[] = $labels[$x];
			}
		}
		
		if(count($enabled_days) == 7)
		{
			$output = __('Everyday', 'dynamicpackages');
		}
		else {
			$output = implode(', ', $enabled_days);
		}

		//store output in $cache
		self::$cache[$cache_key] = $output;

		
		return $output;
	}

	public static function update_package_date_in_db($the_id)
	{
		$the_id = (int) $the_id;

		if ($the_id <= 0) {
			return '';
		}

		// Resolve the default-language post.
		if (function_exists('pll_get_post') && function_exists('pll_default_language')) {
			$default_id = pll_get_post(
				$the_id,
				pll_default_language('slug')
			);

			if ($default_id) {
				$the_id = (int) $default_id;
			}
		}

		$cache_key = 'dy_update_package_date_in_db_' . $the_id;

		// Request-level cache.
		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		// Persistent transient cache.
		$cached_output = get_transient($cache_key);

		if ($cached_output !== false) {
			return self::$cache[$cache_key] = $cached_output;
		}

		$from = (int) package_field('package_booking_from');
		$to   = (int) package_field('package_booking_to');

		$base_timestamp = strtotime('today');

		$from_timestamp = $from > 0
			? strtotime("+{$from} days", $base_timestamp)
			: $base_timestamp;

		$to_timestamp = $to > 0
			? strtotime("+{$to} days", $base_timestamp)
			: strtotime('+365 days', $from_timestamp);

		// Invalid range.
		if ($to_timestamp < $from_timestamp) {
			$output = '';
		} else {

			$from_date = date('Y-m-d', $from_timestamp);
			$to_date   = date('Y-m-d', $to_timestamp);

			$range = (array) self::get_date_range(
				$from_date,
				$to_date
			);

			$disabled_dates = array_fill_keys(
				(array) self::get_disabled_range(),
				true
			);

			$disabled_days = array_fill_keys(
				array_map(
					'intval',
					(array) self::get_week_days_list()
				),
				true
			);

			$output = '';

			foreach ($range as $date) {

				if (isset($disabled_dates[$date])) {
					continue;
				}

				$day = (int) date('N', strtotime($date));

				if (isset($disabled_days[$day])) {
					continue;
				}

				$output = $date;
				break;
			}
		}

		if ($output !== '') {
			update_post_meta($the_id, 'package_date', $output);
		} else {
			delete_post_meta($the_id, 'package_date');
		}

		set_transient(
			$cache_key,
			$output,
			HOUR_IN_SECONDS
		);

		self::$cache[$cache_key] = $output;

		return $output;
	}

}