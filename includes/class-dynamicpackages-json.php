<?php

class dy_Json
{
	function __construct()
	{
		add_action('wp', array('dy_Json', 'export'));
		add_filter('minimal_ld_json', array('dy_Json', 'ld_json'), 100);
	}
	
	public static function is_disabled_dates()
	{
		global $is_disabled_dates;
		$output = false;
		
		if(isset($is_disabled_dates))
		{
			return $is_disabled_dates;
		}
		else
		{
			if(isset($_GET['json']) && !is_admin())
			{
				$output = true;
				$GLOBALS['is_disabled_dates'] = $output;	
			}
		}
		
		return $output;
	}
	
	public static function ld_json($arr = array())
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
					//dy_validators::has_children() returns the children obj
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
					$event = dy_validators::event();
					
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
					$aggregateRating['ratingValue'] = esc_html(dy_Reviews::get_rating(get_the_ID()));
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
						
						if(dy_Reviews::get_rating(get_the_ID()) > 0)
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
							$event_date = $event[$x].' '.package_field('package_departure_hour');
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
							
							if(dy_Reviews::get_rating(get_the_ID()) > 0)
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
								'address' => esc_html(package_field('package_departure_address'))
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
	
	public static function export()
	{
		if(self::is_disabled_dates())
		{
			if(is_singular('packages'))
			{
				if($_GET['json'] == 'disabled_dates')
				{
					wp_send_json(dy_Public::disabled_dates());
				}
				else
				{
					wp_send_json(array('Error' => 'json param is null or invalid'), 400);
				}
			}
		}
	}
}

?>