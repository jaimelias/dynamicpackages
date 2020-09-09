<?php

	global $post;
	global $dy_request_invalids;
				
	if($post->post_parent > 0)
	{
		echo '<link rel="canonical" href="'.esc_url(get_permalink($post->post_parent)).'"/>';			
	}

	if(is_booking_page() || isset($dy_request_invalids) || is_checkout_page())
	{	
		$head = '<meta name="robots" content="noindex, nofollow" />';
		echo $head;
	}
	
?>	