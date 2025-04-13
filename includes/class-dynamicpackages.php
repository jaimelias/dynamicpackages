<?php

if ( !defined( 'WPINC' ) ) exit;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link http://jaimelias.com
 * @since 1.0.0
 *
 * @package dynamicpackages
 * @subpackage dynamicpackages/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since 1.0.0
 * @package dynamicpackages
 * @subpackage dynamicpackages/includes
 * @author Jaimelías <jaimelias@about.me>
 */
#[AllowDynamicProperties]
class dynamicpackages {


	public function __construct() {

		$this->plugin_name = 'Dynamic Packages';
		$this->plugin_id = 'dynamicpackages';
		$this->version = '1.0.9';
	}

	public function run() {
		$this->load_dependencies();
		$this->define_utility_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_gateteways_hooks();
	}

	private function load_dependencies() {

		$dir = plugin_dir_path(dirname( __FILE__ ));
		
		require_once $dir . 'vendor/autoload.php';
		//require_once $dir . 'includes/class-dynamicpackages-loader.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-parsedown.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-validators.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-utilities.php';
		require_once $dir . 'includes/class-dynamicpackages-form-actions.php';
		require_once $dir . 'includes/class-dynamicpackages-reviews.php'; //optimized
		
		//public
		require_once $dir . 'public/class-dynamicpackages-public.php';
		require_once $dir . 'public/class-dynamicpackages-forms.php';
		require_once $dir . 'public/class-dynamicpackages-shortcodes.php'; //optimized
		require_once $dir . 'public/class-dynamicpackages-tables.php'; //optimized	
		require_once $dir . 'public/class-dynamicpackages-json.php';
		require_once $dir . 'public/class-dynamicpackages-add-calendar.php';
		require_once $dir . 'public/class-dynamicpackages-package-page.php';
		require_once $dir . 'public/class-dynamicpackages-booking-page.php';
		require_once $dir . 'public/class-dynamicpackages-confirmation-page.php';
		require_once $dir . 'public/class-dynamicpackages-export-post-types.php';
		
		//admin
		require_once $dir . 'admin/class-dynamicpackages-admin.php';
		require_once $dir . 'admin/class-dynamicpackages-add-ons.php';
		require_once $dir . 'admin/class-dynamicpackages-metaboxes.php';
		require_once $dir . 'admin/class-dynamicpackages-metapost.php';
		require_once $dir . 'admin/class-dynamicpackages-post-type.php';

		//gateways
		require_once $dir . 'gateways/class-dynamicpackages-gateways.php';
	}

	public function define_utility_hooks()
	{
		$this->reviews = new Dynamicpackages_Reviews();
	}

	private function define_admin_hooks() 
	{
		new Dynamicpackages_Admin($this->plugin_id, $this->plugin_name, $this->version);
		new Dynamicpackages_Metaboxes();
		new Dynamicpackages_Metapost();
		new Dynamicpackages_Taxonomy_Add_Ons();
		new Dynamicpackages_Post_Types();
	}

	private function define_public_hooks() 
	{
		new Dynamicpackages_Public($this->version);
		new Dynamicpackages_Tables();
		new Dynamicpackages_Shortcodes();
		new Dynamicpackages_Forms();
		new Dynamicpackages_JSON($this->reviews);
		new Dynamicpackages_Actions();
		new Dynamicpackages_Package_Page($this->version);
		new Dynamicpackages_Booking_Page($this->version);
		new Dynamicpackages_Confirmation_Page($this->version);
		new Dynamicpackages_Export_Post_Types($this->version);
	}

	private function define_gateteways_hooks()
	{
		new Dynamicpackages_Gateways($this->plugin_id);
	}


}
