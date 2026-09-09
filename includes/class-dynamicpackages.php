<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class dynamicpackages {


	public function __construct() {

		$this->plugin_name = 'Dynamic Packages';
		$this->plugin_id = 'dynamicpackages';
		$this->version = is_local_host() ? time() : DYNAMICPACKAGES_VERSION;
		
		$this->load_dependencies();

		new Dynamicpackages_Post_Types();
		
		add_action('init', [$this, 'load_dynamicpackage_textdomain'], PHP_INT_MAX);

		$this->initialize_plugin();
	}

	public function initialize_plugin(){
		
		$this->define_utility_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_gateteways_hooks();	
	}

	private function load_dependencies() {

		$dir = plugin_dir_path(dirname( __FILE__ ));

		//dynamic core scripts
		require_once $dir . 'dy-core/loader.php';
		require_once $dir . 'includes/class-dynamicpackages-parsedown.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-validators.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-utilities.php';
		require_once $dir . 'includes/class-dynamicpackages-form-actions.php';
		require_once $dir . 'includes/class-dynamicpackages-reviews.php'; //optimized
		require_once $dir . 'includes/class-dynamicpackages-fields.php'; //optimized

		
		//public
		require_once $dir . 'public/class-dynamicpackages-public.php';
		require_once $dir . 'public/class-dynamicpackages-forms.php';
		require_once $dir . 'public/class-dynamicpackages-shortcodes.php'; //optimized
		require_once $dir . 'public/class-dynamicpackages-tables.php'; //optimized	
		require_once $dir . 'public/class-dynamicpackages-wp-json.php';
		require_once $dir . 'public/class-dynamicpackages-ld-json.php';
		require_once $dir . 'public/class-dynamicpackages-add-calendar.php';
		require_once $dir . 'public/class-dynamicpackages-package-page.php';
		require_once $dir . 'public/class-dynamicpackages-booking-page.php';
		require_once $dir . 'public/class-dynamicpackages-confirmation-page.php';
		require_once $dir . 'public/class-dynamicpackages-location-category.php';
		require_once $dir . 'public/class-dynamicpackages-training-data.php';
		
		//admin
		require_once $dir . 'admin/class-dynamicpackages-admin.php';
		require_once $dir . 'admin/class-dynamicpackages-add-ons.php';
		require_once $dir . 'admin/class-dynamicpackages-metaboxes.php';
		require_once $dir . 'admin/class-dynamicpackages-metapost.php';
		require_once $dir . 'admin/class-dynamicpackages-post-type.php';
		require_once $dir . 'admin/class-dynamicpackages-extended-package-fields.php';

		//gateways
		require_once $dir . 'gateways/class-dynamicpackages-gateways.php';

	}

	public function define_utility_hooks()
	{
		$this->reviews = new Dynamicpackages_Reviews();
		new Dynamicpackages_LD_JSON($this->reviews);
	}

	private function define_admin_hooks() 
	{
		new Dynamicpackages_Admin($this->plugin_id, $this->plugin_name, $this->version);
		new Dynamicpackages_Metaboxes();
		new Dynamicpackages_Metapost();
		new Dynamicpackages_Taxonomy_Add_Ons();

	}

	private function define_public_hooks() 
	{
		new Dynamicpackages_Public($this->version);
		new Dynamicpackages_Tables();
		new Dynamicpackages_Shortcodes();
		new Dynamicpackages_Forms();
		new Dynamicpackages_Actions();
		new Dynamicpackages_Package_Page($this->version);
		new Dynamicpackages_Booking_Page($this->version);
		new Dynamicpackages_Confirmation_Page($this->version);
		new Dynamicpackages_Location_Category($this->version);
		new Dynamicpackages_Export_Post_Types($this->version);
		new Dynamicpackages_WP_JSON();
	}

	private function define_gateteways_hooks()
	{
		new Dynamicpackages_Gateways($this->plugin_id);
	}


	public function load_dynamicpackage_textdomain() {

		$domain = 'dynamicpackages';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	
		
		$dir = dirname( plugin_basename( __FILE__ ) ) . '/languages';
		
		load_plugin_textdomain(
			$domain,
			false,
			$dir
		);
	}

}
