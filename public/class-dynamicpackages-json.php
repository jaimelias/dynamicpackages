<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_JSON
{
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
		global $is_json_request;
		$output = false;
		
		if(isset($is_json_request))
		{
			return $is_json_request;
		}
		else
		{
			if(isset($_GET['json']) && !is_admin())
			{
				$output = true;
				$GLOBALS['is_json_request'] = $output;	
			}
		}
		
		return $output;
	}
	
	public function get_header()
	{
		add_filter('minimal_ld_json', array(&$this, 'ld_json'), 100);
	}

	public function ld_json($arr = array())
	{
		global $dy_ld_json;
		global $polylang;
		
		if(isset($ld_json))
		{
			$arr = $dy_ld_json;
		}
		else
		{
			if(is_singular('packages'))
			{
				global $post;
				$starting_at = floatval(money(dy_utilities::starting_at($post->ID)));
				
				if(dy_validators::is_valid_schema())
				{
					global $post;
					$schema = intval(package_field('package_schema'));
					
					//offers
					$offers = array();
					$offers['@type'] = 'Offer';
					$offers['priceCurrency'] = 'USD';
					$offers['price'] = $starting_at;				
					$offers['url'] = esc_url(get_the_permalink());
					$offers['availability'] = 'http://schema.org/InStock';		
					$offers['validFrom'] = esc_html(date('Y-m-d', dy_strtotime('now')));
					
					//aggregateRating
					$aggregateRating = array();
					$aggregateRating['@type'] = 'aggregateRating';
					$aggregateRating['ratingValue'] = esc_html($this->reviews->get_rating(get_dy_id()));
					$aggregateRating['reviewCount'] = esc_html(get_comments_number());

					//reviews
					$reviews = array();
					$comments = $this->reviews->get_comments($post->ID);

					foreach($comments as $comment)
					{
						$review = array(
							'@type' => 'Review',
							'datePublished' => esc_html(date('Y-m-d', strtotime($comment->comment_date))),
							'description' => esc_html($comment->comment_content)
						);

						$author = array(
							'@type' => 'Person',
							'name' => esc_html($comment->comment_author)
						);

						$review['author'] = $author;		
						
						$reviewRating = array(
							'@type' => 'Rating',
							'bestRating' => '5',
							'ratingValue' => get_comment_meta($comment->comment_ID, 'dy_rating', true)
						);

						$review['reviewRating'] = $reviewRating;						
						
						array_push($reviews, $review);
					}				

					if($schema === 1)
					{
						//is product
						$arr['@context'] = 'https://www.schema.org';
						$arr['@type'] = 'Product';
						$arr['brand'] = array();
						$arr['brand']['@type'] = 'Brand';
						$arr['brand']['name'] = esc_html(get_bloginfo('name'));
						$arr['name'] = esc_html(get_the_title());
						$arr['sku'] = md5(package_field('package_trip_code'));
						
						if(has_post_thumbnail())
						{
							$arr['image'] = get_the_post_thumbnail_url();
						}
						
						$arr['description'] = $post->post_excerpt;
						
						if($this->reviews->get_rating(get_dy_id()) > 0)
						{
							$arr['aggregateRating'] = $aggregateRating;
						}

						$offers['priceValidUntil'] = esc_html(date('Y-m-d', strtotime('+1 year')));	
						$arr['offers'] = $offers;
						
						if(is_array($reviews))
						{
							if(count($reviews) > 0)
							{
								$arr['review'] = $reviews;
							}
						}
					}
					else
					{
						// is event
						$events = apply_filters('dy_event_arr', array());
						$event_arr = array();
						$event_max = count($events);
						
						if($event_max > 30)
						{
							$event_max = 30;
						}
						
						for($x = 0; $x < $event_max; $x++)
						{
							$duration = intval(package_field('package_duration'));
							$unit = package_field('package_length_unit');
							$event_date = $events[$x].' '.package_field('package_start_hour');
							$event_date_name = date_i18n('M d', strtotime($event_date));
							$event_date_format = date_i18n('Y-m-d\TH:i', strtotime($event_date));						
							
							if($unit == 0)
							{
								$event_date_end = date('Y-m-d\TH:i', strtotime($event_date)+(60 * $duration));
							}
							else if($unit == 1)
							{
								$event_date_end = date('Y-m-d\TH:i', strtotime($event_date) + (3600 * $duration));
							}
							else if($unit == 4)
							{
								$event_date_end = date('Y-m-d\TH:i', strtotime($event_date) + (7 * 24 * 3600 * $duration));
							}
							else
							{
								$event_date_end = date('Y-m-d\TH:i', strtotime("+ {$duration} days", strtotime($event_date)));
							}
							
							$event = array();
							$event['@context'] = 'https://www.schema.org';
							$event['@type'] = 'Event';
							$event['name'] = esc_html(get_the_title().' - '.$event_date_name);				
							$event['startDate'] = esc_html($event_date_format);
							$event['endDate'] = esc_html($event_date_end);
							$event['description'] = $post->post_excerpt;
							$event['organizer'] = array(
								'name' => esc_html(get_bloginfo('name')),
								'url' => esc_url(get_bloginfo('url'))
							);
							$event['performer'] = esc_html(get_bloginfo('name'));
							$event['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
							$event['eventStatus'] = 'https://schema.org/EventScheduled';
							
							if($this->reviews->get_rating(get_dy_id()) > 0)
							{
								$event['aggregateRating'] = $aggregateRating;
							}							
							if(has_post_thumbnail())
							{
								$event['image'] = get_the_post_thumbnail_url();
							}						
							
							$offers['priceValidUntil'] = esc_html($events[$x]);
							$event['offers'] = $offers;

							$event['location'] = array(
								'@type' => 'Place',
								'name' => esc_html(get_bloginfo('name')),
								'address' => esc_html(package_field('package_start_address'))
							);
							
							array_push($event_arr, $event);
						}

						$arr = $event_arr;
					}					
				}
			}
			
			$GLOBALS['dy_ld_json'] = $arr;
		}
		
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
		$disable = array();
		$disable['disable'] = array();
		$days = dy_utilities::get_week_days_abbr();
		$error_fallback = array(
			'disable' => [0, 1, 2, 3, 4, 5, 6],
			'min' => true,
			'max' => 365
		);
		
		if(empty(package_field('package_event_date')))
		{
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
			$disabled_dates = array();

			

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
				   
				   $range = array();
				   $range_fix = array();
				   
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
			
			$enabled_dates = array();
			
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
					
					$range = array();
					$range_fix = array();
					
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
	

}

?>