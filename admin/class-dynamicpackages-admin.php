<?php


if ( !defined( 'WPINC' ) ) exit;


class Dynamicpackages_Admin {

	public function __construct($plugin_id, $plugin_name, $version )
	{
		$this->plugin_dir_file = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_url( __DIR__ );
		$this->plugin_name = $plugin_name;
		$this->plugin_id = $plugin_id;
		$this->version = $version;
		add_action('admin_menu', array(&$this, 'add_settings_page'));
		add_action('admin_init', array(&$this, 'settings_init'));
		add_action('admin_init', array(&$this, 'load_scripts'));
		add_editor_style($this->plugin_dir_file . 'css/dynamicpackages-admin.css');
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
	}

	public function load_scripts()
	{
		global $pagenow;

		if(isset($pagenow))
		{
			
			if(in_array($pagenow, array('post.php', 'term.php', 'admin.php')))
			{
				$GLOBALS['dy_load_picker_scripts'] = true;
			}
		}
		
	}

	public function enqueue_styles() {

		$this->handsontable();
		wp_enqueue_style( $this->plugin_id, $this->plugin_dir_file . 'css/dynamicpackages-admin.css', array(), time(), 'all' );
	}
	
	public function handsontable()
	{
		wp_enqueue_style( 'handsontableCss', $this->plugin_dir . 'assets/handsontable/handsontable.full.min.css', array(), '8.1.0', 'all' );
		wp_enqueue_script( 'handsontableJS', $this->plugin_dir . 'assets/handsontable/handsontable.full.min.js', array('jquery'), '8.1.0', true );
	}

	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_id, $this->plugin_dir_file . 'js/dynamicpackages-admin.js', array('jquery', 'handsontableJS', 'picker-js'), time(), true );
	}

	
	public  function add_settings_page()
	{
		$icon_url = $this->plugin_dir_file . 'assets/rocket.svg';
		add_menu_page($this->plugin_name, $this->plugin_name, 'manage_options',  $this->plugin_id, array(&$this, 'settings_page'), $icon_url);
	}

	public function settings_init(  ) { 

		//package
		register_setting('dy_settings', 'dy_breadcrump', 'intval');
		register_setting('dy_settings', 'dy_webhook', 'esc_url');
		register_setting('dy_settings', 'dy_quote_webhook', 'esc_url');
		register_setting('dy_settings', 'dy_disabled_dates', 'esc_html');	
		register_setting('dy_settings', 'dy_max_disabled_dates', 'intval');	

		//list settings
		register_setting('dy_settings', 'dy_archive_hide_excerpt', 'esc_html');
		register_setting('dy_settings', 'dy_archive_hide_enabled_days', 'esc_html');
		register_setting('dy_settings', 'dy_archive_hide_start_address', 'esc_html');

		//cloudflare
		register_setting('dy_settings', 'dy_cloudflare_api_token', 'esc_html');


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
			'dy_breadcrump', 
			esc_html(__( 'Package Archive Page', 'dynamicpackages' )), 
			array(&$this, 'dy_breadcrump_render'), 
			'dy_settings', 
			'dy_settings_section' 
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


	public function settings_hot($arr)
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

	public function settings_input($arr){
			$name = $arr['name'];
			$url = (array_key_exists('url', $arr)) ? '<a target="_blank" rel="noopener noreferrer" href="'.esc_url($arr['url']).'">?</a>' : null;
			$type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
			$value = ($type == 'checkbox') ? 1 : get_option($name);
		?>
		<input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" <?php echo ($type == 'checkbox') ? checked( 1, get_option($name), false ) : null; ?> /> <span><?php echo $url; ?></span>

	<?php }	
	
	
	public function dy_breadcrump_render() { 
		global $polylang;
		$options = get_option('dy_breadcrump');

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
		<select name='dy_breadcrump'>
			<option value="<?php echo esc_attr(get_option('page_on_front')); ?>" <?php selected($options, get_option('page_on_front')); ?>><?php echo __('Home').': '.get_the_title(get_option('page_on_front')); ?></option>
			<?php if($wp_query->have_posts()): ?>
				<?php while ($wp_query->have_posts()): $wp_query->the_post(); ?>
					<option value="<?php echo get_the_ID();?>" <?php selected($options, get_the_ID()); ?>><?php echo get_the_title();?></option>
				<?php endwhile; wp_reset_postdata(); ?>
			<?php endif; ?>
		</select>
		<?php
	}

	public function settings_page()
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
