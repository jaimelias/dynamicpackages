<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Public {

	private static $cache = [];

	public function __construct($version) {

		if(is_admin())
		{
			return;
		}

		$this->version = $version;
		$this->plugin_dir_url_file = plugin_dir_url( __FILE__ );
		$this->plugin_dir_url_dir = plugin_dir_url( __DIR__ );
		$this->dirname_file = dirname( __FILE__ );

		add_action('init', array(&$this, 'init'));

		//scripts
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'), DY_IS_PACKAGE_PAGE_PRIORITY);

		//redirect
		add_action('template_redirect', array(&$this, 'template_redirect'));
		add_filter('post_type_link', array(&$this, 'post_type_link'), DY_IS_PACKAGE_PAGE_PRIORITY, 2);

		//template
		add_filter('template_include', array(&$this, 'package_template'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('the_content', array(&$this, 'the_content'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('pre_get_document_title', array(&$this, 'wp_title'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('wp_title', array(&$this, 'wp_title'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('the_title', array(&$this, 'the_title'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('single_term_title', array(&$this, 'modify_tax_title'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('get_the_excerpt', array(&$this, 'modify_excerpt'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('term_description', array(&$this, 'modify_term_description'));
		add_action('pre_get_posts', array(&$this, 'set_one_tax_per_page'));
		add_filter('term_description', array(&$this, 'modify_term_description'));


		//packages
		add_filter('dy_details', array(&$this, 'details'));
		add_action('dy_description', array(&$this, 'description'));
		add_action('dy_show_coupons', array(&$this, 'show_coupons'));
		add_filter('minimal_description', array(&$this, 'meta_description'));
		add_filter('dy_event_arr', array(&$this, 'event_arr'));
		add_filter('dy_price_type', array(&$this, 'price_type'));
		add_filter('dy_booking_sidebar', array(&$this, 'booking_sidebar'));
		add_action('dy_children_package', array(&$this, 'children_package'));
		add_action('dy_similar_packages_link', array(&$this, 'similar_packages_link'));
		add_action('dy_get_terms_conditions_list', array(&$this, 'get_terms_conditions_list'));
		add_action('dy_get_included_list', array(&$this, 'get_included_list'));
		add_action('dy_get_not_included_list', array(&$this, 'get_not_included_list'));
		add_action('dy_get_category_list', array(&$this, 'get_category_list'));
		add_action('dy_get_location_list', array(&$this, 'get_location_list'));
		add_action('dy_show_badge', array(&$this, 'show_badge'));
		add_action('dy_show_event_date', array(&$this, 'show_event_date'));
		add_action('dy_edit_link', array(&$this, 'edit_link'));
	}

	public function init()
	{
		$this->current_language = current_language();
	}
	
	public function enqueue_styles() {
		
		wp_enqueue_style('dynamicpackages', $this->plugin_dir_url_file . 'css/dynamicpackages-public.css', array(), $this->version);
	}
	
	public function enqueue_scripts() {
 
		global $post;

		if(is_tax('package_category') || is_tax('package_location') || is_post_type_archive('packages') || (($post instanceof WP_Post) && has_shortcode( $post->post_content, 'packages')))
		{
			wp_enqueue_script('dynamicpackages-archive', $this->plugin_dir_url_file . 'js/dynamicpackages-archives.js', array('jquery', 'dy-core-utilities'), $this->version, true );
		}
		
	}
		

	
	public function package_template($template)
	{
		if(is_tax('package_terms_conditions') || is_tax('package_location') || is_tax('package_category') || 'packages' == get_post_type())
		{
			$new_template = locate_template( array( 'page.php' ) );
			return $new_template;			
		}	
		return $template;
	}
	public function booking_sidebar()
	{
		ob_start();
		require_once($this->dirname_file . '/partials/quote-form.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public function the_content($content)
	{
		global $post;
		global $polylang;

		if(is_tax('package_location') || is_tax('package_category'))
		{
			ob_start();
			require_once($this->dirname_file . '/partials/archive.php');
			$content = ob_get_contents();
			ob_end_clean();
		}
		else if(is_tax('package_terms_conditions'))
		{
			$Parsedown = new Parsedown();
			$term = get_term(get_queried_object()->term_id);
			$content = $Parsedown->text($term->description);
		}
		else if(is_singular('packages'))
		{
			if(!is_booking_page())
			{
				$partial_content = $content;
				
				ob_start();
				require_once($this->dirname_file . '/partials/single.php');
				$content = ob_get_contents();
				ob_end_clean();

			}
		}
		
		return $content;
	}
	
	public function wp_title($title)
	{
		global $polylang;
		global $post;		

		if(is_singular('packages'))
		{
			if(is_booking_page())
			{
				$title = __('Online Booking', 'dynamicpackages').' '.get_the_title().' | '. get_bloginfo( 'name' );
			}	
			

			if($post->post_parent > 0)
			{
				$args = array('p' => $post->post_parent, 'posts_per_page' => 1, 'post_type' => 'packages');
				$parent_query = new WP_Query($args);
				
				$title = get_the_title();
				
				if ( $parent_query->have_posts() )
				{
					while ( $parent_query->have_posts() )
					{
						$parent_query->the_post();
						$title .= ' | '. get_the_title().' | '. get_bloginfo( 'name' );
					}
					
					wp_reset_postdata();
				}
			}				
		}

		return $title;
	}
	
	public function the_title($title)
	{
		if(!in_the_loop()) return $title;
		
		if(is_singular('packages') && is_booking_page())
		{				
			$title = sprintf(__('Booking Page: %s', 'dynamicpackages'), $title);
		}

		return $title;
	}
	
	public function modify_tax_title($title)
	{
		if ( is_tax('package_terms_conditions') && in_the_loop() ) {
			$title = sprintf('<span class="linkcolor">%s</span>', $title);
		}

		return $title;
	}


	
	public static function price_type($force_per_person = false)
	{
		$name      = 'dy_price_type';
		$the_id    = get_dy_id();
		$cache_key = sprintf('%s_%s', $name, $the_id);

		if ( isset(self::$cache[$cache_key]) ) {
			return self::$cache[$cache_key];
		}

		$price_type    = (int) package_field('package_fixed_price');
		$package_type  = dy_utilities::get_package_type($the_id);
		$duration_unit = (int) package_field('package_length_unit');
		$output        = '';

		if ( $price_type === 0 || $force_per_person === true) {
			$output = sprintf('%s ', __('per person', 'dynamicpackages'));
		}

		if ( $package_type === 'multi-day' ) {
			$output .= sprintf(
				'%s %s',
				__('per', 'dynamicpackages'),
				dy_utilities::duration_label($duration_unit, 1)
			);
		} elseif ( $package_type === 'rental-per-hour' ) {
			$output .= __('per hour', 'dynamicpackages');
		} elseif ( $package_type === 'rental-per-day' ) {
			$output .= __('per day', 'dynamicpackages');
		} elseif ( $package_type === 'transport' ) {
			$output .= __('one-way', 'dynamicpackages');
		}

		// store output in cache
		self::$cache[$cache_key] = $output;

		return $output;
	}


	public function get_location_list()
	{
		$output = '';
		$cache_key = 'dy_get_location_list';


        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$output = dy_utilities::get_tax_list('package_location', __('Places of Interest:', 'dynamicpackages'), true, 'dashicons dashicons-location');
        
		//store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
	}
	public function get_category_list()
	{
		$output = '';
		$cache_key = 'dy_get_category_list';


        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$output = dy_utilities::get_tax_list('package_category', __('Categories:', 'dynamicpackages'), true, 'dashicons dashicons-tag');
        
		//store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
	}


	public function get_terms_conditions_list()
	{
		$output = '';
		$cache_key = 'dy_get_terms_conditions_list';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$output = dy_utilities::get_tax_list('package_terms_conditions', __('Terms & Conditions:', 'dynamicpackages'), true, 'dashicons dashicons-warning');
        
		//store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;	
	}	
	public function get_included_list()
	{
		$output = '';
		$cache_key = 'dy_get_included_list';


        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
        
		$output = dy_utilities::get_tax_list('package_included', __('Included:', 'dynamicpackages'), false, 'dashicons dashicons-yes');
        
		//store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
	}
	
	public function get_not_included_list()
	{

		$output = '';
		$cache_key = 'dy_get_not_included_list';


        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$output = dy_utilities::get_tax_list('package_not_included', __('Not Included:', 'dynamicpackages'), false, 'dashicons dashicons-no');
        
		//store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
	}
	
	public function set_one_tax_per_page( $query )
	{
		if((is_tax('package_location') || is_tax('package_category') || is_tax('package_terms_conditions')) && $query->is_main_query())
		{
			$query->set( 'posts_per_page', 1 );
		}
	}
	
	public static function description() {

		//in this example free of cost if for infants under centain age and discount if four children older than infant but under center age
		// the correct example of adult with children is "Round trip | Ferry to Saboga Island (Departure April 12, 2025 | Return April 13, 2025): 1 adult, 1 child under 4 years old"
		// the correct example of adult with chidlren and infant is "Round trip | Ferry to Saboga Island (Departure April 12, 2025 | Return April 13, 2025): 1 adult, 1 infant under 4 years old, 1 child under 11 years old"

		global $post;
	
		// Guard: nothing to do without a post or booking date
		if ( empty( $post ) || empty( $_REQUEST['booking_date'] ) ) {
			return '';
		}

		$name = 'dy_description_str';
		$the_id = get_dy_id();
		$cache_key = $name.'_'.$the_id;

		if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }
	
		// Core flags & data
		$isTransport    = dy_utilities::get_package_type() === 'transport';
		$routeRaw       = sanitize_text_field( $_REQUEST['route'] ?? '' );
		$modify_route    = $isTransport && $routeRaw === '1';
	
		$startShort     = package_field( 'package_start_address_short' );
		$returnShort    = package_field( 'package_return_address_short' );
	
		// Dates & hours
		$depDate        = dy_utilities::format_date( dy_utilities::booking_date() );
		$depHour        = dy_utilities::hour() ? '@ ' . dy_utilities::hour() : '';
	
		$endRaw         = sanitize_text_field( $_REQUEST['end_date'] ?? '' );
		$hasReturn      = $endRaw && is_valid_date( $endRaw );
		$retDate        = $hasReturn ? dy_utilities::format_date( dy_utilities::end_date() ) : '';
		$retHour        = ( $hasReturn && dy_utilities::return_hour() ) ? '@ ' . dy_utilities::return_hour() : '';
	
		// Passenger counts
		$counts = [
			'adults'   => intval( sanitize_text_field( $_REQUEST['pax_regular'] ?? 0 ) ),
			// swap the order here if you want infants listed before children:
			'discount' => intval( sanitize_text_field( $_REQUEST['pax_discount'] ?? 0 ) ), // children
			'free'     => intval( sanitize_text_field( $_REQUEST['pax_free']     ?? 0 ) ), // infants
		];
		$ages = [
			'discount' => package_field( 'package_discount' ) ?: 0, // child max age
			'free'     => package_field( 'package_free'     ) ?: 0, // infant max age
		];
	
		$chunks = [];
		foreach ( $counts as $type => $num ) {
			if ( $num < 1 ) {
				continue;
			}
			switch ( $type ) {
				case 'adults':
					$label = _n( 'adult', 'adults', $num, 'dynamicpackages' );
					$chunks[] = sprintf( '%d %s', $num, $label );
					break;
		
				case 'free': // infants
					$label = _n( 'infant', 'infants', $num, 'dynamicpackages' );
					$chunks[] = sprintf(
						'%d %s under %d %s',
						$num,
						$label,
						$ages[ $type ],
						__( 'years old', 'dynamicpackages' )
					);
					break;
		
				case 'discount': // children
					$label = _n( 'child', 'children', $num, 'dynamicpackages' );
					$chunks[] = sprintf(
						'%d %s under %d %s',
						$num,
						$label,
						$ages[ $type ],
						__( 'years old', 'dynamicpackages' )
					);
					break;
			}
		}
		
		$peopleStr = implode( ', ', $chunks );
	
		// Itinerary & trip type
		if ( $isTransport ) {
			// Dynamic “Departure” / “Return” labels
			if ( $startShort && $returnShort ) {
				$depLabel = $modify_route
					? "{$returnShort}-{$startShort}"
					: "{$startShort}-{$returnShort}";
				$retLabel = $modify_route
					? "{$startShort}-{$returnShort}"
					: "{$returnShort}-{$startShort}";
			} else {
				$depLabel = __( 'Departure', 'dynamicpackages' );
				$retLabel = __( 'Return', 'dynamicpackages' );
			}
	
			// Build legs
			$itinerary  = sprintf(
				'%s %s %s',
				$depLabel,
				$depDate,
				$modify_route ? $retHour : $depHour
			);
			$tripType = __( 'One-way', 'dynamicpackages' );
	
			if ( $hasReturn ) {
				$itinerary .= ' | ' . sprintf(
					'%s %s %s',
					$retLabel,
					$retDate,
					$modify_route ? $depHour : $retHour
				);
				$tripType = __( 'Round trip', 'dynamicpackages' );
			}
		} else {
			$itinerary = trim( sprintf( '%s %s', $depDate, $depHour ) );
			$tripType  = dy_utilities::show_duration();
		}
	
		// Final assembly
		$output = sprintf(
			'%s | %s (%s): %s',
			$tripType,
			$post->post_title,
			$itinerary,
			$peopleStr
		);

		self::$cache[$cache_key] = $output;

		return $output;
	}
	
	
	public function show_badge()
	{
		$output = '';
		$code   = (int) package_field('package_badge');

		if ( $code > 0 ) {
			
			$color    = (string) package_field('package_badge_color');

			$messages = [
				null,
				__('Best Seller', 'dynamicpackages'),
				__('New', 'dynamicpackages'),
				__('Offer', 'dynamicpackages'),
				__('Featured', 'dynamicpackages'),
				__('Last Minute Deal', 'dynamicpackages'),
			];

			if ( isset($messages[$code]) ) {
				$output = sprintf(
					'<small class="dy_badge_class %s">%s</small>',
					esc_html($color),
					esc_html($messages[$code])
				);
			}
		}

		echo $output;
	}

	
	public function children_package()
	{
		$cache_key = 'dy_children_package';
		$output = '';

        if (isset(self::$cache[$cache_key])) {
            echo self::$cache[$cache_key];
			return;
        }

		global $post;
		
		if(!dy_validators::is_child() && ($post instanceof WP_Post))
		{
			$duration = package_field('package_duration');
			$duration_unit = package_field('package_length_unit');
			$header_name = 'package_child_title_'.$this->current_language;
			$header_title = package_field($header_name, $post->ID);
			$label = (empty($header_title)) ? __('Packages', 'dynamicpackages') : sprintf(__('%s available %s', 'dynamicpackages'), $this->count_child(), $header_title);
			
			$args = array(
				'post_parent' => $post->ID,
				'post_type'   => 'packages', 
				'numberposts' => -1
			); 
			
			$children_array = get_children($args);

			if(is_array($children_array))
			{
				if(count($children_array) > 0)
				{
					$has_rows = false;
					$rows_arr = [];
					
					foreach($children_array as $item)
					{
						if(property_exists($item, 'post_name'))
						{
							if(!empty($item->post_name))
							{
								$row = '';
								$starting_at = intval(dy_utilities::starting_at($item->ID));
								$subpackage_name = 'package_child_title_'.$this->current_language;
								$button_label = ($starting_at > 0) ? '$' . $starting_at : __('Rates', 'dynamicpackages');
								
								$subpackage_name = package_field($subpackage_name, $item->ID);
								
								if(empty($subpackage_name))
								{
									$subpackage_name = $item->post_title;
								}
								
								$row .= '<tr>';
								$row .= '<td>'.esc_html($subpackage_name).'</td>';
								$row .= '<td class="text-center">'.esc_html(package_field('package_max_persons', $item->ID)).' <span class="dashicons dashicons-admin-users"></span></td>';
								$row .= '<td><a class="strong pure-button pure-button-primary rounded block width-100 borderbox" href="'.esc_url(normalize_url(rtrim(get_the_permalink(), '/').'/'.$item->post_name)).'">'.esc_html($button_label).' <span class="dashicons dashicons-arrow-right"></span></a></td>';
								$row .= '</tr>';
								
								$rows_arr[] = array('price' => $starting_at, 'row' => $row);
							}
						}
					}
					
					$count_rows = count($rows_arr);

					if($count_rows > 0)
					{

						function sort_by_price($array) {
							usort($array, function($a, $b) {
								return $a['price'] - $b['price'];
							});
						
							return $array;
						}
						
						if($count_rows === 1)
						{
							$rows = $rows_arr[0]['row'];
						}
						else
						{

							$rows = '';
							$rows_arr = sort_by_price($rows_arr);

							for ($x=0; $x < $count_rows; $x++)
							{ 
								$rows .= $rows_arr[$x]['row'];
							}

						}

						$output .= '<table class="pure-table pure-table-bordered bottom-20 width-100"><thead class="text-center"><tr><th colspan="3">'.esc_html($label).':</th></tr></thead><tbody class="small">'.$rows.'</tbody></table>';
					}		
				}
			}			
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
		return;
	}
	
	public function modify_excerpt($excerpt)
	{
		global $post;
		
		if(is_singular('packages'))
		{
			if(is_booking_page())
			{
				$excerpt = null;
			}
			else
			{
				$excerpt = null;
				
				if(!in_the_loop())
				{
					$excerpt .= ' '.__('Starting at', 'dynamicpackages');
					
					if(intval(dy_utilities::starting_at()) > 0)
					{
						$excerpt .= ' '.wrap_money_full(dy_utilities::starting_at()).' '.apply_filters('dy_price_type', false).'. ';
						
						if(package_field('package_payment') > 0 && package_field('package_deposit' ) > 0)
						{
							$excerpt .= __('Book it with a', 'dynamicpackages').' '.package_field('package_deposit' ).'% '.__('deposit', 'dynamicpackages').'. ';
						}
					}
					
				}
				else
				{
					$excerpt = dy_utilities::show_duration(true) . ' - ';
				}		
								
				$excerpt .= $post->post_excerpt;

			}
		}
		
		return $excerpt;
	}
	



	
	public function modify_term_description($description)
	{
		if(is_tax())
		{
			if(is_tax('package_terms_conditions') || is_tax('package_location') || is_tax('package_category'))
			{
				$description = null;
			}
		}
		return $description;
	}
	
	
	public function details()
	{

		$name = 'dy_details_list';
		$the_id = get_dy_id();
		$cache_key = $name.'_'.$the_id;

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		global $dy_is_archive;
		$is_archive = (isset($dy_is_archive)) ? true : false;
		$booking_date = (dy_utilities::booking_date()) ? dy_utilities::format_date(dy_utilities::booking_date()) : null;
		$end_date = (dy_utilities::end_date()) ? dy_utilities::format_date(dy_utilities::end_date()) : null;
		
		$is_transport = dy_utilities::get_package_type($the_id) === 'transport';
		$is_confirmation_page = is_confirmation_page();
		$is_booking_page = is_booking_page();
		$min_hour = package_field('package_min_hour');
		$max_hour = package_field('package_max_hour');
		$check_in_hour = package_field('package_check_in_hour');
		$start_hour = dy_utilities::hour();
		$start_address = package_field('package_start_address');
		$start_address_short = package_field('package_start_address_short');
		$check_in_end_hour = package_field('package_check_in_end_hour');
		$return_address = package_field('package_return_address');
		$return_address_short = package_field('package_return_address_short');

		
		$return_hour = dy_utilities::return_hour();
		$max_persons = package_field('package_max_persons');
		$show_max_persons = (intval(get_option('dy_archive_hide_max_persons')) === 1 || intval(package_field('package_fixed_price')) === 0) 
			? false 
			: true;
		$is_package_by_hour = boolval(package_field('package_by_hour'));
		$schedule = '';
		$is_transport_fixed = false;

		if($is_package_by_hour && $min_hour && $max_hour)
		{
			$schedule = __('Schedule', 'dynamicpackages') .' '. $min_hour . ' - '. $max_hour;
		}
		else if($is_transport && $start_hour && $return_hour && !$is_package_by_hour)
		{
			$is_transport_fixed = true;
			$schedule = sprintf(__('Departure %s - Return %s', 'dynamicpackages'), $start_hour, $return_hour);
		}

		// Base labels
		$label_departure = __('Departure', 'dynamicpackages');
		$label_return    = __('Return',    'dynamicpackages');

		// Determine if we should swap start/return
		$modify_route = $is_transport && isset($_REQUEST['route']) && sanitize_text_field($_REQUEST['route']) === '1';

		// Only override labels if we have both addresses
		if (
			$is_transport
			&& ! empty($start_address_short)
			&& ! empty($return_address_short)
		) {
			// Prepare the two‑item orders for departure and return
			if ($modify_route) {
				$dep_pair = [$return_address_short, $start_address_short];
				$ret_pair = [$start_address_short, $return_address_short];
			} else {
				$dep_pair = [$start_address_short, $return_address_short];
				$ret_pair = [$return_address_short, $start_address_short];
			}

			$label_departure = sprintf(
				__('Departure (%s – %s)', 'dynamicpackages'),
				$dep_pair[0],
				$dep_pair[1]
			);
			$label_return = sprintf(
				__('Return (%s – %s)', 'dynamicpackages'),
				$ret_pair[0],
				$ret_pair[1]
			);
		}

		// Core args
		$args = [
			'label_itinerary' => [ null,            __('Itinerary',   'dynamicpackages') ],
			'max_persons'     => [ 'admin-users',   $max_persons . ' ' . __('pers. max.', 'dynamicpackages') ],
			'duration'        => [ 'clock',         dy_utilities::show_duration() ],
			'enabled_days'    => [ 'calendar',      dy_utilities::enabled_days() ],
			'schedule'        => [ 'clock',         $schedule ],
		];

		// Swap‐aware values
		$sa = $modify_route;  // shorthand
		$first_address  = $sa ? $return_address : $start_address;
		$second_address = $sa ? $start_address        : $return_address;
		$first_hour     = $sa ? $return_hour          : $start_hour;
		$second_hour    = $sa ? $start_hour           : $return_hour;
		$first_checkin  = $sa ? $check_in_end_hour    : $check_in_hour;
		$second_checkin = $sa ? $check_in_hour        : $check_in_end_hour;

		// Build once
		$route = [
			'label_departure'    => [ null,     $label_departure ],
			'booking_date'       => [ 'calendar',$booking_date ],
			'check_in'           => [ 'clock',   __('Check‑in', 'dynamicpackages') . ' ' . $first_checkin ],
			'start_hour'         => [ 'clock',   __('Departing', 'dynamicpackages') . ' ' . $first_hour ],
			'start_address'      => [ 'location',$first_address ],
			'label_return'       => [ null,     $label_return ],
			'end_date'           => [ 'calendar',$end_date ],
			'check_in_end_hour'  => [ 'clock',   __('Check‑in', 'dynamicpackages') . ' ' . $second_checkin ],
			'return_hour'        => [ 'clock',   __('Returning', 'dynamicpackages') . ' ' . $second_hour ],
			'return_address'     => [ 'location',$second_address ],
		];

		// Merge in one go
		$args = array_merge($args, $route);


		$req = [];
		$show_labels = false;

		if($is_archive || is_page() || is_tax())
		{
			if(dy_utilities::enabled_days()) $req[] = 'enabled_days';
			if($schedule) $req[] = 'schedule';
			if($show_max_persons) $req[] = 'max_persons';
			if(dy_utilities::hour() && $is_transport_fixed === false) $req[] = 'start_hour';
			if(!get_option('dy_archive_hide_start_address')) $req[] = 'start_address';
			if(!get_option('dy_archive_hide_enabled_days')) $req[] = 'enabled_days';

		}
		else if(is_singular('packages') && !$is_booking_page && !$is_confirmation_page)
		{

			$show_labels = true;
			$req[] = 'duration';
			if(dy_utilities::enabled_days()) $req[] = 'enabled_days';
			if($schedule && $is_transport_fixed === false) $req[] = 'schedule';
			if($show_max_persons) $req[] = 'max_persons';

			if($is_transport)
			{
				$req[] = 'label_itinerary';
				$req[] = 'label_departure';
			}

			if($check_in_hour) $req[] = 'check_in';
			if(dy_utilities::hour()) $req[] = 'start_hour';
			if($start_address) $req[] = 'start_address';

			if($is_transport)
			{
				$req[] = 'label_return';
				$req[] = 'end_date';
				if($return_hour) $req[] = 'return_hour';
				if($check_in_end_hour) $req[] = 'check_in_end_hour';
				if($return_address)$req[] = 'return_address';				
			}
		}
		else if($is_booking_page || $is_confirmation_page)
		{
			$show_labels = true;

			if($is_transport)
			{
				$req[] = 'label_departure';
			}
			else
			{
				$req[] = 'duration';
			}

			if($booking_date) $req[] = 'booking_date';
			if($check_in_hour) $req[] = 'check_in';
			if(dy_utilities::hour()) $req[] = 'start_hour';
			if($start_address) $req[] = 'start_address';

			if(isset($_REQUEST['end_date']))
			{
				if($_REQUEST['end_date'] !== '')
				{
					$req[] = 'label_return';
					$req[] = 'end_date';
					if($return_hour) $req[] = 'return_hour';
					if($check_in_end_hour) $req[] = 'check_in_end_hour';
					if($return_address) $req[] = 'return_address';
				}
			}
		}

		foreach ($args as $key => $value) {
			// Check if the first element of the inner array is null
			if (!in_array($key, $req)) {
				// Remove the item with null first element
				unset($args[$key]);
			}
			else if(empty($value[0]) && $show_labels === false)
			{
				unset($args[$key]);
			}
		}


		$output = '';

		foreach($args as $k => $v)
		{
			if($v[1])
			{
				if($v[0])
				{
					
					$output .= '<div class="dy_pad bottom-5 dashicons-before dashicons-'.esc_attr($v[0]).'"> <span class="hidden">-</span> '.esc_html($v[1]).'</div>';
				}
				else
				{
					$output .= '<div class="dy_pad bottom-5"><strong>'.esc_html($v[1]).'</strong></div>';
				}
			}
		}

		self::$cache[$cache_key] = $output;
		
		return $output;
	}
	

	public function show_event_date() {
		$sort = isset($_GET['sort']) ? sanitize_text_field( $_GET['sort'] ) : '';

		// Map valid keys to translated labels
		$labels = [
			'today'    => __( 'today', 'dynamicpackages' ),
			'tomorrow' => __( 'tomorrow', 'dynamicpackages' ),
			'week'     => __( 'next 7 days', 'dynamicpackages' ),
			'month'    => __( 'next 30 days', 'dynamicpackages' ),
		];

		if ( ! isset( $labels[ $sort ] ) ) {
			return; // Nothing to render for invalid/missing sort
		}

		printf(
			'<small class="dy_event_date_class">%s</small>',
			esc_html( $labels[ $sort ] )
		);
	}
	
	public function show_coupons()
	{
		if(dy_validators::has_coupon())
		{
			$package_type = dy_utilities::get_package_type();
			$duration_unit = package_field('package_length_unit');
			$coupons = dy_utilities::get_package_hot_chart('package_coupons');
			$output = '';			
						
			if(is_array($coupons) && array_key_exists('coupons', $coupons))
			{
				$coupons = $coupons['coupons'];

				for($x = 0; $x < count($coupons); $x++)
				{
					if(!empty($coupons[$x][3]) && !empty($coupons[$x][0]))
					{
						$expiration = 0;

						if(is_valid_date($coupons[$x][2]))
						{
							$expiration_date = $coupons[$x][2] . ' 23:59:59';
							$expiration = new DateTime($expiration_date);
							$expiration = $expiration->getTimestamp();
						}
						
						if($expiration >= strtotime('today midnight') || $expiration === 0)
						{
							$label = '';
							$output .= '<div class="dy_coupon bottom-20 dy_pad">';
							$label .= esc_html(__('Get a', 'dynamicpackages'));
							$label .= ' <strong>'.esc_html($coupons[$x][1]).'%</strong>';
							$label .= ' '.esc_html(__('off using the coupon code', 'dynamicpackages'));
							$label .= ' <strong>'.strtoupper(esc_html($coupons[$x][0])).'</strong>.';
							
							if(isset($coupons[$x][4]) && $package_type !== 'transport' && $package_type !== 'one-day')
							{
								if(is_numeric($coupons[$x][4]))
								{
									$label .= '<br/><small>' . sprintf(__('This coupon is valid for booking of minimum %s %s.', 'dynamicpackages'), esc_html($coupons[$x][4]), esc_html(dy_utilities::duration_label($duration_unit, $coupons[$x][4]))).'</small>';
								}
							}
							
							if(!empty($expiration))
							{
								$label .= '<br/><small>'.esc_html(__('Offer expires on', 'dynamicpackages'));
								$label .= ' '.esc_html(date_i18n(get_option('date_format' ), strtotime($coupons[$x][2]))).'.</small>';
							}
							$label = apply_filters('coupon_gateway', $label, $coupons[$x][0]);
							$output .= $label;
							$output .= '</div>';								
						}
					}

				}
			}
			echo $output;
		}
	}
	public function count_child($this_id = null)
	{
		if($this_id == null)
		{
			global $post;
			$this_id = $post->ID;
		}
		
		$pages = get_pages( array( 'child_of' => $this_id, 'post_type' => 'packages'));
		
		if(is_array($pages))
		{
			return count($pages);
		}
	}
	public function similar_packages_link()
	{
		$output = '';
		$cache_key = 'dy_similar_packages_link';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		global $post;
		
		if(($post instanceof WP_Post) && dy_validators::is_child())
		{
			$similar_packages_label = isset($this->current_language)
				&& package_field('package_child_title_'.$this->current_language, $post->post_parent)
				? package_field('package_child_title_'.$this->current_language, $post->post_parent)
				: __('Similar packages', 'dynamicpackages');

			$output = '<div class="bottom-20"><a class="pure-button rounded block width-100 borderbox strong" href="'.esc_url(get_the_permalink($post->post_parent)).'"><span class="dashicons dashicons-arrow-left"></span>'.esc_html($this->count_child($post->post_parent)).' '.esc_html($similar_packages_label).'</a></div>';			
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
	}
	
	public static function meta_description($description)
	{
		if(is_singular('packages'))
		{
			$starting_at = (dy_validators::has_children()) ? dy_utilities::starting_at_archive() : dy_utilities::starting_at();
			
			if($starting_at > 0)
			{
				$description = (empty($description)) ? get_the_title() : $description;
				$description = rtrim(trim($description), '.') . '. ' . __('From', 'dynamicpackages') . ' ' . wrap_money_full($starting_at) . ' '.apply_filters('dy_price_type', false) . '.';
			}			
		}
		
		return $description;
	}

	public function event_arr()
	{
		$output = [];
		$cache_key = 'event_arr';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$package_start_address = package_field('package_start_address');
		$package_start_hour = package_field('package_start_hour');

		if(!empty($package_start_address) && !empty($package_start_hour))
		{
			$from = intval(package_field('package_booking_from'));
			$to = intval(package_field('package_booking_to'));

			if($from >= 0 && $to > $from)
			{
				$new_range = [];
				$today = date('Y-m-d', strtotime("+ {$from} days", dy_strtotime('now')));
				$last_day = date('Y-m-d', strtotime("+ {$to} days", dy_strtotime('now')));
				$range = dy_utilities::get_date_range($today, $last_day);
				$disabled_range = dy_utilities::get_disabled_range();
				$week_days = dy_utilities::get_week_days_list();
				$count_range = 	(count($range) <= 30) ? count($range) : 30;
										
				for($x = 0; $x < $count_range; $x++)
				{
					if(!in_array($range[$x], $disabled_range))
					{
						$day = dy_date('N', dy_strtotime($range[$x]));
						
						if(!in_array($day, $week_days))
						{
							array_push($new_range, $range[$x]);
						}
					}
				}
				
				if(is_array($new_range))
				{
					if(count($new_range) > 0)
					{
						$output = $new_range;
					}
				}
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}



	public function template_redirect()
	{
		// Only front end, only GET (avoid breaking form submits/previews/AJAX/REST/cron).
		if ( is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || wp_doing_cron() ) {
			return;
		}
		if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
			return;
		}

		if(
			is_singular('packages') === false 
			|| is_404()
			|| is_main_query() === false
		) {
			return;
		}

		$lang = current_language();
		$redirect_page = (string) package_field('package_redirect_page');
		$redirect_url = (string) package_field('package_redirect_url_' . $lang);

		if($redirect_url === '' || filter_var($redirect_url, FILTER_VALIDATE_URL) === false) {
			return;
		}

		$valid_redirect_page = (
			(!is_booking_page() && ($redirect_page === '' || $redirect_page === '0')
			||
			(is_booking_page() && $redirect_page === '1')
		)) ? true : false;

		if($valid_redirect_page) {
			wp_redirect( $redirect_url, 301 );
			exit;
		}

		return;
	}

	public function post_type_link($url, $post)
	{

		if (!is_object($post) || $post->post_type !== 'packages') {
			return $url;
		}

		$lang = current_language();
		$redirect_page = (string) package_field('package_redirect_page',  $post->ID);
		$redirect_url = (string) package_field('package_redirect_url_' . $lang, $post->ID);

		if($redirect_url === '' || filter_var($redirect_url, FILTER_VALIDATE_URL) === false)
		{
			return $url;
		}
		
		if($redirect_page === '' || $redirect_page === '0')
		{
			$url = $redirect_url;
		}

		return $url;
	}

	public function edit_link() {

		$the_id = get_dy_id();
		$cache_key = 'dy_edit_link' . '_' . $the_id;

		if (isset(self::$cache[$cache_key])) {
			return self::$cache[$cache_key];
		}

		$url = get_edit_post_link( $the_id );

		$is_logged = false;

		if(is_user_logged_in() && array_intersect(array('editor', 'administrator', 'author', 'contributor'), wp_get_current_user()->roles))
		{
			$is_logged = true;
		}

		$link = ($is_logged) 
			? '<a target="_blank" class="pure-button text-muted rounded dy-edit-link small pure-button-bordered width-100" href="'.esc_url($url).'">'.__('Edit').' <span class="dashicons dashicons-edit"></span></a>'
			: '';

		self::$cache[$cache_key] = $link;

		echo $link;
	}
}