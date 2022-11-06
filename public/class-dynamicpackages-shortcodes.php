<?php

if ( !defined( 'WPINC' ) ) exit;

class Dynamicpackages_Shortcodes {
	
	public function __construct()
	{
		$this->plugin_dir_path = plugin_dir_path(__DIR__);
		$this->init();
	}
	
	public function init()
	{
		add_shortcode('packages', array(&$this, 'package_shortcode_full'));
		add_shortcode('package_filter', array(&$this, 'package_filter'));
		add_shortcode('package_contact', array(&$this, 'contact'));
		add_shortcode('package_categories', array(&$this, 'categories'));
		add_shortcode('package_locations', array(&$this, 'locations'));
		add_action('dy_contact_inquiry_textarea', array(&$this, 'inquiry_textarea'));
	}

	public function contact($content = null)
	{

		$GLOBALS['dy_has_form'] = true;

		ob_start();
		require_once $this->plugin_dir_path.'public/partials/quote-form.php';
		$output = ob_get_contents();
		ob_end_clean();	
		
		return $output;		
	}

	public function inquiry_textarea()
	{
		if(!is_singular('packages'))
		{
			?>
			<p>
				<label for="inquiry"><?php echo esc_html(__('Message', 'dynamicpackages')); ?></label>
				<textarea id="inquiry" name="inquiry" required></textarea>
			</p>
			<?php
		}
	}
	
	public function package_filter($content = '')
	{
		return apply_filters('dy_form_filter_form', $content);
	}	
	
	public function package_shortcode_full($attr, $content = '')
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
					$package_main = (get_option('dy_breadcrump')) ? get_option('dy_breadcrump') : get_option('page_on_front');
					
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
		
		if(isset($_GET['location']))
		{
			if($_GET['location'] != null)
			{
				$loc_imp = sanitize_text_field($_GET['location']);
			}
		}
		if(isset($_GET['category']))
		{
			if($_GET['category'] != null)
			{
				$cat_imp = sanitize_text_field($_GET['category']);
			}
		}
		if(isset($_GET['sort']))
		{
			if($_GET['sort'] != null)
			{
				$sort_imp = sanitize_text_field($_GET['sort']);
			}
		}	
		
		ob_start();
		require($this->plugin_dir_path . 'public/partials/archive.php');
		$content = ob_get_contents();
		ob_end_clean();	

		return $content;
	}

	public function categories()
	{
		return dy_utilities::get_tax_list('package_category');
	}
	public function locations()
	{
		return dy_utilities::get_tax_list('package_location');
	}
}
?>