<?php


class dy_Post_Type
{
	// Register Custom Post Type
	
	function __construct()
	{
		add_action('init', array(&$this, 'package_post_type'));
		add_action('init', array(&$this, 'location_taxonomy'));
		add_action('init', array(&$this, 'category_taxonomy'));
		add_action('init', array(&$this, 'included_taxonomy'));
		add_action('init', array(&$this, 'not_included_taxonomy'));
		add_action('init', array(&$this, 'terms_conditions_taxonomy'));
		add_action('init', array(&$this, 'add_ons'));
	}
	
	public function package_post_type() {

		$labels = array(
			'name' => __( 'Packages', 'dynamicpackages' ),
			'singular_name' => __( 'Package', 'dynamicpackages' ),
			'menu_name' => __( 'Packages', 'dynamicpackages' ),
			'name_admin_bar' => __( 'Package', 'dynamicpackages' ),
			'parent_item_colon' => __( 'Parent Package:', 'dynamicpackages' ),
			'all_items' => __( 'All Packages', 'dynamicpackages' ),
			'add_new_item' => __( 'Add New Package', 'dynamicpackages' ),
			'add_new' => __( 'Add New', 'dynamicpackages' ),
			'new_item' => __( 'New Package', 'dynamicpackages' ),
			'edit_item' => __( 'Edit Package', 'dynamicpackages' ),
			'update_item' => __( 'Update Package', 'dynamicpackages' ),
			'view_item' => __( 'View Package', 'dynamicpackages' ),
			'search_items' => __( 'Search Package', 'dynamicpackages' ),
			'not_found' => __( 'Not found', 'dynamicpackages' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'dynamicpackages' ),
			'locations_list' => __( 'Packages list', 'dynamicpackages' ),
			'locations_list_navigation' => __( 'Packages list navigation', 'dynamicpackages' ),
			'filter_items_list' => __( 'Filter locations list', 'dynamicpackages' ),
		);
		
		$args = array(
			'label' => __( 'Package', 'dynamicpackages' ),
			'description' => __( 'Package Description', 'dynamicpackages' ),
			'labels' => $labels,
			'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'excerpt', 'comments'),
			'hierarchical' => true,
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 5,
			'show_in_rest' => true,
			'show_in_admin_bar' => true,
			'show_in_nav_menus' => true,
			'can_export' => true,
			'has_archive' => false,
			'exclude_from_search' => false,
			'publicly_queryable' => true,
			'capability_type' => 'page',
			'menu_icon' => 'dashicons-tickets-alt',
		);
		
		register_post_type( 'packages', $args );

	}
	
	public function location_taxonomy() {
		
		$labels = array(
			'name' => __( 'Locations', 'dynamicpackages'),
			'singular_name' => __( 'Location', 'dynamicpackages')
			);		

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'public' => true,
			'show_in_rest'				=> true,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud' => true
		);
		register_taxonomy( 'package_location', array( 'packages' ), $args );
	}
	public function category_taxonomy() {

		$labels = array(
			'name' => __( 'Categories', 'dynamicpackages'),
			'singular_name' => __( 'Category', 'dynamicpackages')
			);
		
		$args = array(
			'labels' => $labels,		
			'hierarchical' => true,
			'public' => true,
			'show_ui' => true,
			'show_in_rest'				=> true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud' => true
		);
		register_taxonomy( 'package_category', array( 'packages' ), $args );
	}

	public function included_taxonomy() {

		$labels = array(
			'name' => __( 'Included', 'dynamicpackages'),
			'singular_name' => __( 'Included', 'dynamicpackages')
			);
		
		$args = array(
			'labels' => $labels,		
			'hierarchical' => true,
			'public' => true,
			'show_in_rest'				=> true,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud' => true
		);
		register_taxonomy( 'package_included', array( 'packages' ), $args );
	}	

	public function not_included_taxonomy() {

		$labels = array(
			'name' => __( 'Not Included', 'dynamicpackages'),
			'singular_name' => __( 'Not Included', 'dynamicpackages')
			);
		
		$args = array(
			'labels' => $labels,		
			'hierarchical' => true,
			'public' => true,
			'show_ui' => true,
			'show_in_rest'				=> true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud' => true
		);
		register_taxonomy( 'package_not_included', array( 'packages' ), $args );
	}

	public function terms_conditions_taxonomy() {

		$labels = array(
			'name' => __( 'Terms & Conditions', 'dynamicpackages'),
			'singular_name' => __( 'Terms & Conditions', 'dynamicpackages')
			);
		
		$args = array(
			'labels' => $labels,		
			'hierarchical' => true,
			'public' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_rest'				=> true,
			'show_in_nav_menus' => true,
			'show_tagcloud' => true
		);
		register_taxonomy( 'package_terms_conditions', array( 'packages' ), $args );
	}
	
	public function add_ons() {

		$labels = array(
			'name' => __( 'Add-ons', 'dynamicpackages'),
			'singular_name' => __( 'Add-on', 'dynamicpackages')
			);
		
		$args = array(
			'labels' => $labels,		
			'hierarchical' => true,
			'public' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_rest'				=> true,
			'show_in_nav_menus' => true,
			'show_tagcloud' => true
		);
		register_taxonomy('package_add_ons', array( 'packages' ), $args );		
	}
}

?>
