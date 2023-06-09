<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamic_Core_Admin {
    
    public function __construct()
    {
		$this->plugin_dir_url_file = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_url( __DIR__ );
        $this->plugin_name = 'Dynamic Core';
        $this->slug = 'dy-core';
        $this->setting_id = 'dy_core_settings';
        $this->section_company = 'dy_core_section_company';
        $this->section_security = 'dy_core_section_security';
        $this->section_analytics = 'dy_core_section_analytics';
        add_action('admin_init', array(&$this, 'settings_init'), 1);
        add_action('admin_menu', array(&$this, 'admin_menu'), 1);
		add_action('admin_head', array(&$this, 'args'));
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles'));
    }


	public function enqueue_scripts()
	{
		global $dy_load_picker_scripts;

		if(isset($dy_load_picker_scripts))
		{
			load_picker_scripts($this->plugin_dir_url_file, $this->plugin_dir);
		}
	}
	public function enqueue_styles()
	{
		global $dy_load_picker_scripts;

		if(isset($dy_load_picker_scripts))
		{
			load_picker_styles($this->plugin_dir_url_file);
		}
	}

    public function args()
    {
        $args = array(
            'pluginUrl' => $this->plugin_dir_url_file,
            'lang' => current_language()
        );

        echo '<script>const dyCoreArgs = '.json_encode($args).';</script>';
    }	

    public function settings_init()
    {

		global $polylang;
		$default_language = default_language();

        //settings - company
		register_setting($this->setting_id, 'dy_email', 'sanitize_email');
		register_setting($this->setting_id, 'dy_phone', 'esc_html');
		register_setting($this->setting_id, 'dy_address', 'esc_html');
		register_setting($this->setting_id, 'dy_tax_id', 'esc_html');

		register_setting($this->setting_id, 'dy_whatsapp', 'intval');




		//settings - security
		register_setting($this->setting_id, 'dy_recaptcha_site_key', 'sanitize_user');
		register_setting($this->setting_id, 'dy_recaptcha_secret_key', 'sanitize_user');
        register_setting($this->setting_id, 'dy_cloudflare_api_token', 'esc_html');

		//settings - analytics
		register_setting($this->setting_id, 'dy_ipgeolocation_api_token', 'sanitize_user');
		register_setting($this->setting_id, 'dy_gtag_tracking_id', 'sanitize_user');
		register_setting($this->setting_id, 'dy_gtm_tracking_id', 'sanitize_user');
		register_setting($this->setting_id, 'dy_facebook_pixel_id', 'sanitize_user');

        //section
		add_settings_section($this->section_company, __('Company', 'dynamicpackages' ), '', $this->setting_id);
		add_settings_section($this->section_security, __('Security', 'dynamicpackages' ), '', $this->setting_id);
		add_settings_section($this->section_analytics, __('Analytics', 'dynamicpackages' ), '', $this->setting_id);

        //fields

		add_settings_field( 
			'dy_email', 
			esc_html(__( 'Email', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_company,
			array('name' => 'dy_email', 'type' => 'email')
		);

		add_settings_field( 
			'dy_phone', 
			esc_html(__( 'Phone', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_company,
			array('name' => 'dy_phone', 'type' => 'text')
		);
		
		add_settings_field( 
			'dy_whatsapp', 
			esc_html(__( 'Whatsapp', 'dynamicpackages' ).' '. strtoupper($default_language)), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_company,
			array('name' => 'dy_whatsapp', 'type' => 'number')
		);

		if(isset($polylang))
		{
			$languages = get_languages();

			for($x = 0; $x < count($languages); $x++)
			{
				$lang = $languages[$x];

				if($default_language !== $lang)
				{
					register_setting($this->setting_id, 'dy_whatsapp_' . $lang, 'intval');

					add_settings_field( 
						'dy_whatsapp_' . $lang, 
						esc_html(__( 'Whatsapp', 'dynamicpackages' ).' '. strtoupper($lang)), 
						array(&$this, 'settings_input'), 
						$this->setting_id, 
						$this->section_company,
						array('name' => 'dy_whatsapp_' . $lang, 'type' => 'number')
					);			
				}
			}
		}

		add_settings_field( 
			'dy_address', 
			esc_html(__( 'Address', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_company,
			array('name' => 'dy_address', 'type' => 'text')
		);

		add_settings_field( 
			'dy_tax_id', 
			esc_html(__( 'Tax Identification ID', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_company,
			array('name' => 'dy_tax_id', 'type' => 'text')
		);

		add_settings_field( 
			'dy_recaptcha_site_key', 
			esc_html(__( 'Recaptcha Site Key', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_security,
			array('name' => 'dy_recaptcha_site_key', 'url' => 'https://www.google.com/recaptcha/admin') 
		);	

		add_settings_field( 
			'dy_recaptcha_secret_key', 
			esc_html(__( 'Recaptcha Secret Key', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_security,
			array('name' => 'dy_recaptcha_secret_key', 'url' => 'https://www.google.com/recaptcha/admin') 
		);
		
		add_settings_field( 
			'dy_cloudflare_api_token', 
			esc_html(__( 'Cloudflare API Token', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_security,
			array('name' => 'dy_cloudflare_api_token') 
		);

		add_settings_field( 
			'dy_ipgeolocation_api_token', 
			__( 'IpGeolocation.io API Token', 'dynamicpackages' ), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_analytics,
			array('name' => 'dy_ipgeolocation_api_token', 'url' => 'http://ipgeolocation.io/') 
		);

		add_settings_field( 
			'dy_gtag_tracking_id', 
			__( 'Google - Analytics GA4 (GTAG)', 'dynamicpackages' ), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_analytics,
			array('name' => 'dy_gtag_tracking_id', 'url' => 'https://analytics.google.com/') 
		);

		add_settings_field( 
			'dy_gtm_tracking_id', 
			__( 'Google - Global Tag Manager (GMT)', 'dynamicpackages' ), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_analytics,
			array('name' => 'dy_gtm_tracking_id', 'url' => 'https://tagmanager.google.com/') 
		);

		add_settings_field( 
			'dy_facebook_pixel_id', 
			__( 'Facebook Pixel ID', 'dynamicpackages' ), 
			array(&$this, 'settings_input'), 
			$this->setting_id, 
			$this->section_analytics,
			array('name' => 'dy_facebook_pixel_id', 'url' => 'https://www.facebook.com/business/tools/meta-pixel') 
		);
    }

	public function settings_input($arr){
        $name = $arr['name'];
        $url = (array_key_exists('url', $arr)) ? '<a target="_blank" rel="noopener noreferrer" href="'.esc_url($arr['url']).'">?</a>' : null;
        $type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
        $value = ($type == 'checkbox') ? 1 : get_option($name);
        ?>
            <input 
                type="<?php echo esc_attr($type); ?>" 
                name="<?php echo esc_attr($name); ?>" 
                id="<?php echo esc_attr($name); ?>" 
                value="<?php echo esc_attr($value); ?>" <?php echo ($type == 'checkbox') ? checked( 1, get_option($name), false ) : null; ?> /> <span><?php echo $url; ?></span>
    <?php }	

    public  function admin_menu()
    {
        add_menu_page(
            $this->plugin_name, 
            $this->plugin_name, 
            'manage_options',  
            $this->slug, 
            array(&$this, 'settings_page'), 
            'dashicons-building'
        );
    }

	public function settings_page()
	{ 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php echo esc_html($this->plugin_name); ?></h1>	
			<?php
				settings_fields( $this->setting_id );
				do_settings_sections( $this->setting_id );
				submit_button();
			?>			
		</form>
		
		<?php
	}

}

?>