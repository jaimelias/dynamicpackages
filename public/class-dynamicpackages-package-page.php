<?php

if ( !defined( 'WPINC' ) ) exit;


class Dynamicpackages_Package_Page {

    public function __construct()
    {
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );

        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    }

    public function enqueue_scripts()
    {
        if(is_singular('packages') && !is_booking_page() && !is_checkout_page())
        {
            wp_enqueue_script('dynamicpackages-page', $this->plugin_dir_url_file . 'js/dynamicpackages-package-page.js', array( 'jquery', 'landing-cookies', 'dy-core-utilities'), time(), true );
            wp_add_inline_script('dynamicpackages-page', $this->enabled_times(), 'before');
        }
    }

	public function enabled_times()
	{	

		$output = array();

		if(is_singular('packages'))
		{
			$by_hour = intval(package_field('package_by_hour'));		
			$min_hour = package_field('package_min_hour');		
			$max_hour = package_field('package_max_hour');
			
			if($by_hour === 1)
			{				
				if(!empty($min_hour))
				{
					$min_hour = strtotime($min_hour);
					$output[] = array(intval(date('H', $min_hour)), intval(date('i', $min_hour)));
				}
				if(!empty($max_hour))
				{
					$max_hour = strtotime($max_hour);
					$output[] = array(intval(date('H', $max_hour)), intval(date('i', $max_hour)));			
				}
			}
			
			return 'const dyPackageEnabledTimes = '.json_encode($output).';';
		}
	}
}

?>