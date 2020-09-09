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
			
			if(isset($_GET['booking_date']))
			{
				$date = $_GET['booking_date'];
			}
			if(isset($_GET['booking_hour']))
			{
				$hour = $_GET['booking_hour'];
			}
			if(isset($_POST['booking_date']))
			{
				$date = $_POST['booking_date'];
			}
			if(isset($_POST['booking_hour']))
			{
				$hour = $_POST['booking_hour'];
			}			
			if(package_field('package_event_date') != '')
			{
				$date = package_field('package_event_date');
			}
			if(package_field('package_departure_hour') != '')
			{
				$hour = package_field('package_departure_hour');
			}	
			if($hour != '' && $date != '')
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
			
			$description = (isset($_POST['description'])) ? sanitize_text_field($_POST['description']) : get_the_excerpt();

			$calendar = (isset($_POST['booking_date']) && isset($_POST['booking_hour'])) ? sanitize_text_field($_POST['booking_date']).' '.sanitize_text_field($_POST['booking_hour']) : dy_Public::date().' '.dy_utilities::hour();
			
			global $post;
			ob_start();
			?>
				<div class="bottom-20 addevent_container">
					<div title="<?php echo esc_html(__('Add to Calendar', 'dynamicpackages')); ?>" class="addeventatc">
						<?php echo esc_html(__('Add to Calendar', 'dynamicpackages')); ?>
						<span class="start"><?php echo esc_html($calendar); ?></span>
						<span class="timezone"><?php echo esc_html(get_option('timezone_string')); ?></span>
						<span class="title"><?php echo esc_html($post->post_title); ?></span>
						<span class="description"><?php echo esc_html($description); ?></span>
						<span class="location"><?php echo esc_html(package_field('package_departure_address')); ?></span>
					</div>
				</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			return $output;				
		}
	}
	
	public function css()
	{
		return '.addeventatc{visibility: hidden;}.addevent_container{height: 42px;}';
	}
}

?>