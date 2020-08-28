<?php

class dynamicpackages_Forms
{
	public static function package_filter_form()
	{
		global $polylang;
		
		$package_main = (get_option('dy_packages_breadcrump')) ? get_option('dy_packages_breadcrump') : get_option('page_on_front');
		
		if(isset($polylang))
		{	
			if(pll_current_language() != pll_default_language())
			{
				$package_main = pll_get_post($package_main, pll_current_language());

			}
		}	
		
		?>
		<form id="dy_package_filter" action="<?php echo esc_url(get_permalink($package_main)); ?>" method="get">
		
			<div class="pure-g gutters">

			
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="bottom-20">
						<?php echo self::get_all_terms_select('package_location', __('Select Location', 'dynamicpackages')); ?>
					</div>
				</div>	
				
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="bottom-20">
						<?php echo self::get_all_terms_select('package_category', __('Select Category', 'dynamicpackages')); ?>
					</div>
				</div>
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="bottom-20">
						<?php echo self::sort_by(); ?>
					</div>
				</div>
				<div class="pure-u-1 pure-u-md-1-4">
					<div class="pure-g">
						<div class="pure-u-1 pure-u-md-4-5">
							<div class="bottom-20">
								<input placeholder="<?php echo esc_html(__('Search Keyword', 'dynamicpackages')); ?>" type="text" name="package_search" value="<?php if(isset($_GET['package_search'])) {echo sanitize_text_field(strtolower(substr($_GET['package_search'], 0, 25))); } ?>" />	
							</div>
						</div>
						<div class="pure-u-1 pure-u-md-1-5 small">
							<button class="block borderbox width-100 pure-button pure-button-primary" type="submit"><i class="fas fa-search"></i></button>
						</div>
					</div>
				</div>				
			</div>
				
			</form>		
			
		<?php
	}
	
	public static function sort_by()
	{
		$sort = 'any';
		
		if(isset($_GET['package_sort']))
		{
			if($_GET['package_sort'] != '')
			{
				$sort = sanitize_text_field($_GET['package_sort']);
			}
		}
		
		ob_start();
		?>
			<select name="package_sort">
				<option value="any" <?php echo ($sort == 'any') ? 'selected':''; ?>>-- <?php echo esc_html(__('Sort by', 'dynamicpackages')); ?> --</option>
				<option value="new" <?php echo ($sort == 'new') ? 'selected':''; ?>><?php echo esc_html(__('Newest', 'dynamicpackages')); ?></option>
				<option value="low" <?php echo ($sort == 'low') ? 'selected':''; ?>><?php echo esc_html(__('Price', 'dynamicpackages')); ?>: <?php echo esc_html(__('low to high', 'dynamicpackages')); ?></option>
				<option value="high" <?php echo ($sort == 'high') ? 'selected':''; ?>><?php echo esc_html(__('Price', 'dynamicpackages')); ?>: <?php echo esc_html(__('hight to low', 'dynamicpackages')); ?></option>
				<option value="today" <?php echo ($sort == 'today') ? 'selected':''; ?>><?php echo esc_html(__('Date', 'dynamicpackages')); ?>: <?php echo esc_html(__('Today', 'dynamicpackages')); ?></option>
				<option value="tomorrow" <?php echo ($sort == 'tomorrow') ? 'selected':''; ?>><?php echo esc_html(__('Date', 'dynamicpackages')); ?>: <?php echo esc_html(__('Tomorrow', 'dynamicpackages')); ?></option>
				<option value="week" <?php echo ($sort == 'week') ? 'selected':''; ?>><?php echo esc_html(__('Date', 'dynamicpackages')); ?>: <?php echo esc_html(__('next 7 days', 'dynamicpackages')); ?></option>
				<option value="month" <?php echo ($sort == 'month') ? 'selected':''; ?>><?php echo esc_html(__('Date', 'dynamicpackages')); ?>: <?php echo esc_html(__('next 30 days', 'dynamicpackages')); ?></option>
			</select>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;		
	}
	
