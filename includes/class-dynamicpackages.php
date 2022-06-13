<?php

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


class dynamicpackages {


	public function __construct() {

		$this->plugin_name = 'dynamicpackages';
		$this->version = '1.0.0';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_utility_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_gateteways_hooks();
		$this->validate_recaptcha = dy_validators::validate_recaptcha();
	}



	private function load_dependencies() {

		$dir = plugin_dir_path(dirname( __FILE__ ));
		
		require_once $dir . 'vendor/autoload.php';
		require_once $dir . 'includes/class-dynamicpackages-loader.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-parsedown.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-i18n.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-validators.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-mailer.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-utilities.php';
		require_once $dir . 'includes/class-dynamicpackages-form-actions.php';
		require_once $dir . 'includes/class-dynamicpackages-reviews.php'; //optimized
		
		//public
		require_once $dir . 'public/class-dynamicpackages-public.php';
		require_once $dir . 'public/class-dynamicpackages-forms.php';
		require_once $dir . 'public/class-dynamicpackages-shortcodes.php'; //optimized
		require_once $dir . 'public/class-dynamicpackages-tables.php'; //optimized
		require_once $dir . 'public/class-dynamicpackages-ical.php';		
		require_once $dir . 'public/class-dynamicpackages-json.php';
		require_once $dir . 'public/class-dynamicpackages-add-calendar.php';
		
		//admin
		require_once $dir . 'admin/class-dynamicpackages-admin.php';
		require_once $dir . 'admin/class-dynamicpackages-providers.php'; //optimized but needs admin-fields-class
		require_once $dir . 'admin/class-dynamicpackages-add-ons.php';
		require_once $dir . 'admin/class-dynamicpackages-metaboxes.php';
		require_once $dir . 'admin/class-dynamicpackages-metapost.php';
		require_once $dir . 'admin/class-dynamicpackages-post-type.php';

		//gateways
		require_once $dir . 'gateways/class-dynamicpackages-gateways.php';

		$this->loader = new dynamicpackages_Loader();
	}

	private function set_locale() {

		$plugin_i18n = new dynamicpackages_i18n();
		$plugin_i18n->set_domain($this->plugin_name);
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	public function define_utility_hooks()
	{
		$this->reviews = new Dynamic_Packages_Reviews();
	}

	private function define_admin_hooks() 
	{
		new Dynamic_Packages_Admin($this->plugin_name, $this->version);
		new Dynamic_Packages_Metaboxes();
		new Dynamic_Packages_Metapost();
		new Dynamic_Packages_Taxonomy_Providers();
		new Dynamic_Packages_Taxonomy_Add_Ons();
		new Dynamic_Packages_Post_Types();
	}

	private function define_public_hooks() 
	{
		new Dynamic_Packages_Public();
		new Dynamic_Packages_Tables();
		new Dynamic_Packages_Shortcodes();
		new Dynamic_Packages_Forms();
		new Dynamic_Packages_JSON($this->reviews);
		new Dynamic_Packages_Ical();
		new Dynamic_Packages_Actions();
	}

	private function define_gateteways_hooks()
	{
		new Dynamic_Packages_Gateways();
	}

	public function run() {
		$this->loader->run();
	}
}
