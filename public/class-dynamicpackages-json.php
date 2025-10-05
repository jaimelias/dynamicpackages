<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_JSON
{
	private static $cache = [];

	function __construct($reviews)
	{
		$this->reviews = $reviews;
		$this->init();
	}

	public function init(){
		add_action('wp', array(&$this, 'export'));
		add_action('get_header', array(&$this, 'get_header'));
	}
	
	public function is_json_request()
	{
		$cache_key = 'is_json_request';
		$output = false;
		
       if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
		else
		{
			if(isset($_GET['json']) && !is_admin())
			{
				$output = true;
				self::$cache[$cache_key] = $output;
			}
		}
		
		return $output;
	}
	
	public function get_header()
	{
		add_filter('minimal_ld_json', array(&$this, 'ld_json'), 100);
	}

	public function ld_json($arr = [])
	{
		// cache hit?
		if (isset($GLOBALS['dy_ld_json'])) {
			return $GLOBALS['dy_ld_json'];
		}

		// only build on single "packages"
		if (!is_singular('packages')) {
			$GLOBALS['dy_ld_json'] = $arr;
			return $arr;
		}

		global $post;

		// precompute commonly used values
		$site_name   = get_bloginfo('name');
		$url         = get_the_permalink();
		$title       = get_the_title();
		$has_thumb   = has_post_thumbnail();
		$thumb_url   = $has_thumb ? get_the_post_thumbnail_url() : null;
		$dy_id       = get_dy_id();
		$rating_val  = (float) $this->reviews->get_rating($dy_id);
		$review_cnt  = (int) get_comments_number();
		$starting_at = (float) money(dy_utilities::starting_at($post->ID));
		$schema      = (int) package_field('package_schema');
		$is_valid    = dy_validators::is_valid_schema();

		if (!$is_valid) {
			$GLOBALS['dy_ld_json'] = $arr;
			return $arr;
		}

		// base offers (mutated per context)
		$offers = [
			'@type' => 'Offer',
			'priceCurrency' => 'USD',
			'price' => $starting_at,
			'url' => $url,
			'availability' => 'https://schema.org/InStock',
			'validFrom' => esc_html(date('Y-m-d', dy_strtotime('now')))
		];

		$offers['hasMerchantReturnPolicy'] = [
			'@type' => 'MerchantReturnPolicy',
			'merchantReturnLink' => "{$url}#package_terms_conditions_list",
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'returnMethod' => 'https://schema.org/ReturnByMail',
			'returnFees' => 'https://schema.org/FreeReturn',
			'merchantReturnDays' => 30,
		];

		// aggregate rating (conditionally attached below)
		$aggregateRating = [
			'@type' => 'aggregateRating',
			'ratingValue' => esc_html($rating_val),
			'reviewCount' => esc_html($review_cnt),
		];

		// build reviews
		$reviews = [];
		$comments = $this->reviews->get_comments($post->ID);
		foreach ($comments as $comment) {
			$reviews[] = [
				'@type' => 'Review',
				'datePublished' => esc_html(date('Y-m-d', strtotime($comment->comment_date))),
				'description' => esc_html($comment->comment_content),
				'author' => [
					'@type' => 'Person',
					'name' => esc_html($comment->comment_author),
				],
				'reviewRating' => [
					'@type' => 'Rating',
					'bestRating' => '5',
					'ratingValue'=> get_comment_meta($comment->comment_ID, 'dy_rating', true),
				],
			];
		}

		if ($schema === 1) {
			// Product
			$arr = [
				'@context' => 'https://www.schema.org',
				'@type' => 'Product',
				'brand' => [
					'@type' => 'Brand',
					'name' => $site_name,
				],
				'name' => $title,
				'sku' => md5(package_field('package_trip_code')),
				'url' => $url,
			];

			if (!empty($post->post_excerpt)) {
				$arr['description'] = $post->post_excerpt;
			}
			if ($has_thumb) {
				$arr['image'] = $thumb_url;
			}
			if ($rating_val > 0) {
				$arr['aggregateRating'] = $aggregateRating;
			}

			// product-specific price validity
			$offers_product = $offers;
			$offers_product['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
			$arr['offers'] = $offers_product;

			if (!empty($reviews)) {
				$arr['review'] = $reviews;
			}
		} else {
			// Events
			$events = apply_filters('dy_event_arr', []);
			$event_max = min(30, count($events));
			$event_arr = [];

			$duration = (int) package_field('package_duration');
			$unit     = package_field('package_length_unit');
			$start_hr = package_field('package_start_hour');
			$start_address = package_field('package_start_address');
			$site_url = get_bloginfo('url');

			for ($x = 0; $x < $event_max; $x++) {
				$event_date        = $events[$x] . ' ' . $start_hr;
				$event_ts          = strtotime($event_date);
				$event_date_name   = date_i18n('M d', $event_ts);
				$event_date_format = date_i18n('Y-m-d\TH:i', $event_ts);

				// compute end date by unit
				if ($unit == 0) {                // minutes
					$end_ts = $event_ts + (60 * $duration);
				} elseif ($unit == 1) {          // hours
					$end_ts = $event_ts + (3600 * $duration);
				} elseif ($unit == 4) {          // weeks
					$end_ts = $event_ts + (7 * 24 * 3600 * $duration);
				} else {                          // days (default)
					$end_ts = strtotime("+ {$duration} days", $event_ts);
				}

				$event = [
					'@context' => 'https://www.schema.org',
					'@type' => 'Event',
					'name' => esc_html($title . ' - ' . $event_date_name),
					'startDate' => esc_html($event_date_format),
					'endDate' => esc_html(date('Y-m-d\TH:i', $end_ts)),
					'description' => $post->post_excerpt,
					'organizer' => [
						'name' => $site_name,
						'url' => $site_url,
					],
					'performer' => $site_name,
					'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
					'eventStatus' => 'https://schema.org/EventScheduled',
				];

				if ($rating_val > 0) {
					$event['aggregateRating'] = $aggregateRating;
				}
				if ($has_thumb) {
					$event['image'] = $thumb_url;
				}

				$event_offers = $offers;
				$event_offers['priceValidUntil'] = esc_html($events[$x]);
				$event['offers'] = $event_offers;

				$event['location'] = [
					'@type' => 'Place',
					'name' => $site_name,
					'address' => esc_html($start_address),
				];

				$event_arr[] = $event;
			}

			$arr = $event_arr;
		}

		$GLOBALS['dy_ld_json'] = $arr;
		return $arr;
	}

	
	public function export()
	{
		if($this->is_json_request())
		{
			if(is_singular('packages'))
			{
				if($_GET['json'] == 'disabled_dates')
				{
					wp_send_json($this->disabled_dates());
				}
				else
				{
					wp_send_json(array('Error' => 'json param is null or invalid'), 400);
				}
			}
		}
	}


	public function disabled_dates()
	{
		$disable = [];
		$disable['disable'] = [];
		$days = dy_utilities::get_week_days_abbr();
		$error_fallback = array(
			'disable' => [0, 1, 2, 3, 4, 5, 6],
			'min' => true,
			'max' => 365
		);
		
		for($x = 0; $x < count($days); $x++)
		{
			if(intval(package_field('package_day_'.$days[$x] )) == 1)
			{
				array_push($disable['disable'], $x+1);
			}
		}

		$time = date('Y-m-d');
		$from = intval(package_field('package_booking_from'));
		
		if($from == 0)
		{
			$from = true;
		}
		
		$to = intval(package_field('package_booking_to'));
		
		$disable['min'] = $from;
		$disable['max'] = $to;	
		$disabled_dates = [];

		

		$global_disabled_dates = dy_utilities::get_option_hot_chart('dy_disabled_dates');
		$get_disabled_dates = dy_utilities::get_package_hot_chart('package_disabled_dates');
		$get_enabled_dates = dy_utilities::get_package_hot_chart('package_enabled_dates');
		
		if(is_array($global_disabled_dates))
		{
			if(array_key_exists('disabled_dates', $global_disabled_dates))
			{
				$global_disabled_dates = $global_disabled_dates['disabled_dates'];
									
				for($x = 0; $x < count($global_disabled_dates); $x++)
				{
					$disabled_dates[] = $global_disabled_dates[$x];
				}
			}
		}
		
		if(is_array($get_disabled_dates))
		{
			if(array_key_exists('disabled_dates', $get_disabled_dates))
			{		
				$get_disabled_dates = $get_disabled_dates['disabled_dates'];
										
				for($x = 0; $x < count($get_disabled_dates); $x++){
					$disabled_dates[] = $get_disabled_dates[$x];
				}
			}
		}				
		
		if(is_array($disabled_dates))
		{
			for($x = 0; $x < count($disabled_dates); $x++)
			{
				if(!is_valid_date($disabled_dates[$x][0]))
				{
					continue;
				}

				$date_from = $disabled_dates[$x][0] . ' 00:00:00';
				$date_to = (!is_valid_date($disabled_dates[$x][1])) 
					? $disabled_dates[$x][0]  . ' 00:00:00' 
					: $disabled_dates[$x][1]  . ' 00:00:00';

				$period = new DatePeriod(
					new DateTime($disabled_dates[$x][0]),
					new DateInterval('P1D'),
					new DateTime(date('Y-m-d H:i:s', strtotime($disabled_dates[$x][1] . ' +1 day')))
				);
				
				$range = [];
				$range_fix = [];
				
				foreach ($period as $key => $value)
				{
					$this_date = $value->format('Y-m-d H:i:s');
					$this_date = explode("-", $this_date);
					$this_date = array_map('intval', $this_date);
					$this_date = array_map(function($arr, $keys){
						if($keys == 1)
						{
							$arr = $arr - 1;
						}
						return $arr;
					}, $this_date, array_keys($this_date));
					$disable['disable'][] = $this_date;
				}

			}			
		}
	
		$api_disabled_endpoint = package_field('package_disabled_dates_api');
		
		if (filter_var($api_disabled_endpoint, FILTER_VALIDATE_URL) !== false)
		{
			$api_disabled_dates = wp_remote_get($api_disabled_endpoint);
			
			if(is_wp_error($api_disabled_dates) || !is_array($api_disabled_dates) || wp_remote_retrieve_response_code($api_disabled_dates) !== 200)
			{
				return $error_fallback;
			}

			if(array_key_exists('body', $api_disabled_dates))
			{
				$api_disabled_dates = json_decode($api_disabled_dates['body']);
				
				if(is_array($api_disabled_dates))
				{	
					for($x = 0; $x < count($api_disabled_dates); $x++)
					{
						if(is_valid_date($api_disabled_dates[$x]))
						{
							$api_date = $api_disabled_dates[$x];
							$api_date = explode("-", $api_date);
							$api_date = array_map('intval', $api_date);
							$api_date = array_map(function($arr, $keys){
								if($keys == 1)
								{
									$arr = $arr - 1;
								}
								return $arr;
							}, $api_date, array_keys($api_date));
							$disable['disable'][] = $api_date;									
						}
					}
				}
			}
		}
		
		$enabled_dates = [];
		
		if(is_array($get_enabled_dates))
		{
			if(array_key_exists('enabled_dates', $get_enabled_dates))
			{		
				$get_enabled_dates = $get_enabled_dates['enabled_dates'];
									
				for($x = 0; $x < count($get_enabled_dates); $x++){
					$enabled_dates[] = $get_enabled_dates[$x];
				}
			}				
		}
		
		if(is_array($enabled_dates))
		{
			for($x = 0; $x < count($enabled_dates); $x++)
			{

				if(!is_valid_date($enabled_dates[$x][0]))
				{
					continue;
				}

				$from_date = $enabled_dates[$x][0] . ' 00:00:00';
				$to_date = (!is_valid_date($enabled_dates[$x][1])) 
					? $enabled_dates[$x][0] . ' 00:00:00'
					: $enabled_dates[$x][1] . ' 00:00:00';

				$period = new DatePeriod(
					new DateTime($enabled_dates[$x][0]),
					new DateInterval('P1D'),
					new DateTime(date('Y-m-d H:i:s', strtotime($enabled_dates[$x][1] . ' +1 day')))
				);
				
				$range = [];
				$range_fix = [];
				
				foreach ($period as $key => $value)
				{
					$this_date = $value->format('Y-m-d H:i:s');
					$valid_date = true;
					
					if(isset($api_disabled_dates))
					{
						if(is_array($api_disabled_dates))
						{
							if(in_array($this_date, $api_disabled_dates))
							{
								$valid_date = false;
							}
						}								
					}
					
					if($valid_date)
					{
						$this_date = explode("-", $this_date);
						$this_date = array_map('intval', $this_date);
						$this_date = array_map(function($arr, $keys){
							if($keys == 1)
							{
								$arr = $arr - 1;
							}							
							return $arr;
						}, $this_date, array_keys($this_date));
						
						$this_date[] = 'inverted';
						
						$disable['disable'][] = $this_date;								
					}
				}				
			}			
		}
		
		if(count($disable) > 0)
		{
			return $disable;
		}
	
	}
	

}

?>