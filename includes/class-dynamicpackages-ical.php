<?php 


class dy_Ical
{
	function __construct()
	{
			add_action('wp', array('dy_Ical', 'export'), 2);
	}
	
	public static function is_valid()
	{
		global $ical_valid;
		$output = false;
		
		if(isset($ical_valid))
		{
			return $ical_valid;
		}
		else
		{
			if(isset($_GET['ical']) && is_singular('packages') && package_field('package_start_hour') != '')
			{
				$output = true;
				$GLOBALS['ical_valid'] = $output;
			}
		}
		
		return $output;
	}
	
	
	public static function export()
	{
		if(self::is_valid())
		{
			$event = dy_validators::event();
			$events = array();
			$event_max = count($event);
			
			if($event_max > 200)
			{
				$event_max = 200;
			}		
			
			for($x = 0; $x < $event_max; $x++)
			{
				global $post;
				
				$event_date_name = date_i18n('M d', strtotime($event[$x]));
				$event_item = array();
				$event_item['SUMMARY'] = esc_html($post->post_title.' - '.$event_date_name);
				
				if(has_excerpt())
				{
					$event_item['DESCRIPTION'] = esc_html($post->post_excerpt);
				}
				
				$event_item['UID'] = esc_html(strtoupper(uniqid()));
				$event_item['DTSTART'] = esc_html(self::start($event[$x]));
				$event_item['DTEND'] = esc_html(self::end($event[$x]));
				$event_item['DTSTAMP'] = esc_html(dy_date(self::date_format()), strtotime(get_the_date()));
				$event_item['TRANSP'] = 'TRANSPARENT';
				
				if(package_field('package_start_address'))
				{
					$event_item['LOCATION'] = esc_html(package_field('package_start_address'));
				}
				
				$categories = dy_utilities::implode_taxo_names('package_category');
				
				if($categories != '')
				{
					$event_item['CATEGORIES'] = esc_html($categories);
				}

				array_push($events, $event_item);
			}
			header('Content-type: text/calendar');
			die(self::calendar($events));
		}
	}
	
	public static function calendar($events)
	{
		$output = "BEGIN:VCALENDAR\r\n";
		$output .= "VERSION:2.0\r\n";
		$title = get_bloginfo('name');
		
		$output .= "PRODID:-//".get_bloginfo('name')."//".$title."//EN\r\n";
		$output .= "CALSCALE:GREGORIAN\r\n";
		$output .= "METHOD:PUBLISH\r\n";
		
		$timezone = get_option('timezone_string');

		for($x = 0; $x < count($events); $x++)
		{
			$output .= "BEGIN:VEVENT\r\n";
			foreach($events[$x] as $k => $v)
			{
				$label = $k;
				
				if($timezone != '')
				{
					if($k == 'DTSTART' || $k == 'DTEND' || $k == 'DTSTAMP')
					{
						$label .= ';TZID=' . $timezone;
					}				
				}
				
				$item = $label . ":" . $v;
				$item = strlen($item) > 72 ? substr($item, 0, 72) . '...' : $item;
				$output .= $item .  "\r\n";
			}
			$output .= "END:VEVENT\r\n";
		}
		
		
		$output .= "END:VCALENDAR";
		return $output;
	}
	
	public static function start($date)
	{
		$hour = package_field('package_start_hour');
		return date(self::date_format(), strtotime($date.' '.$hour));
	}
	
	public static function end($date)
	{		
		$duration = intval(package_field('package_duration'));
		$unit = package_field('package_length_unit');
		$event_date = $date.' '.package_field('package_start_hour');
		
		if($unit == 0)
		{
			$output = strtotime($event_date) + (60 * $duration);
		}
		else if($unit == 1)
		{
			$output = strtotime($event_date) + (3600 * $duration);
		}
		else if($unit == 4)
		{
			$output = strtotime($event_date) + (7 * 24 * 3600 * $duration);
		}
		else
		{
			$output = strtotime("+ {$duration} days", strtotime($event_date));
		}		
		
		return date(self::date_format(), $output);
	}	
	
	public static function get_from_to_range()
	{
		if(package_field('package_event_date') != '')
		{
			$new_range = array(package_field('package_event_date'));
		}
		else
		{
			$today = dy_strtotime('today');
			$last_day = strtotime("+365 days", dy_strtotime('today'));
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
		}
		return $new_range;
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
	public static function arrayFlatten($array) { 
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

	public static function date_format()
	{
		return 'Ymd\THis';
	}
}



?>