<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://jaimelias.com
 * @since      1.0.0
 *
 * @package    dynamicpackages
 * @subpackage dynamicpackages/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    dynamicpackages
 * @subpackage dynamicpackages/admin
 * @author     Jaimelías <jaimelias@about.me>
 */
class dy_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_editor_style(plugin_dir_url( __FILE__ ) . 'css/dynamicpackages-admin.css');
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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
		self::handsontable();

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dynamicpackages-admin.css', array(), time(), 'all' );
		
		wp_enqueue_style( 'picker-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.css', array(), '', 'all' );
		wp_enqueue_style( 'picker-date-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.date.css', array(), '', 'all' );
		wp_enqueue_style( 'picker-time-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.time.css', array(), '', 'all' );	
	
	}
	
	public static function handsontable()
	{
		wp_enqueue_style( 'handsontableCss', plugin_dir_url( __FILE__ ) . 'css/handsontable.full.min.css', array(), time(), 'all' );
		wp_enqueue_script( 'handsontableJS', plugin_dir_url( __FILE__ ) . 'js/handsontable.full.min.js', array('jquery'), time(), true );
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

		//recaptcha
		register_setting('dy_settings', 'captcha_site_key', 'sanitize_user');
		register_setting('dy_settings', 'captcha_secret_key', 'sanitize_user');

		//mandrill
		register_setting('dy_settings', 'sendgrid_api_key', 'sanitize_user');
		register_setting('dy_settings', 'sendgrid_username', 'sanitize_text_field');
		register_setting('dy_settings', 'sendgrid_email', 'sanitize_text_field');
		
		//settings
		register_setting('dy_settings', 'dy_packages_breadcrump', 'intval');
		register_setting( 'dy_settings', 'primary_gateway', 'esc_html');
		register_setting( 'dy_settings', 'dy_tax', 'intval');
		register_setting( 'dy_settings', 'dy_webhook', 'esc_url');
		register_setting( 'dy_settings', 'dy_quote_webhook', 'esc_url');
		
		//ip geolocation
		register_setting('dy_settings', 'ipgeolocation', 'sanitize_user');	


		add_settings_section(
			'dy_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
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
			'primary_gateway', 
			esc_html(__( 'Payment Gateway', 'dynamicpackages' )), 
			array(&$this, 'text_field_dynamicpackages_0_render'), 
			'dy_settings', 
			'dy_gateways_section', array('primary_gateway', dy_utilities::get_gateways())
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

		add_settings_field( 
			'sendgrid_api_key', 
			esc_html(__( 'Sendgrid Api Key', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_integrations_section',
			array('name' => 'sendgrid_api_key') 
		);

		add_settings_field( 
			'sendgrid_username', 
			esc_html(__( 'Sendgrid Username', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_integrations_section',
			array('name' => 'sendgrid_username') 
		);	

		add_settings_field( 
			'sendgrid_email', 
			esc_html(__( 'Sendgrid Email', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_integrations_section',
			array('name' => 'sendgrid_email') 
		);			
		
		add_settings_field( 
			'ipgeolocation', 
			esc_html(__( 'IPGeolocation API Key', 'dynamicpackages' )), 
			array(&$this, 'settings_input'), 
			'dy_settings', 
			'dy_settings_section',
			array('name' => 'ipgeolocation', 'url' => 'https://app.ipgeolocation.io/auth/login') 
		);	
	}

	public static function settings_input($arr){
			$name = esc_html($arr['name']);
			$url = (array_key_exists('url', $arr)) ? '<a href="'.esc_url($arr['url']).'">?</a>' : null;
			$type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
		?>
		<input type="<?php echo $type; ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_html(get_option($name)); ?>" /> <span><?php echo $url; ?></span>

	<?php }
	
	public static function text_field_dynamicpackages_0_render($gateways) { 
		$options = get_option($gateways[0]);
		?>
		
		<select name="<?php echo esc_html($gateways[0]); ?>">
			<option value="0" <?php echo ($options == '0' ) ? 'selected' : ''; ?> ><?php echo esc_html(__('None', 'dynamicpackages')); ?></option>		
			<?php
				for($x = 0; $x < count($gateways[1]); $x++)
				{
					$selected = '';
					
					if($options == $gateways[1][$x]['name'] )
					{
						$selected = 'selected';
					}
					
					echo '<option '.esc_html($selected).' value="'.esc_html($gateways[1][$x]['name']).'">'.esc_html($gateways[1][$x]['name']).'</option>';
				}
			?>
		</select>
		<?php
	}
	
	public static function dy_packages_breadcrump_render() { 
		global $polylang;
		$options = get_option( 'dy_packages_breadcrump' );
		$args = array();
		$args['post_parent'] = 0;
		$args['post_type'] = 'page';
		$args['posts_per_page'] = 500;
		$args['orderby'] = 'title';
		$args['order'] = 'ASC';
		$args['post__not_in'] = array('-'.get_option('page_on_front'));
		
		if(isset($polylang))
		{
			$args['lang'] = array(pll_default_language());
		}
		
		$wp_query = new WP_Query($args);
		?>
		<select name='dy_packages_breadcrump'>
			<option value="<?php echo esc_html(get_option('page_on_front')); ?>" <?php selected($options, get_option('page_on_front')); ?>><?php echo __('Home').': '.get_the_title(get_option('page_on_front')); ?></option>
			<?php if($wp_query->have_posts()): ?>
				<?php while ($wp_query->have_posts()): $wp_query->the_post(); ?>
					<option value="<?php echo get_the_ID();?>" <?php selected($options, get_the_ID()); ?>><?php echo get_the_title();?></option>
				<?php endwhile; wp_reset_postdata(); ?>
			<?php endif; ?>
		</select>
		<?php
	}
	

	public static function sanitize_thumb_width( $input ) {
		$valid = array();
		$valid['text_field_dynamicpackages_3'] = intval(sanitize_text_field( $input['text_field_dynamicpackages_3'] ));
		if($valid['text_field_dynamicpackages_3'] < 100)
		{
			$valid['text_field_dynamicpackages_3'] = 100;
		}
		return $valid;
	}
	public static function sanitize_thumb_height( $input ) {
		$valid = array();
		$valid['text_field_dynamicpackages_4'] = intval(sanitize_text_field( $input['text_field_dynamicpackages_4'] ));
		if($valid['text_field_dynamicpackages_4'] < 100)
		{
			$valid['text_field_dynamicpackages_4'] = 100;
		}
		return $valid;
	}

	public static function settings_page()
	{ 
		?><div class="wrap">
		<form action='options.php' method='post'>
			
			<h1><?php esc_html(_e("Dynamicpackages", "dynamicpackages")); ?></h1>	
			<?php
			settings_fields( 'dy_settings' );
			do_settings_sections( 'dy_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}	
	
}
