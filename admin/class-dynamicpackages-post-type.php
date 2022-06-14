<?php


class Dynamicpackages_Post_Types
{
	// Register Custom Post Type
	
	function __construct()
	{
		add_action('init', array(&$this, 'package_post_type'));
		add_action('init', array(&$this, 'register_taxonomies'), 10);
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

	public function __echo_null()
	{
		echo '';
	}
	
	public function register_taxonomies(){
		$taxonomies = array(
			'package_location' => array(
				'name' => __( 'Locations', 'dynamicpackages'),
				'singular_name' => __( 'Location', 'dynamicpackages')
			),
			'package_category' => array(
				'name' => __( 'Categories', 'dynamicpackages'),
				'singular_name' => __( 'Category', 'dynamicpackages')
			),
			'package_included' => array(
				'name' => __( 'Included', 'dynamicpackages'),
				'singular_name' => __( 'Included', 'dynamicpackages')
			),
			'package_not_included' => array(
				'name' => __( 'Not Included', 'dynamicpackages'),
				'singular_name' => __( 'Not Included', 'dynamicpackages')
			),
			'package_terms_conditions' => array(
				'name' => __( 'Terms & Conditions', 'dynamicpackages'),
				'singular_name' => __( 'Terms & Conditions', 'dynamicpackages')
			),
			'package_add_ons' => array(
				'name' => __( 'Add-ons', 'dynamicpackages'),
				'singular_name' => __( 'Add-on', 'dynamicpackages')
			),
			'package_provider' => array(
				'name' => __( 'Providers', 'dynamicpackages'),
				'singular_name' => __( 'Provider', 'dynamicpackages')
			)
		);

		foreach($taxonomies as $key => $value)
		{
			$singular = $value['singular_name'];
			$plural = $value['name'];
			$labels = $value;
			$labels['search_items'] = sprintf(__('Search %s', 'dynamicpackages'), $plural);
			$labels['all_items'] = sprintf(__('All %s', 'dynamicpackages'), $plural);
			$labels['parent_item'] = sprintf(__('Parent %s', 'dynamicpackages'), $singular);
			$labels['parent_item_colon'] = sprintf(__('Parent %s', 'dynamicpackages'), $singular);
			$labels['edit_item'] = sprintf(__('Edit %s', 'dynamicpackages'), $singular);
			$labels['update_item'] = sprintf(__('Update %s', 'dynamicpackages'), $singular);
			$labels['add_new_item'] = sprintf(__('Add New %s', 'dynamicpackages'), $singular);
			$labels['new_item_name'] = sprintf(__('New %s Name', 'dynamicpackages'), $singular);
			$labels['menu_name'] = '📋 '.$plural;

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

			register_taxonomy($key, array( 'packages' ), $args );
		}
	}
}

?>
