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
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->validate_recaptcha = dy_validators::validate_recaptcha();
	}

	private function load_dependencies() {

		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Parsedown.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dynamicpackages-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-dynamicpackages-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-search.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'gateways/gateways.php';	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-metaboxes.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-metapost.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-tax.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-post-type.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/forms.php';			
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/tables.php';			
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/add_to_calendar.php';			
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/validators.php';						
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/shortcodes.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/reviews.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'mailer/mailer.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-ical.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-json.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-utilities.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/form-actions.php';	

		$this->loader = new dynamicpackages_Loader();

	}

	private function set_locale() {

		$plugin_i18n = new dynamicpackages_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name());
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}


	private function define_admin_hooks() {

		$plugin_admin = new dy_Admin($this->get_plugin_name(), $this->get_version());
		$plugin_metaboxes = new dy_Metaboxes();
		$plugin_metapost = new dy_Metapost();
		$plugin_post_type = new dy_Post_Type();
		$plugin_reviewes = new Dynamic_Packages_Reviews();
		$plugins_tax = new dy_Tax_Mod();
	}

	private function define_public_hooks() {

		$plugin_public = new Dynamic_Packages_Public();
		$plugin_settings = new Dynamic_Packages_Gateways();	
		$plugin_search = new Dynamic_Packages_Search();
		$plugin_reviewes = new Dynamic_Packages_Reviews();
		$plugins_shortcodes = new Dynamic_Packages_Shortcodes();
		$plugins_forms = new Dynamic_Packages_Forms();
		$plugins_json = new Dynamic_Packages_JSON();
		$plugins_ical = new Dynamic_Packages_Ical();
		$plugin_form_actions = new Dynamic_Packages_Actions();
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
