<?php

	global $post;
				
	if($post->post_parent > 0)
	{
		echo '<link rel="canonical" href="'.esc_url(get_permalink($post->post_parent)).'"/>';			
	}

	if(is_booking_page() || dynamicpackages_Validators::validate_checkout())
	{	
		$head = '<meta name="robots" content="noindex, nofollow" />';
		echo $head;
	}
	
?>	