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


add_action( 'plugins_loaded', function() {
    new dynamicpackages();
});