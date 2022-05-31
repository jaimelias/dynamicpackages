<?php


if ( !defined( 'ABSPATH' ) ) exit;


class dy_Admin {


	private $plugin_name;
	private $version;


	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->init();
	}

	public function init()
	{
		add_action('admin_menu', array(&$this, 'add_settings_page'), 99);
		add_action('admin_init', array(&$this, 'settings_init'), 1);
		add_editor_style(plugin_dir_url( __FILE__ ) . 'css/dynamicpackages-admin.css');
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));		
		add_action('admin_init', array(&$this, 'register_polylang_strings'));		
	}

	public function enqueue_styles() {

		self::handsontable();

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dynamicpackages-admin.css', array(), time(), 'all' );
		
		wp_enqueue_style( 'picker-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.css', array(), '', 'all' );
		wp_enqueue_style( 'picker-date-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.date.css', array(), '', 'all' );
		wp_enqueue_style( 'picker-time-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.time.css', array(), '', 'all' );	
	
	}
	
	public static function handsontable()
	{
		wp_enqueue_style( 'handsontableCss', plugin_dir_url( __DIR__ ) . 'assets/handsontable/handsontable.full.min.css', array(), time(), 'all' );
		wp_enqueue_script( 'handsontableJS', plugin_dir_url( __DIR__ ) . 'assets/handsontable/handsontable.full.min.js', array('jquery'), '8.1.0', true );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in dynamicpackages_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The dynamicpackages_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */	
		global $typenow;
		if('packages' == $typenow)
		{			
				
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dynamicpackages-admin.js', array('jquery', 'handsontableJS'), time(), true );				
			wp_add_inline_script('dynamicpackages', self::wp_version(), 'before');
			
		//picker
			wp_enqueue_script( 'picker-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.js', array('jquery'), '', false );
			wp_enqueue_script( 'picker-date-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.date.js', array(), '', false );
			wp_enqueue_script( 'picker-time-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.time.js', array(), '', false );	
			wp_enqueue_script( 'picker-legacy', plugin_dir_url( __FILE__ ) . 'js/picker/legacy.js', array(), '', false );	
			$picker_translation = 'js/picker/translations/'.substr(get_locale(), 0, -3).'.js';
			if(file_exists(get_template_directory().$picker_translation))
			{
				wp_enqueue_script( 'picker-time-translation', plugin_dir_url( __FILE__ ). $picker_translation, array(), '', false );
			}

			
			
		}
		
	}
	
	public static function wp_version()
	{
		return 'function dy_wp_version(){return '.esc_html(intval(get_bloginfo('version'))).';}';
	}
	
	public static function register_polylang_strings()
	{
		global $polylang;
		
		if($polylang)
		{
			pll_register_string('more_packages', 'More packages');
			pll_register_string('tax_title_modifier', 'Packages in');
			pll_register_string('page_title_modifier', 'Find Packages');
			pll_register_string('checkout_page_title', 'Booking Page');
		}
	}
	
	public static function get_duration_unit()
	{
		$output = '';
		$length_unit = package_field( 'package_length_unit' );
		
		if($length_unit == 2)
		{
			$output = __('Daily', 'dynamicpackages');
		}
		else if($length_unit == 3)
		{
			$output = __('Nightly', 'dynamicpackages');
		}
		else if($length_unit == 4)
		{
			$output = __('Weekly', 'dynamicpackages');
		}
		
		return $output;	
	}
	
	public  function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', 'Dynamicpackages - Settings', '<strong>Settings</strong>', 'manage_options', 'dynamicpackages', array(&$this, 'settings_page'));
	}

	public function settings_init(  ) { 

		register_setting('dy_settings', 'dy_email', 'sanitize_email');
		register_setting('dy_settings', 'dy_phone', 'esc_html');
		register_setting('dy_settings', 'dy_whatsapp', 'intval');
		register_setting('dy_settings', 'dy_address', 'esc_html');
		register_setting('dy_settings', 'dy_tax_id', 'esc_html');
		register_setting('dy_settings', 'captcha_site_key', 'sanitize_user');
		register_setting('dy_settings', 'captcha_secret_key', 'sanitize_user');
		register_setting('dy_settings', 'dy_packages_breadcrump', 'intval');
		register_setting( 'dy_settings', 'dy_tax', 'intval');
		register_setting('dy_settings', 'dy_webhook', 'esc_url');
		register_setting('dy_settings', 'dy_quote_webhook', 'esc_url');
		register_setting('dy_settings', 'ipgeolocation', 'sanitize_user');	
		register_setting('dy_settings', 'dy_disabled_dates', 'esc_html');	
		register_setting('dy_settings', 'dy_max_disabled_dates', 'intval');	

		//list settings
		register_setting('dy_settings', 'dy_archive_hide_excerpt', 'esc_html');
		register_setting('dy_settings', 'dy_archive_hide_enabled_days', 'esc_html');
		register_setting('dy_settings', 'dy_archive_hide_start_address', 'esc_html');

		//cloudflare
		register_setting('dy_settings', 'cfp_key', 'esc_html');


		add_settings_section(
			'dy_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			'dy_settings'
		);
		
		add_settings_section(
			'dy_list_section', 
			esc_html(__( 'Package List Settings', 'dynamicpackages' )), 
			'', 
			'dy_settings'
		);
		

		
		add_settings_section(
			'dy_gateways_section', 
			esc_html(__( 'Gateway Settings', 'dynamicpackages' )), 
			'', 
			'dy_settings'
		);

		add_settings_section(
			'dy_integrations_section', 
			esc_html(__( 'Integrations Settings', 'dynamicpackages' )), 
			'', 
			'dy_settings'
		);

		add_settings_field( 
			'dy_packages_breadcrump', 
			esc_html(__( 'Package Archive Page', 'dynamicpackages' )), 
			array(&$this, 'dy_packages_breadcrump_render'), 
			'dy_settings', 
			'dy_settings_section' 
		);		
		add_settings_field( 
			'dy_tax', 
			esc_html(__( 'Tax', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'dy_tax', 'type' => 'number')
		);
		
		
		add_settings_field( 
			'dy_email', 
			esc_html(__( 'Company Email', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'dy_email', 'type' => 'email')
		);

		add_settings_field( 
			'dy_phone', 
			esc_html(__( 'Company Phone', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'dy_phone', 'type' => 'text')
		);
		
		add_settings_field( 
			'dy_whatsapp', 
			esc_html(__( 'Company Whatsapp', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'dy_whatsapp', 'type' => 'number')
		);		

		add_settings_field( 
			'dy_address', 
			esc_html(__( 'Company Address', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'dy_address', 'type' => 'text')
		);

		add_settings_field( 
			'dy_tax_id', 
			esc_html(__( 'Tax Identification Number', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'dy_tax_id', 'type' => 'text')
		);		
		

		add_settings_field( 
			'dy_webhook', 
			esc_html(__( 'Checkout Webhook URL', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_integrations_section',
			array('name' => 'dy_webhook')
		);

		add_settings_field( 
			'dy_quote_webhook', 
			esc_html(__( 'Quote Webhook URL', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_integrations_section',
			array('name' => 'dy_quote_webhook')
		);		

		add_settings_field( 
			'captcha_site_key', 
			esc_html(__( 'Recaptcha Site Key', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'captcha_site_key') 
		);	

		add_settings_field( 
			'captcha_secret_key', 
			esc_html(__( 'Recaptcha Secret Key', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'captcha_secret_key') 
		);
		
		//cloudflare

		add_settings_field( 
			'cfp_key', 
			esc_html(__( 'Cloudflare Api Token', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'cfp_key') 
		);



		add_settings_field( 
			'ipgeolocation', 
			esc_html(__( 'IPGeolocation.io', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'ipgeolocation', 'url' => 'http://ipgeolocation.io/') 
		);	

		add_settings_field( 
			'dy_max_disabled_dates', 
			esc_html(__( 'Max. Disabled Dates', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'dy_max_disabled_dates', 'type' => 'number')
		);		
		
		add_settings_field( 
			'dy_disabled_dates', 
			esc_html(__( 'Global Disabled Dates', 'dynamicpackages' )), 
			array(&$this, 'settings_hot'), 
			'dy_settings', 
			'dy_settings_section',
			array(
				'name' => 'dy_disabled_dates', 
				'value' => null,
				'max' => 'dy_max_disabled_dates', 
				'container' => 'disabled_dates',
				'headers' => array(__('From', 'dynamicpackages'), __('To', 'dynamicpackages')),
				'type' => array('date', 'date'),
			) 
		);	
		
		//dy list/archive settings
		
		add_settings_field( 
			'dy_archive_hide_excerpt', 
			esc_html(__( 'Hide Package Description (Excerpt)', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_list_section',
			array('name' => 'dy_archive_hide_excerpt', 'type' => 'checkbox')
		);
		
		add_settings_field( 
			'dy_archive_hide_enabled_days', 
			esc_html(__( 'Hide Enabled Days', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_list_section',
			array('name' => 'dy_archive_hide_enabled_days', 'type' => 'checkbox')
		);
		
		add_settings_field( 
			'dy_archive_hide_start_address', 
			esc_html(__( 'Hide Start Address', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_list_section',
			array('name' => 'dy_archive_hide_start_address', 'type' => 'checkbox')
		);
		
	}


	public static function settings_hot($arr)
	{		
		$args = array(
			'container' => $arr['container'],
			'textarea' => $arr['name'],
			'headers' => $arr['headers'],
			'type' => $arr['type'],
			'min' => $arr['max'],
			'max' => $arr['max'],
			'value' => get_option($arr['name'])
		);
		
		echo dy_utilities::handsontable($args);
	}

	public static function settings_input($arr){
			$name = $arr['name'];
			$url = (array_key_exists('url', $arr)) ? '<a href="'.esc_url($arr['url']).'">?</a>' : null;
			$type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
			$value = ($type == 'checkbox') ? 1 : get_option($name);
		?>
		<input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" <?php echo ($type == 'checkbox') ? checked( 1, get_option($name), false ) : null; ?> /> <span><?php echo $url; ?></span>

	<?php }	
	
	
	public static function dy_packages_breadcrump_render() { 
		global $polylang;
		$options = get_option('dy_packages_breadcrump');

		$args = array(
			'post_parent' => 0,
			'post_type' => 'page',
			'posts_per_page' => 500,
			'orderby' => 'title',
			'order' => 'ASC',
			'post__not_in' => array('-'.get_option('page_on_front'))
		);
		
		if(isset($polylang))
		{
			$args['lang'] = array(pll_default_language());
		}
		
		$wp_query = new WP_Query($args);
		?>
		<select name='dy_packages_breadcrump'>
			<option value="<?php echo esc_attr(get_option('page_on_front')); ?>" <?php selected($options, get_option('page_on_front')); ?>><?php echo __('Home').': '.get_the_title(get_option('page_on_front')); ?></option>
			<?php if($wp_query->have_posts()): ?>
				<?php while ($wp_query->have_posts()): $wp_query->the_post(); ?>
					<option value="<?php echo get_the_ID();?>" <?php selected($options, get_the_ID()); ?>><?php echo get_the_title();?></option>
				<?php endwhile; wp_reset_postdata(); ?>
			<?php endif; ?>
		</select>
		<?php
	}

	public static function settings_page()
	{ 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php echo esc_html(__('Dynamicpackages', 'dynamicpackages')); ?></h1>	
			<?php
				settings_fields( 'dy_settings' );
				do_settings_sections( 'dy_settings' );
				submit_button();
			?>			
		</form>
		
		<?php
	}	
	
}
