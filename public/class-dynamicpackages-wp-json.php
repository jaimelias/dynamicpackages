<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_WP_JSON
{
	private static $cache = [];

	function __construct()
	{
		add_action('rest_api_init', array($this, 'register_rest_routes'));
	}

	public function register_rest_routes()
	{

		$package_id_args = array(
			'required'          => true,
			'sanitize_callback' => 'absint',
			'validate_callback' => static function($value) {
				return dy_validators::validate_the_id($value);
			},
		);

		$dy_nonce_args = array(
			'required'          => true,
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => static function($value) {
				return is_string($value) && wp_verify_nonce($value, 'dy_nonce');
			},
		);

		register_rest_route(
			'dy-core',
			'/dynamicpackages/disabled-dates/(?P<package_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(
					$this,
					'disabled_dates_endpoint'
				),
				'permission_callback' => '__return_true',
				'args'                => array(
					'package_id' => $package_id_args,
					'dy_nonce' => $dy_nonce_args,
				),
			)
		);
	}
	

	public function disabled_dates_endpoint($request)
	{
		$package_id = $request['package_id'];
		$post = get_post($package_id);

		$is_readable = $post instanceof WP_Post
			&& 'packages' === $post->post_type
			&& (
				is_post_publicly_viewable($post)
				|| current_user_can('read_post', $package_id)
			);

		if (!$is_readable) {
			return $this->rest_response(
				array(
					'code'    => 'dynamicpackages_package_not_found',
					'message' => 'Package not found.',
					'data'    => array('status' => 404),
				),
				404
			);
		}

		$data = $this->disabled_dates($post);

		return $this->rest_response($data);
	}

	private function rest_response($data, $status = 200)
	{
		$response = new WP_REST_Response($data, $status);

		$headers = wp_get_nocache_headers();
		unset($headers['Last-Modified']);

		// Retained for older HTTP/1.0 clients and intermediaries.
		$headers['Pragma'] = 'no-cache';

		$response->set_headers($headers);

		return $response;
	}


	public function disabled_dates($post)
	{
		$the_id = $post->ID;
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
			$day_of_the_week = (int) package_field('package_day_'.$days[$x], $the_id );

			if($day_of_the_week == 1)
			{
				array_push($disable['disable'], $x+1);
			}
		}

		$time = date('Y-m-d');
		$from = (int) package_field('package_booking_from', $the_id );
		
		if($from == 0)
		{
			$from = true;
		}
		
		$to = (int) package_field('package_booking_to', $the_id );
		
		$disable['min'] = $from;
		$disable['max'] = $to;	
		$disabled_dates = [];

		

		$global_disabled_dates = dy_utilities::get_option_hot_chart('dy_disabled_dates'); //uses get_option
		$get_disabled_dates = dy_utilities::get_package_hot_chart('package_disabled_dates', $the_id );
		$get_enabled_dates = dy_utilities::get_package_hot_chart('package_enabled_dates', $the_id );
		
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
	
		$api_disabled_endpoint = package_field('package_disabled_dates_api', $the_id );
		
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