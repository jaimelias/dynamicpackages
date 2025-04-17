<?php


if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Post_Types
{
	// Register Custom Post Type
	
	function __construct()
	{
		$this->plugin_dir_file = plugin_dir_url( __FILE__ );
		add_action('init', array(&$this, 'package_post_type'));
		add_action('init', array(&$this, 'register_taxonomies'), 10);

		//forces custom taxonomies to polylang. 
		//Strings inside add_tax_to_pll or get_taxonomies_arr must must not be translated to avoid _load_textdomain_just_in_time error
		add_filter( 'pll_get_taxonomies', array(&$this, 'add_tax_to_pll'), 10, 2 );
	}
	
	public function get_taxonomies_arr()
	{
		//forces custom taxonomies to polylang. 
		//Strings inside add_tax_to_pll or get_taxonomies_arr must must not be translated to avoid _load_textdomain_just_in_time error

		return array(
			'package_location' => array(
				'name' => __( 'Locations'),
				'singular_name' => __( 'Location'),
				'emoji' => '🌎',
				'public' => true
			),
			'package_category' => array(
				'name' => __( 'Categories'),
				'singular_name' => __( 'Category'),
				'emoji' => '🏷️',
				'public' => true
			),
			'package_included' => array(
				'name' => __( 'Included'),
				'singular_name' => __( 'Included'),
				'emoji' => '🍹',
				'public' => false		
			),
			'package_not_included' => array(
				'name' => __( 'Not Included'),
				'singular_name' => __( 'Not Included'),
				'emoji' => '❌',
				'public' => false
			),
			'package_terms_conditions' => array(
				'name' => __( 'Terms & Conditions'),
				'singular_name' => __( 'Terms & Conditions'),
				'emoji' => '📄',
				'public' => true
			),
			'package_add_ons' => array(
				'name' => __( 'Add-ons'),
				'singular_name' => __( 'Add-on'),
				'emoji' => '🤑',
				'public' => false
			)
		);
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
			'filter_items_list' => __( 'Filter package list', 'dynamicpackages' ),
		);

		$icon_url = $this->plugin_dir_file . 'assets/rocket.svg';
		
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
			'menu_icon' => $icon_url
		);
		
		register_post_type( 'packages', $args );

	}

	public function register_taxonomies(){


		$taxonomies = $this->get_taxonomies_arr();

		foreach($taxonomies as $key => $value)
		{
			$singular = $value['singular_name'];
			$plural = $value['name'];
			$emoji = $value['emoji'];
			$public = $value['public'];
			$labels = $value;
			$labels['search_items'] = sprintf(__('Search %s', 'dynamicpackages'), $plural);
			$labels['all_items'] = sprintf(__('All %s', 'dynamicpackages'), $plural);
			$labels['parent_item'] = sprintf(__('Parent %s', 'dynamicpackages'), $singular);
			$labels['parent_item_colon'] = sprintf(__('Parent %s', 'dynamicpackages'), $singular);
			$labels['edit_item'] = sprintf(__('Edit %s', 'dynamicpackages'), $singular);
			$labels['update_item'] = sprintf(__('Update %s', 'dynamicpackages'), $singular);
			$labels['add_new_item'] = sprintf(__('Add New %s', 'dynamicpackages'), $singular);
			$labels['new_item_name'] = sprintf(__('New %s Name', 'dynamicpackages'), $singular);
			$labels['menu_name'] = $emoji.' '.$plural;

			$args = array(
				'labels' => $labels,
				'hierarchical' => true,
				'public' => $public,
				'show_in_rest'				=> true,
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud' => true
			);

			register_taxonomy($key, array( 'packages' ), $args );
		}
	}


	public function add_tax_to_pll($taxonomies, $is_settings)
	{


		//forces custom taxonomies to polylang. 
		//Strings inside add_tax_to_pll or get_taxonomies_arr must must not be translated to avoid _load_textdomain_just_in_time error

		if(!is_array($taxonomies))
		{
			return false;
		}

		$custom_taxonomies = $this->get_taxonomies_arr();

		foreach($custom_taxonomies as $k => $v)
		{
			$taxonomies[$k] = $k;
		}

		return $taxonomies;
	}

}

?>
