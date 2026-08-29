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
		add_filter('dy_add_to_calendar', array($this, 'show'));
		add_action( 'wp_enqueue_scripts', array($this, 'scripts'));		
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
		if(!is_singular('packages'))
		{
			return false;
		}

		$date = dy_utilities::booking_date();
		$hour = dy_utilities::hour();
		
		return !empty($hour) && !empty($date);
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

		
		$hour = dy_utilities::hour();
		$booking_date = secure_request('booking_date');

		if(empty($hour) || empty($booking_date)) return '';

		$label        = __('Add to calendar', 'dynamicpackages'); 

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
			esc_attr($label),
			esc_html($label),
			esc_html($booking_date . ' ' . dy_utilities::hour()),
			esc_html(get_option('timezone_string')),
			esc_html($post->post_title),
			esc_html((string) apply_filters('dy_description', null)),
			esc_html(package_field('package_start_address'))
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