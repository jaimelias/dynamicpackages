<?php


if ( !defined( 'WPINC' ) ) exit;


/**
 * Fired during plugin activation
 *
 * @link       http://jaimelias.com
 * @since      1.0.0
 *
 * @package    dynamicpackages
 * @subpackage dynamicpackages/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    dynamicpackages
 * @subpackage dynamicpackages/includes
 * @author     Jaimelías <jaimelias@about.me>
 */

class dynamicpackages_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		dy_core_schedule_rewrite_flush();

		if (class_exists('Dy_Yappy_V2_Store')) {
			Dy_Yappy_V2_Store::install();
		}
	}

}
