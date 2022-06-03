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


	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {

		$this->plugin_name = 'dynamicpackages';
		$this->version = '1.0.0';
		$this->load_dependencies();
		$this->set_locale();
		$this->preload();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->validate_recaptcha = dy_validators::validate_recaptcha();
	}

	public function preload()
	{
		$this->reviews = new Dynamic_Packages_Reviews();
	}

	private function load_dependencies() {

		$file = dirname( __FILE__ );
		$dir = plugin_dir_path(dirname( __FILE__ ));
		
		require_once $dir . 'vendor/autoload.php';
		require_once $dir . 'includes/class-dynamicpackages-loader.php';
		require_once $dir . 'includes/class-dynamicpackages-parsedown.php';
		require_once $dir . 'includes/class-dynamicpackages-i18n.php';
		require_once $dir . 'includes/class-dynamicpackages-search.php';
		require_once $dir . 'includes/class-dynamicpackages-metaboxes.php';
		require_once $dir . 'includes/class-dynamicpackages-metapost.php';
		require_once $dir . 'includes/class-dynamicpackages-tax.php';
		require_once $dir . 'includes/class-dynamicpackages-post-type.php';
		require_once $dir . 'includes/class-dynamicpackages-validators.php';
		require_once $dir . 'includes/class-dynamicpackages-mailer.php';		
		require_once $dir . 'includes/class-dynamicpackages-ical.php';		
		require_once $dir . 'includes/class-dynamicpackages-json.php';		
		require_once $dir . 'includes/class-dynamicpackages-utilities.php';
		require_once $dir . 'includes/class-dynamicpackages-shortcodes.php';
		require_once $dir . 'includes/class-dynamicpackages-form-actions.php';
		require_once $dir . 'includes/class-dynamicpackages-add-calendar.php';
		require_once $dir . 'admin/class-dynamicpackages-admin.php';
		require_once $dir . 'public/class-dynamicpackages-public.php';
		require_once $dir . 'public/partials/forms.php';			
		require_once $dir . 'public/partials/tables.php';
		require_once $dir . 'includes/class-dynamicpackages-reviews.php';
		require_once $dir . 'gateways/gateways.php';	

		$this->loader = new dynamicpackages_Loader();

	}

	private function set_locale() {

		$plugin_i18n = new dynamicpackages_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name());
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}


	private function define_admin_hooks() {

		new dy_Admin($this->get_plugin_name(), $this->get_version());
		new dy_Metaboxes();
		new dy_Metapost();
		new dy_Post_Type();
		new dy_Tax_Mod();
	}

	private function define_public_hooks() {

		new Dynamic_Packages_Public();
		new Dynamic_Packages_Gateways();	
		new Dynamic_Packages_Search();
		new Dynamic_Packages_Tables();
		new Dynamic_Packages_Shortcodes();
		new Dynamic_Packages_Forms();
		new Dynamic_Packages_JSON($this->reviews);
		new Dynamic_Packages_Ical();
		new Dynamic_Packages_Actions();
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}

}
