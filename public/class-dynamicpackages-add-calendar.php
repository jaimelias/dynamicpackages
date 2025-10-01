<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class dy_Add_To_Calendar
{
	function __construct()
	{
		$this->init();
	}
	public function init()
	{
		add_filter('dy_add_to_calendar', array(&$this, 'show'));
		add_action( 'wp_enqueue_scripts', array(&$this, 'scripts'));		
	}
	public function scripts()
	{
		if($this->is_valid())
		{
			
			$url = 'https://addevent.com/libs/atc/1.6.1/atc.min.js';
			wp_enqueue_script('dy_add_to_calendar', $url, '', '', true);
			wp_add_inline_style('minimalLayout', $this->css());
		}
	}
	public function is_valid()
	{
		$output = false;
		
		if(is_singular('packages'))
		{
			$date = '';
			$hour = '';
			$output = false;
			
			if(isset($_REQUEST['booking_date']))
			{
				$date = $_REQUEST['booking_date'];
			}
			if(isset($_REQUEST['booking_hour']))
			{
				$hour = $_REQUEST['booking_hour'];
			}

			if(!empty(package_field('package_start_hour')))
			{
				$hour = package_field('package_start_hour');
			}	
			if(!empty($hour) && !empty($date))
			{
				$output = true;
			}			
		}

		return $output;
	}
	
	public function show()
	{
		if ( ! $this->is_valid() ) {
			return '';
		}

		global $post;

		if ( ! ($post instanceof WP_Post) ) {
			return '';
		}

		$label        = __('Add to calendar', 'dynamicpackages'); // translatable
		$label_attr   = esc_attr($label);
		$label_html   = esc_html($label);

		$booking_date = sanitize_text_field($_REQUEST['booking_date'] ?? '');
		$start_text   = esc_html($booking_date . ' ' . dy_utilities::hour());

		$timezone     = esc_html(get_option('timezone_string'));
		$title        = esc_html($post->post_title);
		$description  = esc_html(apply_filters('dy_description', null));
		$location     = esc_html(package_field('package_start_address'));

		$html = sprintf(
			'<div class="bottom-20 addevent_container">
				<div title="%s" class="addeventatc">
					%s
					<span class="start">%s</span>
					<span class="timezone">%s</span>
					<span class="title">%s</span>
					<span class="description">%s</span>
					<span class="location">%s</span>
				</div>
			</div>',
			$label_attr,
			$label_html,
			$start_text,
			$timezone,
			$title,
			$description,
			$location
		);

		// Match the original behavior of returning buffered HTML (without extra output)
		return $html;
	}

	
	public function css()
	{
		return '.addeventatc{visibility: hidden;}.addevent_container{height: 42px;}';
	}
}

?>