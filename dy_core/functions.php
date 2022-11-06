<?php


if ( ! function_exists('write_log')) {
	
	if(! function_exists('var_error_log'))
	{
		function var_error_log( $object=null ){
			ob_start();
			var_dump( $object );
			$contents = ob_get_contents();
			ob_end_clean();
			return $contents;
		}
	}
	
	function write_log ( $log )  {
		
		$output = '';
		$request_uri = sanitize_text_field($_SERVER['REQUEST_URI']);
		$user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
		
		if ( is_array( $log ) || is_object( $log ) ) {

			$output = print_r(var_error_log($log), true);
			$output .= ' '.$request_uri;  
			$output .= ' '.$user_agent;  
			error_log( $output );
		}
		else
		{
			$output = $log;
			$output .= ' '.$request_uri;  
			$output .= ' '.$user_agent;
			error_log( $log );
		}
	}
}


if(!function_exists('get_languages'))
{
	function get_languages()
	{
		global $polylang;
		$output = array();
		$which_var = 'wp_core_get_languages';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(isset($polylang))
			{
				$languages = PLL()->model->get_languages_list();

				for($x = 0; $x < count($languages); $x++)
				{
					foreach($languages[$x] as $key => $value)
					{
						if($key == 'slug')
						{
							array_push($output, $value);
						}
					}	
				}
			}

			if(count($output) === 0)
			{
				$locale_str = get_locale();

				if(strlen($locale_str) === 5)
				{
					array_push($output, substr($locale_str, 0, -3));
				}
				else if(strlen($locale_str) === 2)
				{
					array_push($output, $locale_str);
				}
			}

			$GLOBALS[$which_var] = $output;
		}


		return $output;
	}	
}

if(!function_exists('current_language'))
{
	function current_language()
	{
		global $polylang;
		$output = '';
		$which_var = 'wp_core_current_language';
		global $$which_var;

		if($$which_var)
		{
			$output = $$which_var;
		}
		else
		{
			if(isset($polylang))
			{
				$output = pll_current_language();
			}
			else
			{
				$locale = get_locale();
				$locale_strlen = strlen($locale);

				if($locale_strlen === 5)
				{
					$output = substr($locale, 0, -3);
				}
				if($locale_strlen === 2)
				{
					$output = $locale;
				}			
			}

			$GLOBALS[$which_var] = $output;
		}


		return $output;
	}
}

if(!function_exists('cloudflare_ban_ip_address'))
{
	function cloudflare_ban_ip_address(){

		$output = false;
		$cfp_key = get_option('cfp_key');
		
		if(!empty($cfp_key))
		{

			$url = 'https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules';

			$ip = (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) 
				? $_SERVER['HTTP_CF_CONNECTING_IP'] 
				: $_SERVER['REMOTE_ADDR'];

			$headers = array(
				'Authorization' => 'Bearer ' . sanitize_text_field($cfp_key),
				'Content-Type' => 'application/json'
			);

			$data = array(
				'mode' => 'block',
				'configuration' => array('target' => 'ip', 'value' => $ip),
				'notes' => 'Banned on '.date('Y-m-d H:i:s').' by PHP-script'
			);

			$resp = wp_remote_post($url, array(
				'headers' => $headers,
				'body' => json_encode($data),
				'data_format' => 'body'
			));
			
			if ( is_array( $resp ) && ! is_wp_error( $resp ) )
			{

				$body = (isset($resp['body'])) ? $resp['body'] : '';

				$success = (isset($data['success'])) 
					? ($data['success'] === true) 
					? true 
					: false
					: false;

				if($resp['response']['code'] === 200)
				{
					if($success)
					{
						//write_log('Banned IP:' . sanitize_text_field($ip));
						$output = true;
					}
					else
					{
						write_log($body);
					}
				}
				else
				{
					write_log($body);
				}
			}
			else
			{
				write_log('Unknown Cloudflare Error');
			}
		}

		return $output;
	}	
}


?>