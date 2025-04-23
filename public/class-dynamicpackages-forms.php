<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Forms
{

	private static $cache = [];

	public function __construct()
	{
		add_filter('dy_package_filter_form_cb', array(&$this, 'package_filter_form'));
		add_action('dy_package_filter_form', array(&$this, 'package_filter_form_cb'));
		add_action('dy_check_prices_form', array(&$this, 'check_prices_form'));
		add_action('dy_archive_pagination', array(&$this, 'pagination'));
	}

	public function package_filter_form_cb()
	{
		echo $this->package_filter_form();
	}
	public function package_filter_form()
	{
		global $polylang;
		
		$package_main = (get_option('dy_breadcrump')) ? get_option('dy_breadcrump') : get_option('page_on_front');
		
		if(isset($polylang))
		{	
			if(pll_current_language() != pll_default_language())
			{
				$package_main = pll_get_post($package_main, pll_current_language());

			}
		}	
		
		ob_start();
		?>
		<form id="dy_package_filter_form" data-action="<?php echo esc_attr(base64_encode(get_permalink($package_main))); ?>" data-method="get" data-home-url="<?php echo esc_url(home_lang()); ?>">
		
			<div class="pure-g gutters">

			
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="bottom-20">
						<?php echo $this->get_all_terms_select('package_location', 'location'); ?>
					</div>
				</div>	
				
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="bottom-20">
						<?php echo $this->get_all_terms_select('package_category', 'category'); ?>
					</div>
				</div>
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="bottom-20">
						<?php echo $this->sort_by(); ?>
					</div>
				</div>
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="pure-g">
						<div class="pure-u-1 pure-u-md-4-5">
							<div class="bottom-20">
								<input placeholder="<?php echo esc_attr('Search Keyword', 'dynamicpackages'); ?>" type="text" name="keywords" value="<?php if(isset($_GET['keywords'])) {echo sanitize_text_field(strtolower(substr($_GET['keywords'], 0, 25))); } ?>" />	
							</div>
						</div>
						<div class="pure-u-1 pure-u-md-1-5 small">
							<button class="block borderbox width-100 pure-button pure-button-primary" type="submit"><span class="dashicons dashicons-search"></span></button>
						</div>
					</div>
				</div>				
			</div>
				
			</form>		
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	public function sort_by()
	{
		$sort = 'any';
		
		if(isset($_GET['sort']))
		{
			if(!empty($_GET['sort']))
			{
				$sort = sanitize_text_field($_GET['sort']);
			}
		}
		
		ob_start();
		?>
			<select name="sort">
				<option value="any" <?php echo ($sort == 'any') ? 'selected':''; ?>>-- <?php esc_html_e('Sort by', 'dynamicpackages'); ?> --</option>
				<option value="new" <?php echo ($sort == 'new') ? 'selected':''; ?>><?php esc_html_e('Newest', 'dynamicpackages'); ?></option>
				<option value="low" <?php echo ($sort == 'low') ? 'selected':''; ?>><?php esc_html_e('Price', 'dynamicpackages'); ?>: <?php esc_html_e('low to high', 'dynamicpackages'); ?></option>
				<option value="high" <?php echo ($sort == 'high') ? 'selected':''; ?>><?php esc_html_e('Price', 'dynamicpackages'); ?>: <?php esc_html_e('hight to low', 'dynamicpackages'); ?></option>
				<option value="today" <?php echo ($sort == 'today') ? 'selected':''; ?>><?php esc_html_e('Date', 'dynamicpackages'); ?>: <?php esc_html_e('Today', 'dynamicpackages'); ?></option>
				<option value="tomorrow" <?php echo ($sort == 'tomorrow') ? 'selected':''; ?>><?php esc_html_e('Date', 'dynamicpackages'); ?>: <?php esc_html_e('Tomorrow', 'dynamicpackages'); ?></option>
				<option value="week" <?php echo ($sort == 'week') ? 'selected':''; ?>><?php esc_html_e('Date', 'dynamicpackages'); ?>: <?php esc_html_e('next 7 days', 'dynamicpackages'); ?></option>
				<option value="month" <?php echo ($sort == 'month') ? 'selected':''; ?>><?php esc_html_e('Date', 'dynamicpackages'); ?>: <?php esc_html_e('next 30 days', 'dynamicpackages'); ?></option>
			</select>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;		
	}
	
	public function check_prices_form()
	{

		$name = 'dy_check_prices_form';
		$the_id = get_dy_id();
		$cache_key = $name.'_'.$the_id;

        if (isset(self::$cache[$cache_key])) {
            echo self::$cache[$cache_key];
        }

		$auto_booking = package_field('package_auto_booking');
		$price_chart = dy_utilities::get_price_chart();
		$starting_at = dy_utilities::starting_at();
		$title = get_the_title();
		$min = package_field('package_min_persons');
		$max = package_field('package_max_persons');
		$option_disc = package_field('package_discount');
		$option_free = package_field('package_free');
		$by_hour = package_field('package_by_hour');
		$package_type = package_field('package_package_type');
		$min_duration = intval(package_field('package_duration'));
		$max_duration = intval(package_field('package_duration_max'));
		$list_durations = ($max_duration > $min_duration) ? (1 + ($max_duration - $min_duration)) : 0;
		$length_unit = package_field('package_length_unit');
		$is_transport = dy_validators::package_type_transport();
		$date_label = ($is_transport) 
			? __('Departure Date', 'dynamicpackages') . ' &raquo; ' 
			: __('Date', 'dynamicpackages');

		$start_hour = package_field('package_start_hour');
		$return_hour = package_field('package_return_hour');

		$start_address_short = package_field('package_start_address_short');
		$return_address_short = package_field('package_return_address_short');
		$has_route = !empty($start_address_short) && !empty($return_address_short);

		$plugin_dir_url = plugin_dir_url( __DIR__ );
		
		$form = '<div class="dy_package_booking_form_container"><form class="dy_package_booking_form" data-starting-at="'.esc_attr($starting_at).'" data-title="'.esc_attr($title).'" data-method="get" data-action="'.esc_attr(base64_encode(get_permalink())).'" data-gclid="true">';
		
		if($auto_booking == 1)
		{
			if(package_field('package_payment') == 1)
			{
				$deposit = dy_utilities::get_deposit();
				$percent = '%';
				$form .=  '<div class="strong large bottom-20 text-muted">'.sprintf(__('Book now with a %s%s deposit!', 'dynamicpackages'), $deposit, $percent).'</div>';
				
			}
		}

		$form .= '<div style="max-width: 300px; margin: 0 auto 20px auto;"><img class="img-responsive" width="600" weight="200" alt="visa mastercard paypal yappy crypto usdt usdc btc" src="'.esc_url($plugin_dir_url.'gateways/matrix/assets/pagos.svg').'"  /></div>';


		$form .= '<input type="hidden" name="dy_id" value="'.esc_attr(get_dy_id()).'"/>';

		if(($by_hour === '0' && $start_hour !== '' && $return_hour !== ''))
		{
			$form .= '<input type="hidden" name="schedule" value="'.esc_attr($start_hour .' - '.$return_hour).'"/>';
		}

		if(isset($_GET['force_availability'])){
			$form .= '<input type="hidden" name="force_availability" value="true"/>';
		}

		$form .= $this->adults_select($price_chart, $min, $max, $option_disc, $option_free);
		$form .= $this->discount_select($price_chart, $min, $max, $option_disc, $option_free);		
		$form .= $this->free_select($price_chart, $min, $max, $option_disc, $option_free);			


		if($is_transport && $has_route)
		{
			$route_a = $start_address_short . ' - ' . $return_address_short;
			$route_b =  $return_address_short . ' - ' . $start_address_short;

			$form .= '<label>'.esc_html(__('Type of trip', 'dynamicpackages')).'</label>';
			$form .= '<p><select name="transport_type"><option value="">---</option><option value="0">'.esc_html(__('One-way', 'dynamicpackages')).'</option><option value="1">'.esc_html(__('Round trip', 'dynamicpackages')).'</option></select></p>';


			$form .= '<label>'.esc_html(__('Route (Origin - Destination)', 'dynamicpackages')).'</label>';
			$form .= '<p><select name="route"><option value="">---</option><option value="0">'.esc_html($route_a).'</option><option value="1">'.esc_html($route_b).'</option></select></p>';

		}

		//departure transport hidden start
		$form .= ($is_transport && $has_route) ? '<div class="departure_route_container hidden">' : '';

		$departure_route_label = ($is_transport && $has_route) ? '<div class="small light departure_route_label"></div>' : '';

		if(empty(package_field('package_event_date')))
		{
			
			$form .= '<hr/><label>'.esc_html($date_label).$departure_route_label.'</label>';
			$form .= '<p><input type="text" name="booking_date" class="dy_date_picker" placeholder="Loading..." disabled/></p>';		
		}
		else
		{
			$form .= '<input type="hidden" value="'.esc_attr(package_field('package_event_date')).'" name="booking_date" />';	
		}

		if($by_hour == 1)
		{
			$form .= '<label>'.esc_html(__('Departure Time', 'dynamicpackages')).' &raquo;'.$departure_route_label.'</label>';
			$form .= '<p><input type="text" name="booking_hour"  class="dy_time_picker" /></p>';	
		}

		//departure transport hidden end and start of departure hidden
		$form .= ($is_transport && $has_route) ? '</div><div class="return_route_container hidden">' : '';
		
		if($is_transport)
		{
			$return_route_label = ($has_route) ? '<div class="small light return_route_label"></div>' : '';
			$form .= '<hr/><label>'.esc_html(__('Date of Return', 'dynamicpackages')).' &laquo;'.$return_route_label.'</label>';
			$form .= '<p><input type="text" name="end_date" class="dy_date_picker" placeholder="Loading..." disabled/></p>';
			
			if($by_hour == 1)
			{
				$form .= '<label>'.esc_html(__('Return Time', 'dynamicpackages')).' &laquo;'.$return_route_label.'</label>';
				$form .= '<p><input type="text" name="return_hour" class="dy_time_picker" /></p>';
			}		
		}

		//departure transport hidden end and start of departure hidden
		$form .= ($is_transport && $has_route) ? '<hr/></div>' : '';
		
		
		if(($package_type == 1 || dy_utilities::package_type_by_hour() || dy_utilities::package_type_by_day()) && $max_duration > $min_duration)
		{
			
			$time_label = __('Nights', 'dynamicpackages');
						
			if($length_unit == 1)
			{
				$time_label = __('Duration', 'dynamicpackages');
			}
			if($length_unit == 2)
			{
				$time_label = __('Days', 'dynamicpackages');
			}
			
			$form .= '<label>'.esc_html($time_label).'</label>';
			$form .= '<p><select type="text" name="booking_extra">';
			
			for($x = 0; $x < $list_durations; $x++)
			{
				$index = $x + $min_duration;
				$select_label = $index;
				
				if($length_unit == 1)
				{
					if($index == 1)
					{
						$select_label .= ' '.__('hour', 'dynamicpackages');
					}
					else
					{
						$select_label .= ' '.__('hours', 'dynamicpackages');
					}
				}
				
				$form .= '<option value="'.esc_attr($index).'">'.esc_html($select_label).'</option>';
			}
			
			$form .= '</select></p>';					
		}
		
		$book_now_text = __('Bookings & Estimates', 'dynamicpackages');
						


		if(dy_validators::has_coupon())
		{
			$get_coupon = '';
			$coupon_hidden = ' class="hidden" ';
			
			if(isset($_GET['coupon']))
			{
				if(!empty($_GET['coupon']))
				{
					$get_coupon = sanitize_text_field($_GET['coupon']);
					$coupon_hidden = '';
				}
			}
			
			
			$form .= '<div class="bottom-20 coupon_code_container"><a href="#coupon_code" class="semibold bottom-5 block"><span class="dashicons dashicons-awards"></span> '.esc_html(__('Enter coupon code', 'dynamicpackages')).'</a><input placeholder="'.esc_html(__('Enter coupon code', 'dynamicpackages')).'" '.$coupon_hidden.' type="text" name="coupon_code"  value="'.esc_attr($get_coupon).'" /></div>';
		}		
		
		$form .= '<div><button type="button" class="width-100 dy_check_prices block pure-button rounded">'.esc_html($book_now_text).' <span class="dashicons dashicons-arrow-right"></span></button></div>';	
		$form .= '</form></div>';
		
		
		self::$cache[$cache_key] = $form;

		echo $form;

	}
	
	public function adults_select($price_chart, $min, $max, $option_disc, $option_free)
	{	
		$adults_select = null;
		
		if(intval(package_field('package_increase_persons')) > 0)
		{
			$max = intval($max) + intval(package_field('package_increase_persons')); 
		}
		
		for($a = 0; $a < $max; $a++)
		{
			if(($a+1) <= $max && ($a+1) >= $min)
			{				
				$adults_select .= '<option value="'.esc_attr(($a+1)).'" >'.esc_html(($a+1)).'</option>';
			}
		}
		
		if(intval($option_disc) > 0 || intval($option_free) > 0)
		{
			$label_text = __('Adults', 'dynamicpackages');
		}
		else
		{
			$label_text = __('People', 'dynamicpackages');
		}
		
		$adults = '<label>'.esc_html($label_text).'</label>';
		$adults .= '<p><select name="pax_regular" class="booking_select">'.$adults_select.'</select></p>';	
		return $adults;
	}
	public function discount_select($price_chart, $min, $max, $option_disc, $option_free)
	{
		$disc = null;		
		
		if(intval($option_disc) > 0)
		{
			$start = 0;
			
			if(intval($option_free) > 0)
			{
				$start = intval($option_free)+1;
			}
			
			$end = $option_disc;
			$range = $start.' - '.$end;
			
			for($c = 0; $c < (count($price_chart)-$min+1); $c++)
			{
				$disc .= '<option value="'.esc_attr(($c+1)).'" >'.esc_html(($c+1)).'</option>';
			}
			
			if($disc != null)
			{
				$disc = '<option value="0">0</option>'.$disc;
				$output = '<label>'.esc_html(__('Children', 'dynamicpackages')).' '.esc_html($range).' '.esc_html(__('years old', 'dynamicpackages')).'</label>';		
				$output .= '<p><select name="pax_discount" class="booking_select">'.$disc.'</select></p>';
				return $output;				
			}
		}
	}
	public function free_select($price_chart, $min, $max, $option_disc, $option_free)
	{		
		$free = null;
		 
		
		if(intval($option_free) > 0)
		{
			$start = 0;
			$end = $option_free;
			$range = $start.' - '.$end;
			
			
			for($f = 1; $f < 3; $f++)
			{
				$free .= '<option data-price="0" value="'.esc_attr($f).'" >'.esc_html($f).'</option>';
			}
			if($free != null)
			{
				$free = '<option value="0">0</option>'.$free;
				$output = '<label>'.esc_html(__('Children', 'dynamicpackages')).' '.esc_html($range).' '.esc_html(__('years old', 'dynamicpackages')).'</label>';
				$output .= '<p><select name="pax_free" id="pax_free" class="booking_select">'.$free.'</select></p>';	
				return $output;					
			}
		}
	}
	
	public function get_all_terms_select($tax, $name)
	{
		$taxonomy = get_taxonomy($tax);
		
		$terms = get_terms(array(
			'taxonomy' => $tax,
			'hide_empty' => true,
			'parent' => 0,
			'orderby' => 'name'
		));
		
		$any = null;
		
		if(isset($_GET[$name]))
		{
			$any = 'selected';
		}
				
		$output = '<select name="'.esc_attr($name).'" class="width-100 block borderbox">';
		
		$output .= '<option value="any" '.esc_html($any).'>-- '.esc_html($taxonomy->labels->singular_name).' --</option>';
		
		
		if (!empty($terms) && ! is_wp_error($terms))
		{
			foreach ( $terms as $term )
			{
				if($term->parent == 0)
				{
					$selected = null;
					
					global $package_location;
					global $package_category;
					
					if(!isset($_GET[$tax]) && (isset($package_category) || isset($package_location)) )
					{
						if(isset($package_category))
						{
							if($term->slug == $package_category )
							{
								$selected = 'selected';
							}
						}
						
						if(isset($package_location))
						{
							if($term->slug == $package_location )
							{
								$selected = 'selected';
							}
						}
					}
					
					elseif(isset($_GET[$name]))
					{
						if($term->slug == $_GET[$name] )
						{
							$selected = 'selected';
						}
					}
					
					$output .= '<option '.esc_html($selected).' id="'.esc_attr($term->slug).'" value="'.esc_attr($term->slug).'">'.esc_html($term->name).'</option>';
					
					$child_terms = get_terms(array(
						'taxonomy' => $tax,
						'hide_empty' => true,
						'parent' => $term->term_id,
						'orderby' => 'name'
					));	
					
					if (!empty($terms) && ! is_wp_error($terms))
					{
						foreach ( $child_terms as $child_term )
						{
							$selected = null;
							
							if(!isset($_GET[$tax]) && (isset($package_category) || isset($package_location)) )
							{
								if(isset($package_category))
								{
									if($child_term->slug == $package_category )
									{
										$selected = 'selected';
									}
								}
								
								if(isset($package_location))
								{
									if($child_term->slug == $package_location )
									{
										$selected = 'selected';
									}
								}
							}							
							
							if(isset($_GET[$tax]))
							{
								if($child_term->slug == $_GET[$tax] )
								{
									$selected = 'selected';
								}
							}
							
							$output .= '<option '.esc_html($selected).' id="'.esc_attr($child_term->slug).'" value="'.esc_attr($child_term->slug).'">'.esc_html('&nbsp;&nbsp;'.$child_term->name).'</option>';
						}
					}
					
				}
			}
		}
		
		$output .= '</select>';	
		return $output;
	}
	
	public function pagination($args)
	{
		$output = '';
		$archive_query = $args['archive_query'];
		$posts_per_page = $args['posts_per_page'];
		
		if($archive_query->found_posts > $posts_per_page)
		{
			$big = 999999999;
			$current = max( 1, current_page_number() );

			$pages =  paginate_links( array(
				'format' => 'page/%#%',
				'current' => $current,
				'total' => $archive_query->max_num_pages,
				'type'  => 'array',
			));
			
			if(is_array($pages))
			{	
				$output .= '<div class="bottom-40 dy_pagination"><ul class="list-style-none text-right small">';
				
				foreach ( $pages as $page )
				{
					$page = str_replace( 'page-numbers', ' pure-button pure-button-bordered page-numbers ', $page);
					$page = str_replace( 'current', ' disabled ', $page);
					$output .= ' <li class="small inline-block">'.html_entity_decode($page).'</li>';
				}
				$output .= '</ul></div>';
			}		 
		}
		
		echo $output;
	}
}


?>