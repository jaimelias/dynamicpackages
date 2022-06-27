<?php

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

			if(!empty(package_field('package_event_date')))
			{
				$date = package_field('package_event_date');
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
		if($this->is_valid())
		{
			global $post;
			
			if(isset($post))
			{

				$text = __('Add to calendar', 'dynamicpackages');

				ob_start();
				?>
					<div class="bottom-20 addevent_container">
						<div title="<?php echo esc_attr($text); ?>" class="addeventatc">
							<?php esc_html_e($text); ?>
							<span class="start"><?php esc_html_e(sanitize_text_field($_REQUEST['booking_date']).' '.dy_utilities::hour()); ?></span>
							<span class="timezone"><?php esc_html_e(get_option('timezone_string')); ?></span>
							<span class="title"><?php esc_html_e($post->post_title); ?></span>
							<span class="description"><?php esc_html_e(apply_filters('dy_description', null)); ?></span>
							<span class="location"><?php esc_html_e(package_field('package_start_address')); ?></span>
						</div>
					</div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
				return $output;	
			}
		}
	}
	
	public function css()
	{
		return '.addeventatc{visibility: hidden;}.addevent_container{height: 42px;}';
	}
}

?>