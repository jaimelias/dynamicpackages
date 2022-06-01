<?php

class Dynamic_Packages_Shortcodes {
	
	public function __construct()
	{
		$this->plugin_dir_path = plugin_dir_path(__DIR__);
		$this->init();
	}
	
	public function init()
	{
		add_shortcode('packages', array(&$this, 'package_shortcode_full'));
		add_shortcode('package_locations', array(&$this, 'get_locations'));
		add_shortcode('package_categories', array(&$this, 'get_categories'));
		add_shortcode('package_filter', array(&$this, 'package_filter'));
		add_shortcode('package_contact', array(&$this, 'contact'));
		add_action('dy_contact_inquiry_textarea', array(&$this, 'inquiry_textarea'));
	}
	
	public function contact($content = null)
	{
		$output = null;
		
		if($this->is_contact_form())
		{
			ob_start();
			require_once $this->plugin_dir_path.'public/partials/quote-form.php';
			$output = ob_get_contents();
			ob_end_clean();			
		}
		
		return $output;		
	}
	public function is_contact_form()
	{
		global $post;
		$output = false;
		
		if(isset($post))
		{
			if(has_shortcode($post->post_content, 'package_contact'))
			{
				$output = true;
			}
		}
		
		return $output;
	}
	public function inquiry_textarea()
	{
		if($this->is_contact_form())
		{
			?>
			<p><label for="inquiry"><?php echo esc_html(__('Message', 'dynamicpackages')); ?></label>
				<textarea id="inquiry" name="inquiry" required></textarea>
			</p>
			<?php
		}
	}
	
	public function package_filter($content = null)
	{
		return apply_filters('dy_package_filter_form', $content);
	}	
	
	public function package_shortcode_full($attr, $content = "")
	{
		global $polylang;
		
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
			else
			{
				if(is_tax('package_categories') || is_tax('package_location'))
				{
					$pagination_imp = true;
				}
				else
				{
					$package_main = (get_option('dy_packages_breadcrump')) ? get_option('dy_packages_breadcrump') : get_option('page_on_front');
					
					if(isset($polylang))
					{	
						if(pll_current_language() != pll_default_language())
						{
							$package_main = pll_get_post($package_main, pll_current_language());
						}
					}
					
					if(get_the_ID() == $package_main)
					{
						$pagination_imp = true;
					}					
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
		require($this->plugin_dir_path . 'public/partials/archive.php');
		$content = ob_get_contents();
		ob_end_clean();	

		return $content;
	}
	
	public function get_locations($attr, $content = "")
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
			
			$content = Dynamic_Packages_Public::get_all_locations($classes);
		}
		
		return $content;
	}
	public function get_categories($attr, $content = "")
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
			
			$content = Dynamic_Packages_Public::get_categories($classes);
		}
		
		return $content;
	}	
	
//class end
}
?>