	public static function auto_booking()
	{
		$auto_booking = package_field('package_auto_booking');
		$price_chart = dy_utilities::get_price_chart();
		$min = package_field('package_min_persons');
		$max = package_field('package_max_persons');
		$option_disc = package_field('package_discount');
		$option_free = package_field('package_free');
		$by_hour = package_field('package_by_hour');
		$package_type = package_field('package_package_type');
		$min_duration = package_field('package_duration');
		$max_duration = package_field('package_duration_max');
		$length_unit = package_field('package_length_unit');
		
		$form = '<div class="booking_form_container"><form id="dy_booking_form" class="booking_form" method="get">';
		
		if(dy_Gateways::has_any_gateway())
		{	
			$form .= '<h3 class="linkcolor uppercase">'.esc_html(__('Book  Now', 'dynamicpackages')).'</h3>';
		}

		if(package_field('package_event_date') == '')
		{
			$form .= '<label>'.esc_html(__('Date', 'dynamicpackages')).'</label>';
			$form .= '<p><input type="text" placeholder="Loading..." name="booking_date" class="required" disabled/></p>';			
		}
		else
		{
			$form .= '<input type="hidden" value="'.esc_html(package_field('package_event_date')).'" name="booking_date" class="required" />';	
		}


		
		if($by_hour == 1)
		{
			$form .= '<label>'.esc_html(__('Hour', 'dynamicpackages')).'</label>';
			$form .= '<p><input type="text" name="booking_hour"  class="required" /></p>';
		
		}
		
		if(($package_type == 1 || dy_utilities::increase_by_hour() || dy_utilities::increase_by_day()) && $max_duration > $min_duration)
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
			
			for($x = ($min_duration); $x < (intval($max_duration)+1); $x++)
			{
				$select_label = $x;
				
				if($length_unit == 1)
				{
					if($x == 1)
					{
						$select_label .= ' '.__('hour', 'dynamicpackages');
					}
					else
					{
						$select_label .= ' '.__('hours', 'dynamicpackages');
					}
				}
				
				$form .= '<option value="'.esc_html($x).'">'.esc_html($select_label).'</option>';
			}
			
			$form .= '</select></p>';					
		}
		
		$book_now_text = __('Book Now', 'dynamicpackages');
		$book_now_icon = '<i class="fas fa-envelope"></i>';
		$get_quote = '';
		$quote_now_text = $book_now_text;
		$margin = '';
		

		if($auto_booking == 1)
		{
			$quote_now_text = __('Get Quote', 'dynamicpackages');
			$book_now_icon = '<i class="fas fa-credit-card"></i>';
			$get_quote = '<div class="text-center"><button type="button" class="booking_quote pure-button width-100 borderbox block rounded">'.esc_html($quote_now_text).' <i class="fas fa-envelope"></i></button></div>';
			$margin = ' bottom-20 ';
			
			if(package_field('package_payment') == 1)
			{
				$deposit = dy_utilities::get_deposit();
				$book_now_text =  __('Pay Deposit', 'dynamicpackages').' '.$deposit.'% ';
				
			}
		}
						
		$form .= self::adults_select($price_chart, $min, $max, $option_disc, $option_free);
		$form .= self::discount_select($price_chart, $min, $max, $option_disc, $option_free);		
		$form .= self::free_select($price_chart, $min, $max, $option_disc, $option_free);	

		if(dy_Validators::has_coupon())
		{
			$get_coupon = '';
			$coupon_hidden = ' class="hidden" ';
			
			if(isset($_GET['coupon']))
			{
				if($_GET['coupon'] != '')
				{
					$get_coupon = sanitize_text_field($_GET['coupon']);
					$coupon_hidden = '';
				}
			}
			
			
			$form .= '<div class="bottom-20" id="booking_coupon"><a href="#booking_coupon" class="light bottom-5 block">'.esc_html(__('Enter coupon code', 'dynamicpackages')).'</a><input placeholder="'.esc_html(__('Enter coupon code', 'dynamicpackages')).'" '.$coupon_hidden.' type="text" name="booking_coupon"  value="'.esc_html($get_coupon).'" /></div>';
		}		
		
