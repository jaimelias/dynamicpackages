<?php

class dy_Shortcodes {
	
	function __construct()
	{
		add_shortcode( 'packages', array('dy_Shortcodes', 'package_shortcode_full') );
		add_shortcode('page_title', array('dy_Shortcodes', 'render_title'));
		add_shortcode('page_excerpt', array('dy_Shortcodes', 'render_excerpt'));
		add_shortcode('package_locations', array('dy_Shortcodes', 'get_locations'));
		add_shortcode('package_categories', array('dy_Shortcodes', 'get_categories'));
		add_shortcode('package_filter', array('dy_Shortcodes', 'package_filter_form'));		
	}
	
	public static function package_filter_form($content = '')
	{
		ob_start();
		dynamicpackages_Forms::package_filter_form();
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}	
	
	public static function package_shortcode_full($attr, $content = "")
	{
		if(is_array($attr))
		{
			if(array_key_exists('category', $attr))
			{
				if($attr['category'] != null)
				{
					$cat_imp = $attr['category'];
				}
			}
			if(array_key_exists('location', $attr))
			{
				if($attr['location'] != null)
				{
					$loc_imp = $attr['location'];				
				}
			}
			if(array_key_exists('cols', $attr))
			{
				if($attr['cols'] != null)
				{
					$col_imp = $attr['cols'];
				}
			}
			if(array_key_exists('display', $attr))
			{
				if($attr['display'] != null)
				{
					$dis_imp = $attr['display'];
				}
			}
			if(array_key_exists('all', $attr))
			{
				if($attr['all'] != null)
				{
					$all_imp = true;
				}
			}
			if(array_key_exists('pagination', $attr))
			{
				if($attr['pagination'] != null)
				{
					$pagination_imp = true;
				}
			}
			if(array_key_exists('sortby', $attr))
			{
				if($attr['sortby'] != null)
				{
					$sort_imp = $attr['sortby'];
				}			
			}			
		}
		
		if(isset($_GET['package_location']))
		{
			if($_GET['package_location'] != null)
			{
				$loc_imp = sanitize_text_field($_GET['package_location']);
			}
		}
		if(isset($_GET['package_category']))
		{
			if($_GET['package_category'] != null)
			{
				$cat_imp = sanitize_text_field($_GET['package_category']);
			}
		}
		if(isset($_GET['package_sort']))
		{
			if($_GET['package_sort'] != null)
			{
				$sort_imp = sanitize_text_field($_GET['package_sort']);
			}
		}	
		
		ob_start();
		require(plugin_dir_path( __FILE__ ) . '/archive.php');
		$content = ob_get_contents();
		ob_end_clean();	

		return $content;
	}	
	
	
	public static function render_title()
	{
		if(is_singular())
		{
			$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
			
			$output = '<h1 data-id="page-title" class="entry-title';
			
			if(isset($_GET['search']) || isset($_GET['package_category']) || isset($_GET['package_location']) || $paged > 1)
			{
				$output .= ' small light';
			}
			
			$output .= '">'.esc_html(get_the_title()).'</h1>';
			
			return $output;
		}
	}
	public static function render_excerpt()
	{
		$output = null;
		
		if(is_singular() && has_excerpt())
		{
			if(get_the_excerpt() != null)
			{
				if(is_singular('packages'))
				{
					$output = '<p class="large strong text-muted">'.get_the_excerpt().'</p>';
				}
				else
				{
					$output = '<p>'.get_the_excerpt().'</p>';
				}
			}
		}
		
		return $output;
	}	
	
	public static function get_locations($attr, $content = "")
	{
		if(is_array($attr))
		{
			$classes = array();
			
			if(array_key_exists('ul', $attr))
			{
				$classes['ul'] = $attr['ul'];
			}
			if(array_key_exists('li', $attr))
			{
				$classes['li'] = $attr['li'];
			}
			if(array_key_exists('a', $attr))
			{
				$classes['a'] = $attr['a'];
			}			
			
			$content = dy_Public::get_all_locations($classes);
		}
		
		return $content;
	}
	public static function get_categories($attr, $content = "")
	{
		if(is_array($attr))
		{
			$classes = array();
			
			if(array_key_exists('ul', $attr))
			{
				$classes['ul'] = $attr['ul'];
			}
			if(array_key_exists('li', $attr))
			{
				$classes['li'] = $attr['li'];
			}
			if(array_key_exists('a', $attr))
			{
				$classes['a'] = $attr['a'];
			}			
			
			$content = dy_Public::get_categories($classes);
		}
		
		return $content;
	}	
	
//class end
}
?>