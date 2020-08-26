<?php

class add_to_calendar
{
	function __construct()
	{
		add_action( 'wp_enqueue_scripts', array('add_to_calendar', 'scripts'));
	}
	public static function scripts()
	{
		if(self::is_valid())
		{
			$url = 'https://addevent.com/libs/atc/1.6.1/atc.min.js';
			wp_enqueue_script('add_to_calendar', $url, '', '', true);
			wp_add_inline_style('minimalLayout', self::css());
		}
	}
	public static function is_valid()
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
			if(isset($_POST['departure_date']))
			{
				$date = $_POST['departure_date'];
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
	
	public static function show()
	{
		if(self::is_valid())
		{
			
			if(isset($_POST['description']))
			{
				$description = sanitize_text_field($_POST['description']);
			}
			else
			{
				$description = get_the_excerpt();
			}	

			if(isset($_POST['departure_date']) && isset($_POST['booking_hour']))
			{
				$calendar = sanitize_text_field($_POST['departure_date']).' '.sanitize_text_field($_POST['booking_hour']);
			}
			else
			{
				$calendar = dynamicpackages_Public::date().' '.dynamicpackages_Public::hour();
			}
			
			global $post;
			ob_start();
			?>
				<div class="bottom-20 addevent_container">
					<div title="<?php echo __('Add to Calendar', 'dynamicpackages'); ?>" class="addeventatc">
						<?php echo __('Add to Calendar', 'dynamicpackages'); ?>
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
			echo $output;				
		}
	}
	
	public static function css()
	{
		return '.addeventatc{visibility: hidden;}.addevent_container{height: 42px;}';
	}
}

?>