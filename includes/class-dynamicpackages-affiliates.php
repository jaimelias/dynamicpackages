<?php

class dynamicpackages_Affiliates{
	
	function __construct()
	{
		add_action('pre_get_posts', array('dynamicpackages_Affiliates', 'get_affiliate_webhook'));
		add_action('init', array('dynamicpackages_Affiliates', 'init'));
		add_action('admin_init', array('dynamicpackages_Affiliates', 'admin_init'), 2);
		add_action('wp_head', array('dynamicpackages_Affiliates', 'affiliate_change'));
	}
	public static function init()
	{
		add_filter('the_permalink', array('dynamicpackages_Affiliates', 'affiliate_archive_permalink'), 1);
	}	
	public static function admin_init()
	{
		register_setting('tp_settings', 'tapfiliate', 'sanitize_user');
		
		add_settings_field( 
			'tapfiliate', 
			esc_html(__( 'Tapfiliate API Key', 'dynamicpackages' )), 
			array('dynamicpackages_Affiliates', 'display_tapfiliate'), 
			'tp_settings', 
			'tp_settings-section' 
		);			
	}
	public static function display_tapfiliate() { ?>
		<input type="text" name="tapfiliate" id="tapfiliate" value="<?php echo esc_html(get_option('tapfiliate')); ?>" /> <a target="_blank" href="https://tapfiliate.com/user/api-access/">Link</a>
		<?php if(!self::validate_tapfiliate_api_key()): ?>
			<br/><span><?php echo __('Invalid API Key', 'dynamicpackages'); ?></span>
		<?php endif; ?>
	<?php }		
	
	
	public static function affiliate_change()
	{
		$output = '';
		
		if(self::valid_affiliate())
		{
			$output = self::get_affiliate('id');
		}
		
		?>
			<script>function affiliate_change(){return "<?php echo $output ?>";}</script>
		<?php	
	}
	
	public static function get_affiliate($option)
	{
		$option = strtolower($option);
		$output = null;

		global $tapfiliate;
		$affiliates = $tapfiliate;
		
		if(is_array($affiliates))
		{
			if($option == 'id')
			{
				$output = $affiliates['id'];
			}
			if($option == 'name')
			{
				$output = $affiliates['firstname'].' '.$affiliates['lastname'];
			}
			if($option == 'email')
			{
				$output = $affiliates['email'];
			}		
		}
		return $output;
	}	
	public static function affiliate_archive_permalink($url)
	{
		if(self::valid_affiliate())
		{
			if(in_the_loop())
			{
				$url .= '?ref='.sanitize_text_field($_GET['ref']);
			}
		}
		return $url;
	}
	public static function get_affiliate_webhook($query)
	{
		global $tapfiliate;
		if($query->is_main_query() && !isset($tapfiliate))
		{
			$affiliate = null;
			$api_key = get_option('tapfiliate');
			$output = null;
			
			if(isset($_GET['ref']))
			{
				$affiliate = sanitize_text_field($_GET['ref']);
			}
			if(isset($_POST['affiliate']))
			{
				$affiliate = sanitize_text_field($_POST['affiliate']);
			}
			
			if($api_key != null && $affiliate != null)
			{
				$headers = array('Api-Key: '. $api_key, 'content-type: application/json');
				$url = 'https://api.tapfiliate.com/1.6/affiliates/';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_ENCODING, '');
				curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
				$result = curl_exec($ch);
				curl_close($ch);
				$array = json_decode($result, true);
							
				//remove this if Retrieve an affiliate is fixed
				if(is_array($array))
				{
					if(count($array) > 0)
					{
						for($x = 0; $x < count($array); $x++)
						{
							if($affiliate == $array[$x]['id'])
							{
								$output = $array[$x];
							}
						}
						if(is_array($output))
						{
							$GLOBALS['tapfiliate'] = $output;
						}
					}
				}
			}			
		}
	}
	public static function affiliate_hash()
	{
		//id.total
		$string = self::get_affiliate('id').'~'.dynamicpackages_Public::currency_format(dynamicpackages_Public::total());
		$string  = wp_hash($string);
		return $string;
	}
	public static function affiliate_mail($vars)
	{
		$headers = array('Content-type: text/html');
		$subject = __('New Commission', 'dynamicpackages');
		$subject .= ' - '.get_bloginfo('name');
		$body = __('Hello', 'dynamicpackages').' '.$vars['affiliate_name'].',<br/>';
		$body .= __('New Commission', 'dynamicpackages');
		$body .= ' '.__('will be added to your account', 'dynamicpackages').'!';
		wp_mail($vars['affiliate_email'], esc_html($subject), $body, $headers);		
	}	
	
	public static function has_affiliate()
	{
		if(isset($_GET['ref']))
		{
			global $tapfiliate;
			
			if(isset($tapfiliate))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}	
	
	public static function valid_affiliate()
	{
		if(self::has_affiliate())
		{
			$booking_affiliate = strtolower(sanitize_text_field($_GET['ref']));
			$booking_affiliate = preg_replace('/@/', '', $booking_affiliate);
			$affiliate = self::get_affiliate('id');
			$affiliate = preg_replace('/@/', '', $affiliate);
			
			if($booking_affiliate == $affiliate && $affiliate != '')
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	public static function valid_affiliate_hash()
	{
		$string = $_POST['affiliate'].'~'.$_POST['affiliate_total'];
		$string = wp_hash($string);
		
		if($string == $_POST['affiliate_hash'])
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public static function validate_tapfiliate_api_key()
	{
		$api_key = get_option('tapfiliate');
		
		if($api_key == null)
		{
			$return = true;
		}
		else
		{
			$headers = array('Api-Key: '. $api_key, 'content-type: application/json');
			$url = esc_url('https://api.tapfiliate.com/1.6/programs/');
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_ENCODING, '');
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			$result = curl_exec($ch);
			curl_close($ch);
			$array = json_decode($result);
			$return = false;
			
			if(is_array($array))
			{
				if(count($array) > 0)
				{
					$return = true;
				}
			}			
		}
		return $return;
	}
	
}
