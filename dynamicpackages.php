<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/jaimelias/dynamicpackages
 * @since             1.0.0
 * @package           dynamicpackages
 *
 * @wordpress-plugin
 * Plugin Name:       Dynamic Packages
 * Plugin URI:        https://github.com/jaimelias/dynamicpackages
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            jaimelias
 * Author URI:        https://jaimelias.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dynamicpackages
 * Domain Path:       /languages
 * GitHub Plugin URI:       jaimelias/dynamicpackages
 */

// If this file is called directly, abort.

if ( !defined( 'WPINC' ) ) exit;

function activate_dynamicpackages() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dynamicpackages-activator.php';
	dynamicpackages_Activator::activate();
}

function deactivate_dynamicpackages() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dynamicpackages-deactivator.php';
	dynamicpackages_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_dynamicpackages' );
register_deactivation_hook( __FILE__, 'deactivate_dynamicpackages' );

//dynamic core scripts
require_once plugin_dir_path( __FILE__ ) . 'dy-core/loader.php';

//init plugin
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dynamicpackages.php';



function package_field($name, $this_id = null)
{
	global $post;
	$week_days = dy_utilities::get_week_days_abbr();
	$languages = get_languages();
	$excludes = array('package_occupancy_chart', 'package_price_chart', 'package_min_persons', 'package_max_persons', 'package_disabled_dates', 'package_disabled_num', 'package_child_title', 'package_free', 'package_discount', 'package_increase_persons', 'package_disabled_dates_api');
	
	if(dy_validators::package_type_transport())
	{
		$excludes[] =  'package_check_in_hour';
		$excludes[] =  'package_start_hour';
		$excludes[] =  'package_check_in_end_hour';
		$excludes[] =  'package_return_hour';
		$excludes[] = 'package_start_address';
		$excludes[] = 'package_return_address';
	}

	for($x = 0; $x < count($week_days); $x++)
	{
		$excludes[] = 'package_week_day_surcharge_'.$week_days[$x];
		$excludes[] = 'package_day_'.$week_days[$x];
	}

	if($this_id === null)
	{	
		if(isset($post))
		{
			$this_id = $post->ID;
			
			if(property_exists($post, 'post_parent'))
			{

				for($x = 0; $x < count($languages); $x++)
				{
					$lang = $languages[$x];
					array_push($excludes, 'package_child_title_'.$lang);
				}
				
				if($post->post_parent > 0 && !in_array($name, $excludes))
				{
					$this_id = $post->post_parent;
				}
			}
		}
	}
	
	$which_var = $name.'_'.$this_id;
	global $$which_var;
	
	if(isset($$which_var))
	{
		return $$which_var;
	}
	else
	{
		$this_field = get_post_meta($this_id, $name, true);
		$GLOBALS[$which_var] = $this_field;
		return $this_field;
	}	
}

function is_booking_page()
{
	return dy_validators::is_booking_page();
}

function is_checkout_page()
{
	$output = false;
	$which_var = 'is_checkout_page';
	global $$which_var;

	if(isset($$which_var))
	{
		$output = $$which_var;
	}
	else
	{
		if(isset($_POST['dy_request']) && isset($_POST['post_id']))
		{
			$output = true;
		}

		$GLOBALS[$which_var] = $output;
	}
	
	return $output;
}

function has_package()
{
	return dy_validators::has_package();
}




function dy_strtotime($str) {
	// This function behaves a bit like PHP's StrToTime() function, but taking into account the Wordpress site's timezone
	// CAUTION: It will throw an exception when it receives invalid input - please catch it accordingly
	// From https://mediarealm.com.au/

	$tz_string = get_option('timezone_string');
	$tz_offset = get_option('gmt_offset', 0);

	if (!empty($tz_string))
	{
		// If site timezone option string exists, use it
		$timezone = $tz_string;
	}
	else if ($tz_offset == 0)
	{
		// get UTC offset, if it isn’t set then return UTC
		$timezone = 'UTC';
	}
	else
	{
		$timezone = $tz_offset;

		if(substr($tz_offset, 0, 1) != "-" && substr($tz_offset, 0, 1) != "+" && substr($tz_offset, 0, 1) != "U")
		{
			$timezone = "+" . $tz_offset;
		}
	}
	
	$datetime = new DateTime($str, new DateTimeZone($timezone));

	return $datetime->format('U');
}

function dy_date($format, $timestamp = null) {
	// This function behaves a bit like PHP's Date() function, but taking into account the Wordpress site's timezone
	// CAUTION: It will throw an exception when it receives invalid input - please catch it accordingly
	// From https://mediarealm.com.au/

	$tz_string = get_option('timezone_string');
	$tz_offset = get_option('gmt_offset', 0);

	if (!empty($tz_string)) 
	{
		// If site timezone option string exists, use it
		$timezone = $tz_string;
	} 
	elseif ($tz_offset == 0) 
	{
			// get UTC offset, if it isn’t set then return UTC
			$timezone = 'UTC';
	} else {
		$timezone = $tz_offset;

		if(substr($tz_offset, 0, 1) != "-" && substr($tz_offset, 0, 1) != "+" && substr($tz_offset, 0, 1) != "U") {
			$timezone = "+" . $tz_offset;
		}
	}

	if($timestamp === null) {
		$timestamp = time();
	}

	$datetime = new DateTime();
	$datetime->setTimestamp($timestamp);
	$datetime->setTimezone(new DateTimeZone($timezone));
	return $datetime->format($format);
}


function run_dynamicpackages() {

	$plugin = new dynamicpackages();
	$plugin->run();

}
run_dynamicpackages();
