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
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'), 11);

		//redirect
		add_filter('wp_headers', array(&$this, 'redirect'));
		add_filter('post_type_link', array(&$this, 'post_type_link'), 10, 2);

		//template
		add_filter('template_include', array(&$this, 'package_template'), 99);
		add_filter('the_content', array(&$this, 'the_content'), 100);
		add_filter('pre_get_document_title', array(&$this, 'wp_title'), 100);
		add_filter('wp_title', array(&$this, 'wp_title'), 100);
		add_filter('the_title', array(&$this, 'modify_title'), 100);
		add_filter('single_term_title', array(&$this, 'modify_tax_title'));
		add_filter('get_the_excerpt', array(&$this, 'modify_excerpt'));
		add_filter('term_description', array(&$this, 'modify_term_description'));
		add_action('pre_get_posts', array(&$this, 'set_one_tax_per_page'));

		//meta tags
		add_action('wp', array(&$this, 'remove_canonicals'));
		add_action('wp_head', array(&$this, 'meta_tags'));
		add_action('wp_head', array(&$this, 'location_category_canonical'));
		add_filter('get_the_excerpt', array(&$this, 'modify_excerpt'));
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
		add_action('dy_get_taxonomies_list', array(&$this, 'get_taxonomies_list'));
		add_action('dy_get_included_list', array(&$this, 'get_included_list'));
		add_action('dy_get_not_included_list', array(&$this, 'get_not_included_list'));
		add_action('dy_get_category_list', array(&$this, 'get_category_list'));
		add_action('dy_get_location_list', array(&$this, 'get_location_list'));
		add_action('dy_show_badge', array(&$this, 'show_badge'));
		add_action('dy_show_event_date', array(&$this, 'show_event_date'));
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

		if(is_tax('package_category') || is_tax('package_location') || is_post_type_archive('packages') || (is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'packages')))
		{
			wp_enqueue_script('dynamicpackages-archive', $this->plugin_dir_url_file . 'js/dynamicpackages-archives.js', array('jquery', 'dy-core-utilities'), $this->version, true );
		}
		
	}
		
	public function meta_tags()
	{
		global $post;
		global $dy_request_invalids;

		if(is_singular('packages'))
		{		
			if(is_booking_page() || isset($dy_request_invalids) || is_checkout_page())
			{	
				echo '<meta name="robots" content="noindex, nofollow" />';
			}
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
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			return '<p class="minimal_alert">'.json_encode($dy_request_invalids).'</p>';
		}

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
				if(dy_validators::is_child())
				{
					
					$subpackage_name = 'package_child_title';
					
					if(isset($polylang))
					{
						$subpackage_name .= '_'.pll_get_post_language($post->ID);
					}
					
					$subpackage = package_field($subpackage_name);

					if(!empty($subpackage))
					{
						$content = '<h2>'.esc_html($subpackage).'</h2>' . $content;
					}
					
					$parent_content = do_blocks(get_post($post->post_parent)->post_content);
					
					$content .= $parent_content;
				}
				
				$GLOBALS['new_content'] = $content;
				
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
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			return __('Error', 'dynamicpackages');
		}

		if(is_tax())
		{
			$title = single_term_title( '', false );
						
			if(is_tax('package_location') || is_tax('package_category'))
			{
				$tax = get_taxonomy( get_queried_object()->taxonomy );	
				
				$tax_title_modifier = null;
				$tax_title_modifier = get_term_meta( get_queried_object()->term_id, 'tax_title_modifier', true);
				
				if(strlen($tax_title_modifier) > strlen(single_term_title( '', false )))
				{
					$title = $tax_title_modifier;
				}				
				
				if(is_tax('package_location'))
				{
					if($tax_title_modifier == null)
					{
						$packages_in = __('Packages in', 'dynamicpackages');
						$title = $packages_in.' '.$title;
						
						if(get_queried_object()->parent > 0)
						{
							$title .= ', '.get_term(get_queried_object()->parent)->name;
						}						
					}
				}
			}
			$title .= ' | '. get_bloginfo( 'name' );
		}
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
		elseif(is_page())
		{
			if(dy_validators::validate_category_location())
			{
				$location = '';
				$category = '';
				$title = __('Find Packages', 'dynamicpackages').': ';
				
				if(isset($_GET['keywords']))
				{
					$keywords_param = sanitize_text_field($_GET['keywords']);

					if(!empty($keywords_param))
					{
						$keywords = strtolower($keywords_param);
						$keywords = preg_replace('/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $keywords);
						$keywords =  preg_replace('/\s+/', ' ', $keywords);
						$keywords =  substr($keywords, 0, 25);
						$title .= '"'.$keywords.'" ';						
					}
				}					
				
				if(isset($_GET['category']))
				{
					$category_param = sanitize_text_field($_GET['category']);

					if($category_param !== 'any' && !empty($category_param))
					{
						$category = get_term_by('slug', $category_param, 'package_category');
						
						if(is_object($category))
						{
							$title .= $category->name;							
						}
					}
				}				
				
				if(isset($_GET['location']))
				{
					$location_param = sanitize_text_field($_GET['location']);

					if($location_param !== 'any' && !empty($location_param))
					{
						$location = get_term_by('slug', $location_param, 'package_location');
						
						if(is_object($location))
						{
							$location_name = $location->name;
							
							if(intval($location->parent) > 0)
							{
								$location_parent = get_term_by('id', $location->parent, 'package_location');
								$location_name .= ', '.$location_parent->name;
							}
							
							if(is_object($category))
							{
								$title .= ' ';
							}

							$title .= $location_name;							
						}
					}
				}

				if(isset($_GET['sort']))
				{
					$sort_param = sanitize_text_field($_GET['sort']);
					$sort_title_labels = $this->get_sort_title_labels();

					if(array_key_exists($sort_param, $sort_title_labels))
					{
						$title .= ' ('.$sort_title_labels[$sort_param].')';
					}					
				}
				
				$paged = current_page_number();
				
				if($paged > 1)
				{
					$title .= ' | '.__('Page', 'dynamicpackages').' '.$paged;
				}
				
				
				$title .= ' | '.get_bloginfo( 'name');
			}
		}

		return $title;
	}
	
	public function modify_title($title)
	{
		global $dy_request_invalids;
		global $post;

		if(!in_the_loop())
		{
			return $title;
		}

		if(isset($dy_request_invalids))
		{
			return __('Error', 'dynamicpackages');
		}

		if(is_tax('package_location') || is_tax('package_category'))
		{
			$tax = get_taxonomy( get_queried_object()->taxonomy );
			$tax_title_modifier = null;
			$tax_title_modifier = get_term_meta( get_queried_object()->term_id, 'tax_title_modifier', true);
			$title = single_term_title( '', false );
			
			if(strlen($tax_title_modifier) > strlen(single_term_title( '', false )))
			{
				$title = $tax_title_modifier;
			}				
			
			if(is_tax('package_location'))
			{
				if($tax_title_modifier == null)
				{
					$packages_in = __('Packages in', 'dynamicpackages');
					$title = $packages_in.' <span class="linkcolor">'. $title .'</span>';
					
					if(get_queried_object()->parent > 0)
					{
						$title .= ', '. get_term(get_queried_object()->parent)->name;
					}						
				}
			}
		}
		
		if(is_singular('packages'))
		{				
			if(is_booking_page())
			{
				$title = sprintf(__('Booking Page: %s', 'dynamicpackages'), $title);
			}			
		}
		elseif(is_page())
		{
			if(dy_validators::validate_category_location())
			{
				$location = '';
				$category = '';
				$title = __('Find Packages', 'dynamicpackages') . ': ';

				if(isset($_GET['keywords']))
				{
					$keywords_param = sanitize_text_field($_GET['keywords']);

					if(!empty($keywords_param))
					{
						$keywords = strtolower($keywords_param);
						$keywords = preg_replace('/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $keywords);
						$keywords =  preg_replace('/\s+/', ' ', $keywords);
						$keywords =  substr($keywords, 0, 25);
						$title .= '"'.$keywords.'" ';							
					}
				}						
						
				if(isset($_GET['category']))
				{
					$category_param = sanitize_text_field($_GET['category']);

					if($category_param !== 'any' && !empty($category_param))
					{
						$category = get_term_by('slug', $category_param, 'package_category');
						
						if(is_object($category))
						{
							$title .= $category->name;
						}
					}
				}					
				
				if(isset($_GET['location']))
				{
					$location_param = sanitize_text_field($_GET['location']);

					if($location_param !== 'any' && !empty($location_param))
					{
						$location = get_term_by('slug', $location_param, 'package_location');
						
						if(is_object($location))
						{
							if(is_object($category))
							{
								$title .= ' ';
							}
							
							$title .= $location->name;

							if($location->parent > 0)
							{
								$location_parent = get_term_by('id', $location->parent, 'package_location');
								$title .= ', '.$location_parent->name;
							}								
						}
					}	
				}	
				
				if(isset($_GET['sort']))
				{
					$sort_param = sanitize_text_field($_GET['sort']);
					$sort_title_labels = $this->get_sort_title_labels();

					if(array_key_exists($sort_param, $sort_title_labels))
					{
						$title .= ' ('.$sort_title_labels[$sort_param].')';
					}					
				}
			}
			
			$paged = current_page_number();
			
			if($paged > 1)
			{
				$title .= ' | '.__('Page', 'dynamicpackages').' '.$paged;
			}			
		}


		return $title;
	}

	public function get_sort_title_labels(){
		return array(
			'new' => __('Newest', 'dynamicpackages'),
			'low' => __('low to high', 'dynamicpackages'),
			'high' => __('high to low', 'dynamicpackages'),
			'today' => __('today', 'dynamicpackages'),
			'tomorrow' => __('tomorrow', 'dynamicpackages'),
			'week' => __('next 7 days', 'dynamicpackages'),
			'month' => __('next 30 days', 'dynamicpackages'),
		);
	}
	
	public function modify_tax_title($title)
	{
		if(is_tax('package_terms_conditions') && in_the_loop())
		{
			$title = '<span class="linkcolor">'.$title.'</span>';
		}
		return $title;
	}

	
	public static function price_type()
	{
		$name = 'dy_price_type';
		$the_id = get_dy_id();
		$cache_key = $name.'_'.$the_id;

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$price_type = intval(package_field('package_fixed_price'));
		$package_type = intval(package_field('package_package_type'));
		$duration = intval(package_field('package_duration'));
		$duration_unit = intval(package_field('package_length_unit'));
		$duration_max = intval(package_field('package_duration_max'));
		$output = '';
		
		if($price_type === 0)
		{
			$output = __('Per Person', 'dynamicpackages').' ';
		}


		if($package_type === 1)
		{
			if($duration === 1 && $duration_max > $duration)
			{
				$output .= __(' / ', 'dynamicpackages').dy_utilities::duration_label($duration_unit, 1);
			}
		}
		else if(dy_utilities::package_type_by_hour())
		{
			$output .= __('Per Hour', 'dynamicpackages');
		}
		else if(dy_utilities::package_type_by_day())
		{
			$output .=__('Per Day', 'dynamicpackages');
		}
		else if(dy_validators::package_type_transport())
		{
			$output .=__('One-way', 'dynamicpackages');
		}

        //store output in $cache
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


	public function get_taxonomies_list()
	{
		$output = '';
		$cache_key = 'dy_get_taxonomies_list';

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
		global $post;
	
		// Guard: nothing to do without a post or booking date
		if ( empty( $post ) || empty( $_REQUEST['booking_date'] ) ) {
			return '';
		}
	
		// Core flags & data
		$isTransport    = dy_validators::package_type_transport();
		$routeRaw       = sanitize_text_field( $_REQUEST['route'] ?? '' );
		$modifyRoute    = $isTransport && $routeRaw !== '0';
	
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
			'discount' => intval( sanitize_text_field( $_REQUEST['pax_discount'] ?? 0 ) ),
			'free'     => intval( sanitize_text_field( $_REQUEST['pax_free'] ?? 0 ) ),
		];
		$ages = [
			'discount' => package_field( 'package_discount' ) ?: 0,
			'free'     => package_field( 'package_free' ) ?: 0,
		];
	
		// Build each “X persons” chunk
		$chunks = [];
		foreach ( $counts as $type => $num ) {
			if ( $num < 1 ) {
				continue;
			}
			switch ( $type ) {
				case 'adults':
					$label = _n( 'person', 'persons', $num, 'dynamicpackages' );
					$chunks[] = sprintf( '%d %s', $num, $label );
					break;
				case 'discount':
				case 'free':
					$labelAge = $ages[ $type ];
					$label = _n( 'adult', 'adults', $num, 'dynamicpackages' );
					$chunks[] = sprintf( '%d %s %d %s',
						$num,
						$label,
						$labelAge,
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
				$depLabel = $modifyRoute
					? "{$returnShort}-{$startShort}"
					: "{$startShort}-{$returnShort}";
				$retLabel = $modifyRoute
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
				$modifyRoute ? $retHour : $depHour
			);
			$tripType = __( 'One-way', 'dynamicpackages' );
	
			if ( $hasReturn ) {
				$itinerary .= ' | ' . sprintf(
					'%s %s %s',
					$retLabel,
					$retDate,
					$modifyRoute ? $depHour : $retHour
				);
				$tripType = __( 'Round trip', 'dynamicpackages' );
			}
		} else {
			$itinerary = trim( sprintf( '%s %s', $depDate, $depHour ) );
			$tripType  = dy_utilities::show_duration();
		}
	
		// Final assembly
		return sprintf(
			'%s | %s (%s): %s',
			$tripType,
			$post->post_title,
			$itinerary,
			$peopleStr
		);
	}
	
	
	public function show_badge()
	{
		$output = '';
		$code = package_field('package_badge');
		
		if($code > 0)
		{
			$color = package_field('package_badge_color');
			$messages = array(null, __('Best Seller', 'dynamicpackages'), __('New', 'dynamicpackages'), __('Offer', 'dynamicpackages'), __('Featured', 'dynamicpackages'), __('Last Minute Deal', 'dynamicpackages') );
			
			$output = '<small class="dy_badge_class '.esc_html($color).'">'.esc_html($messages[$code]).'</small>';
		}
		
		echo $output;
	}
	
	public function children_package()
	{
		$cache_key = 'dy_children_package';
		$output = '';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		global $post;
		
		if(!dy_validators::is_child() && isset($post))
		{
			$duration = package_field('package_duration');
			$duration_unit = package_field('package_length_unit');
			$header_name = 'package_child_title_'.$this->current_language;
			$header_title = package_field($header_name, $post->ID);
			$label = (empty($header_title)) ? __('Packages', 'dynamicpackages') : $header_title;
			
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
					$rows_arr = array();
					
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
								$row .= '<td><a class="strong pure-button pure-button-primary rounded block width-100 borderbox" href="'.esc_url(rtrim(get_the_permalink(), '/').'/'.$item->post_name.'/').'">'.esc_html($button_label).' <span class="dashicons dashicons-arrow-right"></span></a></td>';
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

						$output .= '<table class="pure-table pure-table-bordered bottom-20"><thead class="text-center"><tr><th colspan="3"><strong>'.esc_html($this->count_child()).'</strong> '.esc_html($label).':</th></tr></thead><tbody class="small">'.$rows.'</tbody></table>';
					}		
				}
			}			
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
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
						$excerpt .= ' '.currency_symbol().intval(dy_utilities::starting_at()).' '.apply_filters('dy_price_type', null).'. ';
						
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
		elseif(is_page())
		{
			if(is_object($post))
			{
				if(dy_validators::validate_category_location() && has_shortcode($post->post_content, 'packages'))
				{
					$excerpt = null;
				}				
			}
		}
		
		return $excerpt;
	}
	

	public function location_category_canonical()
	{
		if(dy_validators::validate_category_location())
		{
			$paged = current_page_number();
			$url = get_the_permalink();
			$url_var = array();
			
			if($paged > 1)
			{
				$slug = 'page/' . $paged;

				if(!is_front_page())
				{
					$slug = '/' . $slug;
				}

				$url = $url . $slug;
			}
		
			if(isset($_GET['location']))
			{
				$url_var['location'] = sanitize_text_field($_GET['location']);
			}
			if(isset($_GET['category']))
			{
				$url_var['category'] = sanitize_text_field($_GET['category']);
			}
			if(isset($_GET['sort']))
			{
				$sort_by_value = sanitize_text_field($_GET['sort']);

				if(!empty($sort_by_value) || $sort_by_value !== 'any')
				{
					$sort_by_arr = dy_utilities::sort_by_arr();

					if(in_array($sort_by_value, $sort_by_arr))
					{
						$url_var['sort'] = $sort_by_value;
					}
				}

			}
			if(isset($_GET['keywords']))
			{
				if(!empty($_GET['keywords']))
				{
					$search = strtolower(sanitize_text_field($_GET['keywords']));
					$search = preg_replace('/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $search);
					$search =  preg_replace('/\s+/', ' ', $search);				
					$search =  substr($search, 0, 25);
					$url_var['keywords'] = $search;
				}
			}			

			$url = $url.'?';

			$url_var = http_build_query($url_var);
			
			echo '<link rel="canonical" href="'.esc_url($url.$url_var).'" />';
		}
		else
		{
			if(current_page_number() > 1)
			{
				$paged = current_page_number();
				$url = get_the_permalink();
				$slug = 'page/' . $paged;

				if(!is_front_page())
				{
					$slug = '/' . $slug;
				}

				echo '<link rel="canonical" href="'.esc_url($url. $slug).'" />';
			}
		}
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
	

	
	public function get_all_coupons()
	{
		global $get_all_coupons;
		$output = [];
		
		if(isset($get_all_coupons))
		{
			$output = $get_all_coupons;
		}
		else
		{
			$coupons = dy_utilities::get_package_hot_chart('package_coupons');
			
			if(array_key_exists('coupons', $coupons))
			{
				$output = $coupons['coupons'];
				$GLOBALS['get_all_coupons'] = $output;
			}			
		}
		return $output;
	}

	public function enabled_days()
	{
		$output = '';
		$days = dy_utilities::get_week_days_abbr();
		$labels = dy_utilities::get_week_day_names_short();
		$labels_lon = dy_utilities::get_week_day_names_long();		
		$enabled_days = array();
		$enabled_days_lon = array();
		$event_date = package_field('package_event_date');
		
		for($x = 0; $x < count($days); $x++)
		{
			$day = 'package_day_'.$days[$x];
			
			if(package_field($day) != 1)
			{
				array_push($enabled_days, $labels[$x]);
				array_push($enabled_days_lon, $labels_lon[$x]);
			}
		}
		
		if(count($enabled_days) > 0 && count($enabled_days) < 3)
		{
			$output = implode(', ', $enabled_days_lon);
		}
		else if(count($enabled_days) == 7)
		{
			$output = __('Everyday', 'dynamicpackages');
		}
		else
		{
			$output = implode(', ', $enabled_days);
		}

		if(!empty($event_date))
		{
			if(is_valid_date($event_date))
			{
				$output = dy_utilities::format_date(strtotime($event_date));
			}
			
		}
		
		return $output;
	}
	
	public function details()
	{
		global $dy_is_archive;
		$is_archive = (isset($dy_is_archive)) ? true : false;
		$booking_date = (dy_utilities::booking_date()) ? dy_utilities::format_date(dy_utilities::booking_date()) : null;
		$end_date = (dy_utilities::end_date()) ? dy_utilities::format_date(dy_utilities::end_date()) : null;
		
		$is_transport = dy_validators::package_type_transport();
		$is_checkout_page = is_checkout_page();
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
		$modify_route = $is_transport
			&& isset($_REQUEST['route'])
			&& sanitize_text_field($_REQUEST['route']) !== '0';

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
			'enabled_days'    => [ 'calendar',      $this->enabled_days() ],
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
			if($this->enabled_days()) $req[] = 'enabled_days';
			if($schedule) $req[] = 'schedule';
			if($show_max_persons) $req[] = 'max_persons';
			if(dy_utilities::hour() && $is_transport_fixed === false) $req[] = 'start_hour';
			if(!get_option('dy_archive_hide_start_address')) $req[] = 'start_address';
			if(!get_option('dy_archive_hide_enabled_days')) $req[] = 'enabled_days';

		}
		else if(is_singular('packages') && !$is_booking_page && !$is_checkout_page)
		{

			$show_labels = true;
			$req[] = 'duration';
			if($this->enabled_days()) $req[] = 'enabled_days';
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
		else if($is_booking_page || $is_checkout_page)
		{
			if($is_booking_page)
			{
				$show_labels = true;
			}



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
					$output .= '<div class="dy_pad bottom-5 dashicons-before dashicons-'.esc_attr($v[0]).'"> '.esc_html($v[1]).'</div>';
				}
				else
				{
					$output .= '<div class="dy_pad bottom-5 strong">'.esc_html($v[1]).'</div>';
				}
			}
		}
		
		return $output;
	}
	

	public function show_event_date()
	{
		$output = '';
		
		if(!empty(package_field('package_event_date')))
		{
			$date = strtotime(package_field('package_date'));
			$today = strtotime('today');
			$tomorrow = strtotime('tomorrow');
			
			if($date == $today)
			{
				$output = '<small class="dy_event_date_class">';	
				$output .= __('today', 'dynamicpackages');
				$output .= '</small>';
			}
			elseif($date == $tomorrow)
			{
				$output = '<small class="dy_event_date_class">';	
				$output .= __('tomorrow', 'dynamicpackages');
				$output .= '</small>';
			}
			else
			{
				$date = date_i18n('M d', $date);
				$output = '<small class="dy_event_date_class">';	
				$output .= esc_html($date);	
				$output .= '</small>';				
			}
		}
		else
		{
			if(isset($_GET['sort']))
			{
				if($_GET['sort'] == 'today' || $_GET['sort'] == 'tomorrow' || $_GET['sort'] == 'week' || $_GET['sort'] == 'month')
				{
					$date = strtotime(package_field('package_date'));
					
					if($_GET['sort'] == 'today')
					{
						$label = __('today', 'dynamicpackages');
					}
					elseif($_GET['sort'] == 'tomorrow')
					{
						$label = __('tomorrow', 'dynamicpackages');
					}
					elseif($_GET['sort'] == 'week')
					{
						$label = __('next 7 days', 'dynamicpackages');
					}
					elseif($_GET['sort'] == 'month')
					{
						$label = __('next 30 days', 'dynamicpackages');
					}
					
					
					$output = '<small class="dy_event_date_class">';	
					$output .= esc_html($label);
					$output .= '</small>';	
				}
			}
		}
		echo $output;
	}
	
	public function show_coupons()
	{
		if(dy_validators::has_coupon())
		{
			$duration_unit = package_field('package_length_unit');
			$coupons = $this->get_all_coupons();
			$output = '';			
						
			if(is_array($coupons))
			{
				for($x = 0; $x < count($coupons); $x++)
				{
					if($coupons[$x][3] == 'true' && $coupons[$x][0])
					{
						$expiration = 0;

						if(!empty($coupons[$x][2]))
						{
							$expiration = new DateTime($coupons[$x][2]);
							$expiration->setTime(0,0,0);
							$expiration = $expiration->getTimestamp();
						}
						
						if($expiration >= strtotime('today midnight') || $expiration === 0)
						{
							$expiration = '';
							$valid = true;
							
							if(!empty($coupons[$x][2]))
							{
								$expiration = new DateTime($coupons[$x][2]);
								$expiration->setTime(0,0,0);
								$expiration = $expiration->getTimestamp();
								
								if($expiration < strtotime('today midnight'))
								{
									$valid = false;
								}
							}

							if($valid === true)
							{
								$label = '';
								$output .= '<div class="dy_coupon bottom-20 dy_pad">';
								$label .= esc_html(__('Get a', 'dynamicpackages'));
								$label .= ' <strong>'.esc_html($coupons[$x][1]).'%</strong>';
								$label .= ' '.esc_html(__('off using the coupon code', 'dynamicpackages'));
								$label .= ' <strong>'.strtoupper(esc_html($coupons[$x][0])).'</strong>.';
								
								if(isset($coupons[$x][4]) && !dy_validators::package_type_transport() && !dy_validators::is_package_single_day())
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
		
		if(isset($post) && dy_validators::is_child())
		{
			$output = '<div class="bottom-20"><a class="pure-button rounded block width-100 borderbox" href="'.esc_url(get_the_permalink($post->post_parent)).'"><span class="dashicons dashicons-arrow-left"></span> <strong>'.esc_html($this->count_child($post->post_parent)).'</strong> '.esc_html(__('Similar packages', 'dynamicpackages')).'</a></div>';			
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
				$description = rtrim(trim($description), '.') . '. ' . __('From', 'dynamicpackages') . ' ' . currency_symbol().$starting_at.' '.apply_filters('dy_price_type', null) . '.';
			}			
		}
		
		return $description;
	}

	public function event_arr()
	{
		$output = array();
		$cache_key = 'event_arr';
		
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		$package_start_address = package_field('package_start_address');
		$package_start_hour = package_field('package_start_hour');

		if(!empty($package_start_address) && !empty($package_start_hour))
		{
			$package_event_date = package_field('package_event_date');
			
			if(!empty($package_event_date))
			{
				$today = strtotime(dy_date('Y-m-d'));
				$event_date = strtotime(dy_date($package_event_date));
				
				if($event_date > $today)
				{
					array_push($output, $event_date);
				}
			}
			else
			{
				$from = intval(package_field('package_booking_from'));
				$to = intval(package_field('package_booking_to'));

				if($from >= 0 && $to > $from)
				{
					$new_range = array();
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
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function remove_canonicals()
	{

		if(dy_validators::validate_category_location())
		{
			remove_action('wp_head', 'rel_canonical');
		}
		else
		{
			$paged = current_page_number();

			if($paged > 1)
			{
				remove_action('wp_head', 'rel_canonical');
			}
		}

	}

	public function redirect($headers)
	{
		if(!is_singular('packages') || is_404())
		{
			return $headers;
		}

		$lang = current_language();

		$url = package_field('package_redirect_url_' . $lang);
		$redirect_page = package_field('package_redirect_page');
		$valid_redirect_page = (
			(!is_booking_page() && (empty($redirect_page) || intval($redirect_page) === 0)
			||
			(is_booking_page() && intval($redirect_page) === 1)
		)) ? true : false;

		if(!empty($url) && $valid_redirect_page)
		{
			if( filter_var($url, FILTER_VALIDATE_URL) !== false)
			{
				wp_redirect( $url, 301 );
				exit;
			}
		}
	}

	public function post_type_link($url, $post)
	{

		if (empty($post) || is_404() || is_customize_preview() || is_admin() || get_post_type($post) !== 'packages' || is_category() || is_tag()) {
			return $url;
		}

		$lang = current_language();
		$redirect = package_field('package_redirect_url_' . $lang, $post->ID);
		$redirect_page = package_field('package_redirect_page');

		if(empty($redirect))
		{
			return $url;
		}

		$valid_redirect_page = (empty($redirect_page) || intval($redirect_page) === 0) ? true : false;
		
		if(in_the_loop() || isset($_GET['minimal-sitemap']))
		{
			if( filter_var($redirect, FILTER_VALIDATE_URL) !== false && $valid_redirect_page)
			{
				$url = $redirect;
			}
		}


		return $url;
	}
}