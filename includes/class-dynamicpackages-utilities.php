<?php

class dy_utilities {
	
	public static function get_gateways()
	{
		$path = plugin_dir_path(__DIR__).'gateways/matrix/';
		$files = scandir($path);
		$gateways = array();
		for($x = 0; $x < count($files); $x++)
		{
			if(preg_match('/\.php/', $files[$x]))
			{
				require($path.'/'.$files[$x]);				
				array_push($gateways, $gate);
			}
		}
		return $gateways;
	}

	public static function get_this_gateway()
	{
		$gateways = dy_utilities::get_gateways();
		$output = array();
		
		for($x = 0; $x < count($gateways); $x++)
		{
			if($gateways[$x]['name'] == get_option('primary_gateway'))
			{
				$output = $gateways[$x];
			}
		}
		return $output;
	}	
	
	public static function currency_format($amount)
	{
		return number_format(floatval($amount), 2, '.', '');
	}

	public static function currency_symbol()
	{
		return '$';
	}
	
	public static function currency_name()
	{
		return 'USD';
	}	
	
}