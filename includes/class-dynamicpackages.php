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

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var dynamicpackages_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'dynamicpackages';
		$this->version = '1.0.0';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->mailer = new dynamicpackages_Mailer();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - dynamicpackages_Loader. Orchestrates the hooks of the plugin.
	 * - dynamicpackages_i18n. Defines internationalization functionality.
	 * - dynamicpackages_Admin. Defines all hooks for the admin area.
	 * - dynamicpackages_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/sitemap.php';
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/validators.php';						
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/shortcodes.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/reviews.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'mailer/mailer.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-ical.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-json.php';		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicpackages-utilities.php';		

		$this->loader = new dynamicpackages_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the dynamicpackages_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function set_locale() {

		$plugin_i18n = new dynamicpackages_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name());
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new dynamicpackages_Admin($this->get_plugin_name(), $this->get_version());
		$plugin_metaboxes = new dynamicpackages_Metaboxes();
		$plugin_metapost = new dynamicpackages_Metapost();
		$plugin_post_type = new dynamicpackages_Post_Type();
		$plugin_reviewes = new dynamicpackages_Reviews();
		$plugins_tax = new dynamicpackages_Taxmodifier();
		
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');		
		$this->loader->add_action('admin_init', $plugin_admin, 'register_polylang_strings');		
		$this->loader->add_action('add_meta_boxes', $plugin_metaboxes, 'package_add_meta_box');
		$this->loader->add_action('save_post', $plugin_metapost, 'package_save', 10, 3);

		//settings
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_settings_page', 99);
		$this->loader->add_action('admin_init', $plugin_admin, 'settings_init', 1);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function define_public_hooks() {

		$plugin_public = new dynamicpackages_Public();
		$plugin_settings = new dynamicpackages_Gateways();	
		$plugin_search = new dynamicpackages_Search();
		$plugin_add_to_calendar = new add_to_calendar();
		$plugin_reviewes = new dynamicpackages_Reviews();
		$plugins_shortcodes = new dynamicpackages_Shortcodes();
		$plugins_json = new dynamicpackages_Json();
		$plugins_ical = new dynamicpackages_Ical();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0.0
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since 1.0.0
	 * @return dynamicpackages_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since 1.0.0
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
