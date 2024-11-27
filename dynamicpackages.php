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


class Dynamicpackages_Fields
{
    private static $cache = [];

    public static function get($name, $this_id = null)
    {
        global $post;
        $week_days = dy_utilities::get_week_days_abbr();
        $languages = get_languages();

        // Define base excluded fields
        $excludes = array_merge(
            [
                'package_occupancy_chart',
                'package_price_chart',
                'package_min_persons',
                'package_max_persons',
                'package_disabled_dates',
                'package_disabled_num',
                'package_child_title',
                'package_free',
                'package_discount',
                'package_increase_persons',
                'package_disabled_dates_api',
            ],
            dy_validators::package_type_transport() ? [
                'package_check_in_hour',
                'package_start_hour',
                'package_check_in_end_hour',
                'package_return_hour',
                'package_start_address',
                'package_return_address',
            ] : [],
            array_map(fn($day) => "package_week_day_surcharge_$day", $week_days),
            array_map(fn($day) => "package_day_$day", $week_days)
        );

        // Determine the correct post ID
        if ($this_id === null && isset($post)) {
            $this_id = $post->ID;

            if (property_exists($post, 'post_parent') && $post->post_parent > 0) {
                // Add language-specific excludes
                $excludes = array_merge(
                    $excludes,
                    array_map(fn($lang) => "package_child_title_$lang", $languages)
                );

                // Use parent post ID if the name is not excluded
                if (!in_array($name, $excludes)) {
                    $this_id = $post->post_parent;
                }
            }
        }

        // Use cache if available
        $cache_key = $name . '_' . $this_id;
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        // Retrieve the field value
        $this_field = get_post_meta($this_id, $name, true);

        // Store the value in cache
        return self::$cache[$cache_key] = $this_field;
    }
}





function package_field($name, $this_id = null)
{
	return Dynamicpackages_Fields::get($name, $this_id = null);
}

function is_booking_page()
{
	return dy_validators::is_booking_page();
}

function is_checkout_page()
{
	return dy_validators::is_checkout_page();
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