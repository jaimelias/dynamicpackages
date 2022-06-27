<?php

class Dynamicpackages_JSON
{
	function __construct($reviews)
	{
		$this->reviews = $reviews;
		$this->init();
	}

	public function init(){
		add_action('wp', array(&$this, 'export'));
		add_filter('minimal_ld_json', array(&$this, 'ld_json'), 100);
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
				$event_id = $post->ID;
				
				if(dy_validators::has_children())
				{
					$ids = array();
					$children = dy_validators::has_children();
					
					foreach ( $children as $child )
					{
						$ids[$child->ID] = dy_utilities::starting_at($child->ID);
					}

					if(is_array($ids))
					{
						if(count($ids))
						{
							$id_keys = array_keys($ids, min($ids));
							
							if(is_array($id_keys))
							{
								if(count($id_keys) > 0)
								{
									$event_id = $id_keys[0];
								}
							}
						}
					}
				}			
				
				$starting_at = floatval(number_format(dy_utilities::starting_at($event_id), 2, '.', ''));
				
				if(dy_validators::is_valid_schema())
				{
					global $post;
					$event = apply_filters('dy_event_arr', array());
					
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
					$aggregateRating['ratingValue'] = esc_html($this->reviews->get_rating(get_the_ID()));
					$aggregateRating['reviewCount'] = esc_html(get_comments_number());

					//reviews
					$review = array();
					
					foreach(get_comments(array('post_id' => $event_id)) as $comment)
					{
						$item = array();
						$item['@type'] = 'Review';
						$item['author'] = esc_html($comment->comment_author);
						$item['datePublished'] = esc_html(date('Y-m-d', strtotime($comment->comment_date)));
						$item['description'] = esc_html($comment->comment_content);
						
						$reviewRating = array();
						$reviewRating['@type'] = 'Rating';
						$reviewRating['bestRating'] = '5';
						$reviewRating['ratingValue'] = get_comment_meta($comment->comment_ID, 'dy_rating', true);
						$reviewRating['worstRating'] = '1';
						$item['reviewRating'] = $reviewRating;
						
						array_push($review, $item);
					}
					
					if(isset($polylang))
					{
						foreach(get_comments(array('post_id' => pll_get_post($event_id, pll_default_language()))) as $comment)
						{
							$item = array();
							$item['@type'] = 'Review';
							$item['author'] = esc_html($comment->comment_author);
							$item['datePublished'] = esc_html(date('Y-m-d', strtotime($comment->comment_date)));
							$item['description'] = esc_html($comment->comment_content);
							
							$reviewRating = array();
							$reviewRating['@type'] = 'Rating';
							$reviewRating['bestRating'] = '5';
							$reviewRating['ratingValue'] = get_comment_meta($comment->comment_ID, 'dy_rating', true);
							$reviewRating['worstRating'] = '1';
							$item['reviewRating'] = $reviewRating;						
							
							array_push($review, $item);
						}					
					}
					
					if(count($event) == 0)
					{
						//is not event
						$arr['@context'] = 'https://www.schema.org';
						$arr['@type'] = 'Product';
						$arr['brand'] = array();
						$arr['brand']['@type'] = 'Thing';
						$arr['brand']['name'] = esc_html(get_bloginfo('name'));
						$arr['name'] = esc_html(get_the_title());
						$arr['sku'] = md5(package_field('package_trip_code'));
						$arr['gtin8'] = substr(md5(package_field( 'package_trip_code' )), 0, 8);
						
						if(has_post_thumbnail())
						{
							$arr['image'] = get_the_post_thumbnail_url();
						}
						
						$arr['description'] = $post->post_excerpt;
						
						if($this->reviews->get_rating(get_the_ID()) > 0)
						{
							$arr['aggregateRating'] = $aggregateRating;
						}

						$offers['priceValidUntil'] = esc_html(date('Y-m-d', strtotime('+1 year')));	
						$arr['offers'] = $offers;
						
						if(is_array($review))
						{
							if(count($review) > 0)
							{
								$arr['review'] = $review;
							}
						}
					}
					else
					{
						// is event
						$event_arr = array();
						$event_max = count($event);
						
						if($event_max > 30)
						{
							$event_max = 30;
						}
						
						for($x = 0; $x < $event_max; $x++)
						{
							$duration = intval(package_field('package_duration'));
							$unit = package_field('package_length_unit');
							$event_date = $event[$x].' '.package_field('package_start_hour');
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
							
							$item = array();
							$item['@context'] = 'https://www.schema.org';
							$item['@type'] = 'Event';
							$item['name'] = esc_html(get_the_title().' - '.$event_date_name);				
							$item['startDate'] = esc_html($event_date_format);
							$item['endDate'] = esc_html($event_date_end);
							$item['description'] = $post->post_excerpt;
							$item['organizer'] = array(
								'name' => esc_html(get_bloginfo('name')),
								'url' => esc_url(get_bloginfo('url'))
							);
							$item['performer'] = esc_html(get_bloginfo('name'));
							$item['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
							$item['eventStatus'] = 'https://schema.org/EventScheduled';
							
							if($this->reviews->get_rating(get_the_ID()) > 0)
							{
								$item['aggregateRating'] = $aggregateRating;
							}							
							if(has_post_thumbnail())
							{
								$item['image'] = get_the_post_thumbnail_url();
							}						
							
							$offers['priceValidUntil'] = esc_html($event[$x]);
							$item['offers'] = $offers;

							$item['location'] = array(
								'@type' => 'Place',
								'name' => esc_html(get_bloginfo('name')),
								'address' => esc_html(package_field('package_start_address'))
							);
							
							array_push($event_arr, $item);
						}
						$arr = $event_arr;
						$GLOBALS['dy_ld_json'] = $arr;
					}					
				}
			}			
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
			$get_disabled_dates = json_decode(html_entity_decode(package_field('package_disabled_dates')), true);
			$global_disabled_dates = json_decode(html_entity_decode(get_option('dy_disabled_dates' )), true);
			$get_enabled_dates = json_decode(html_entity_decode(package_field('package_enabled_dates' )), true);
			
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
					if($disabled_dates[$x][0] && $disabled_dates[$x][1])
					{
						$period = new DatePeriod(
							 new DateTime($disabled_dates[$x][0]),
							 new DateInterval('P1D'),
							 new DateTime(date('Y-m-d', strtotime($disabled_dates[$x][1] . ' +1 day')))
						);
						
						$range = array();
						$range_fix = array();
						
						foreach ($period as $key => $value)
						{
							$this_date = $value->format('Y-m-d');
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
			}
		
			$api_disabled_endpoint = package_field('package_disabled_dates_api');
			
			if (filter_var($api_disabled_endpoint, FILTER_VALIDATE_URL) !== false)
			{
				$api_disabled_dates = wp_remote_get($api_disabled_endpoint);
				
				if(is_array($api_disabled_dates))
				{	
					if(array_key_exists('body', $api_disabled_dates))
					{
						$api_disabled_dates = json_decode($api_disabled_dates['body']);
						
						if(is_array($api_disabled_dates))
						{	
							for($x = 0; $x < count($api_disabled_dates); $x++)
							{
								if(dy_validators::is_date($api_disabled_dates[$x]))
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
					if($enabled_dates[$x][0] && $enabled_dates[$x][1])
					{
						$period = new DatePeriod(
							 new DateTime($enabled_dates[$x][0]),
							 new DateInterval('P1D'),
							 new DateTime(date('Y-m-d', strtotime($enabled_dates[$x][1] . ' +1 day')))
						);
						
						$range = array();
						$range_fix = array();
						
						foreach ($period as $key => $value)
						{
							$this_date = $value->format('Y-m-d');
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
			}
			
			if(count($disable) > 0)
			{
				return $disable;
			}				
		}
	
	}
	

}

?>