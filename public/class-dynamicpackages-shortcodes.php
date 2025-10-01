<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
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
				<textarea id="inquiry" name="inquiry" class="required"></textarea>
			</p>
			<?php
		}
	}
	
	public function package_filter($content = '')
	{
		return apply_filters('dy_package_filter_form_cb', $content);
	}	
	
	public function package_shortcode_full($attr, $content = '')
	{
		global $polylang;
		
		if(is_array($attr))
		{
			if(array_key_exists('category', $attr))
			{
				if(!empty($attr['category']))
				{
					$cat_imp = explode(",", $attr['category']);
				}
			}
			if(array_key_exists('location', $attr))
			{
				if(!empty($attr['location']))
				{
					$loc_imp = explode(",", $attr['location']);			
				}
			}
			if(array_key_exists('cols', $attr))
			{
				if(!empty($attr['cols']))
				{
					$col_imp = $attr['cols'];
				}
			}
			if(array_key_exists('display', $attr))
			{
				if(!empty($attr['display']))
				{
					$dis_imp = $attr['display'];
				}
			}
			if(array_key_exists('all', $attr))
			{
				if(!empty($attr['all']))
				{
					$all_imp = true;
				}
			}
			if(array_key_exists('pagination', $attr))
			{
				if(!empty($attr['pagination']))
				{
					if(filter_var($attr['pagination'], FILTER_VALIDATE_BOOLEAN)  === true)
					{
						$pagination_imp = true;
					}
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
					
					if(get_dy_id() == $package_main)
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
		
		if(!empty(secure_get('location')))
		{
			$loc_imp = secure_get('location');
		}
		if(!empty(secure_get('category')))
		{
			$cat_imp = secure_get('category');
		}
		if(!empty(secure_get('sort')))
		{
			$sort_imp = secure_get('sort');
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