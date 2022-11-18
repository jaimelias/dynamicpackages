<?php

if ( !defined( 'WPINC' ) ) exit;

define('DY_CORE_FUNCTIONS', true);

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
		$dy_cloudflare_api_token = get_option('dy_cloudflare_api_token');
		
		if(!empty($dy_cloudflare_api_token))
		{
			$url = 'https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules';


			if(isset($_SERVER['HTTP_CF_CONNECTING_IP']))
			{
				$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
			}
			else
			{
				$ip = $_SERVER['REMOTE_ADDR'];

				if($_SERVER['SERVER_NAME'] !== 'localhost')
				{
					$admin_email = get_option('admin_email');
					$email_message = 'Cloudflare WAF is not Enabled in: ' . get_bloginfo('name');

					$email_args = array(
						'to' => sanitize_email($admin_email),
						'subject' => $email_message,
						'message' => $email_message
					);


					sg_mail($email_args);
				}
			}


			$headers = array(
				'Authorization' => 'Bearer ' . sanitize_text_field($dy_cloudflare_api_token),
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
				$code = $resp['response']['code'];
				$data = json_decode($resp['body'], true);

				$messages = $data['messages'];

				$errors = $data['errors'];

				$log = array(
					'messages' => $messages,
					'errors' => $errors,
					'ip' => $ip
				);

				$log = json_encode($log);

				if($code === 200)
				{
					if($data['success'])
					{
						write_log('Cloudflare WAF Banned IP: '. $log);
						$output = true;
					}
					else
					{
						write_log('Cloudflare WAF Error: '. $log);
					}
				}
				else
				{
					write_log('Cloudflare WAF Error: ' . $log);
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



if(!function_exists('home_lang'))
{
	function home_lang()
	{
		$which_var = 'wp_core_home_lang';
		global $$which_var;
		$output = '';

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			global $polylang;

			if($polylang)
			{
				$pll_url = pll_home_url();
				$current_language = pll_current_language();
				$parsed_url = parse_url($pll_url);
				$scheme = $parsed_url['scheme'];
				$host = $parsed_url['host'];
				$path = $parsed_url['path'];
				$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
				$langPath = '';
				$path_arr = array_values(array_filter(explode('/', $path)));

				if(in_array($current_language, $path_arr))
				{
					$path = $current_language;
				}
				else
				{
					$path = '';
				}

				$output =  home_url($path.'/');
			}
			else
			{
				$output =  home_url('/');
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}
}

if(!function_exists('whatsapp_button'))
{
	function whatsapp_button($label = '', $text = '')
	{
		$output = '';
		$number = preg_replace('/[^0-9.]+/', '', get_option('dy_whatsapp'));

		if(intval($number) > 0)
		{
			if($label === '')
			{
				$label = 'Whatsapp';
			}
			
			if($text === '')
			{
				if(is_singular())
				{
					global $post;
					$text = $post->post_title;
				}
				else if(is_tax())
				{
					$text = single_term_title( '', false);
				}
				else
				{
					$text = get_bloginfo('name');
				}
			}
			
			
			$text =  '?text='.urlencode($text);
			
			$url = 'https://wa.me/'.$number.$text;
			$output = '<a class="pure-button button-whatsapp" target="_blank" href="'.esc_url($url).'"><span class="dashicons dashicons-whatsapp"></span> '.esc_html($label).'</a>';
		}

		return $output;
	}
}


if(!function_exists('validate_recaptcha'))
{
	function validate_recaptcha()
	{
		global $dy_valid_recaptcha;
		$invalids = array();
		$output = false;

		if(isset($dy_valid_recaptcha))
		{
			$output = $dy_valid_recaptcha;
		}
		else
		{
			if(isset($_POST['g-recaptcha-response']))
			{
				$secret_key = get_option('dy_recaptcha_secret_key');

				if($secret_key)
				{
					$url = 'https://www.google.com/recaptcha/api/siteverify';

					$ip = (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) 
						? $_SERVER['HTTP_CF_CONNECTING_IP'] : 
						$_SERVER['REMOTE_ADDR'];

					$params = array(
						'secret' => $secret_key,
						'remoteip' => $ip,
						'response' => sanitize_text_field($_POST['g-recaptcha-response']),
					);

					$resp = wp_remote_post($url, array(
						'body' => $params
					));

					if ( is_array( $resp ) && ! is_wp_error( $resp ) )
					{
						if($resp['response']['code'] === 200)
						{
							$data = json_decode($resp['body'], true);

							if($data['success'] === true)
							{
								$output = true;
							}
							else
							{
								$GLOBALS['dy_request_invalids'] = array(__('Invalid Recaptcha', 'dynamicpackages'));
								$debug_output = array('recaptcha-error' => $data['error-codes']);

								if(array_key_exists('error-codes', $data))
								{
									if(in_array('invalid-input-response' , $data['error-codes']))
									{
										cloudflare_ban_ip_address();
									}
								}
								
								write_log(json_encode($debug_output));
							}
						}
					}
				}
			}

			$GLOBALS['dy_valid_recaptcha'] = $output;
		}

		return $output;
	}
}

if(!function_exists('get_inline_file'))
{
	function get_inline_file($dir)
	{
		ob_start();
		require_once($dir);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}
}


?>