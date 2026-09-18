<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Package_Page {

    public function __construct($version)
    {
		$this->version = $version;
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );

        add_action('wp', [$this, 'load_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('entry_content_class', [$this, 'entry_content_class']);
    }

    public function enqueue_scripts()
    {
        if(is_package_page())
        {
			global $dy_load_picker_scripts;

            wp_enqueue_script('dynamicpackages-page', $this->plugin_dir_url_file . 'js/dynamicpackages-package-page.js', ['jquery', 'landing-cookies', 'dy-core-utilities', 'picker-js'], $this->version, true );
            wp_add_inline_script('dynamicpackages-page', $this->enabled_times(), 'before');
        }
    }

	public function enabled_times()
	{	

		$output = [];

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
					$output['min'] = array(intval(date('H', $min_hour)), intval(date('i', $min_hour)));
				}
				if(!empty($max_hour))
				{
					$max_hour = strtotime($max_hour);
					$output['max'] = array(intval(date('H', $max_hour)), intval(date('i', $max_hour)));			
				}
			}
			
			return 'const dyPackageEnabledTimes = '.json_encode($output).';';
		}
	}

	public function load_scripts($query)
	{
		if(isset($query->query_vars['packages']))
		{
			if($query->query_vars['packages'])
			{
				if(is_package_page())
				{
					$GLOBALS['dy_load_picker_scripts'] = true;
				}
			}
		}
	}

	public function entry_content_class($class) {
		return '';
	}

}

?>