		$form .= '<div><button type="submit" class="width-100 booking_submit '.esc_html($margin).' block pure-button pure-button-primary rounded">'.esc_html($book_now_text).' '.$book_now_icon.'</button></div>';
		$form .= $get_quote;		
		$form .= '</form></div>';
		echo $form;			

	}
	
	public static function adults_select($price_chart, $min, $max, $option_disc, $option_free)
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
				$adults_select .= '<option value="'.esc_html(($a+1)).'" >'.esc_html(($a+1)).'</option>';
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
		$adults .= '<p><select name="pax_regular" >'.$adults_select.'</select></p>';	
		return $adults;
	}
	public static function discount_select($price_chart, $min, $max, $option_disc, $option_free)
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
				$disc .= '<option value="'.esc_html(($c+1)).'" >'.esc_html(($c+1)).'</option>';
			}
			
			if($disc != null)
			{
				$disc = '<option value="0">0</option>'.$disc;
				$output = '<label>'.esc_html(__('Children', 'dynamicpackages')).' '.esc_html($range).' '.esc_html(__('years old', 'dynamicpackages')).'</label>';		
				$output .= '<p><select name="pax_discount" >'.$disc.'</select></p>';
				return $output;				
			}
		}
	}
	public static function free_select($price_chart, $min, $max, $option_disc, $option_free)
	{		
		$free = null;
		 
		
		if(intval($option_free) > 0)
		{
			$start = 0;
			$end = $option_free;
			$range = $start.' - '.$end;
			
			
			for($f = 1; $f < ($max-$min+1); $f++)
			{
				$free .= '<option data-price="0" value="'.esc_html($f).'" >'.esc_html($f).'</option>';
			}
			if($free != null)
			{
				$free = '<option value="0">0</option>'.$free;
				$output = '<label>'.esc_html(__('Children', 'dynamicpackages')).' '.esc_html($range).' '.esc_html(__('years old', 'dynamicpackages')).'</label>';
				$output .= '<p><select name="pax_free" id="pax_free">'.$free.'</select></p>';	
				return $output;					
			}
		}
	}
	
	public static function get_all_terms_select($tax)
	{
		$taxonomy = get_taxonomy($tax);
		
		$terms = get_terms(array(
			'taxonomy' => $tax,
			'hide_empty' => true,
			'parent' => 0,
			'orderby' => 'name'
		));
		
		$any = null;
		
		if(isset($_GET[$tax]))
		{
			$any = 'selected';
		}
		
		//echo var_dump($terms);
		
		$output = '<select name="'.esc_html($tax).'" class="width-100 block borderbox">';
		
		
		$output .= '<option value="any" '.esc_html($any).'>-- '.$taxonomy->labels->singular_name.' --</option>';
		
		
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
					
					elseif(isset($_GET[$tax]))
					{
						if($term->slug == $_GET[$tax] )
						{
							$selected = 'selected';
						}
					}
					
					$output .= '<option '.esc_html($selected).' id="'.esc_html($term->slug).'" value="'.esc_html($term->slug).'">'.esc_html($term->name).'</option>';
					
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
							
							$output .= '<option '.esc_html($selected).' id="'.esc_html($child_term->slug).'" value="'.esc_html($child_term->slug).'">'.esc_html('&nbsp;&nbsp;'.$child_term->name).'</option>';
						}
					}
					
				}
			}
		}
		
		$output .= '</select>';	
		return $output;
	}
	
	public static function pagination($wp_query, $posts_per_page)
	{
		if($wp_query->found_posts > $posts_per_page)
		{
			$big = 999999999;

			$pages =  paginate_links( array(
				'format' => '?paged=%#%',
				'current' => max( 1, get_query_var('paged') ),
				'total' => $wp_query->max_num_pages,
				'type'  => 'array',
			));
			
			if(is_array($pages))
			{	
				echo '<div class="bottom-40 dy_pagination"><ul class="list-style-none text-right small">';
				
				foreach ( $pages as $page )
				{
					$page = str_replace( 'page-numbers', ' pure-button pure-button-bordered page-numbers ', $page);
					$page = str_replace( 'current', ' disabled ', $page);
					echo ' <li class="small inline-block">'.html_entity_decode($page).'</li>';
				}
				echo '</ul></div>';
			}		 
		}		
	}
}


?>