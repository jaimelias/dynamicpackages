<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Package_Page {

    public function __construct($version)
    {
		$this->version = $version;
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );

        add_action('wp', [$this, 'load_scripts'], 10, 0);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('entry_content_class', [$this, 'entry_content_class']);
    }

    public function enqueue_scripts()
    {
        if(is_package_page() && !dy_errors::has_errors())
        {
            wp_enqueue_script('dynamicpackages-page', $this->plugin_dir_url_file . 'js/dynamicpackages-package-page.js', ['jquery', 'landing-cookies', 'dy-core-utilities', 'picker-js'], $this->version, true );
            wp_add_inline_script('dynamicpackages-page', $this->enabled_times(), 'before');
        }
    }

	public function enabled_times(): ?string
	{
		if (!is_singular('packages')) {
			return null;
		}

		$output = [];
		if (Dynamicpackages_Schedule::uses_hour_range()) {
			foreach (['min' => 'package_min_hour', 'max' => 'package_max_hour'] as $bound => $field) {
				$seconds = hour_to_seconds(package_field($field));
				if ($seconds !== false) {
					$output[$bound] = [intdiv($seconds, 3600), intdiv($seconds % 3600, 60)];
				}
			}
		}
		return 'const dyPackageEnabledTimes = ' . wp_json_encode($output) . ';';
	}

	public function load_scripts(): void
	{
		if (!is_package_page() || dy_errors::has_errors()) {
			return;
		}

		$GLOBALS['dy_load_picker_scripts'] = true;
	}

	public function entry_content_class($class) {
		return '';
	}

}

?>
