<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://jaimelias.com
 * @since      1.0.0
 *
 * @package    dynamicpackages
 * @subpackage dynamicpackages/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    dynamicpackages
 * @subpackage dynamicpackages/public
 * @author     Jaimelías <jaimelias@about.me>
 */
class dy_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public function __construct() {
		
		$this->init();
	}
	
	public function init()
	{
		add_action('wp_headers', array(&$this, 'request_invalids'));
		add_action('wp_enqueue_scripts', array('dy_Public', 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array('dy_Public', 'enqueue_scripts'), 11);
		add_action('pre_get_posts', array('dy_Public', 'global_vars'));
		add_filter('template_include', array('dy_Public', 'package_template'), 99);
		add_filter('the_content', array('dy_Public', 'the_content'), 100);
		add_filter('pre_get_document_title', array('dy_Public', 'wp_title'), 100);
		add_filter('wp_title', array('dy_Public', 'wp_title'), 100);
		add_filter('the_title', array('dy_Public', 'modify_title'), 100);
		add_filter('single_term_title', array('dy_Public', 'modify_tax_title'));
		add_action('pre_get_posts', array('dy_Public', 'fix_multiple_tax'));
		add_action('wp_head', array('dy_Public', 'booking_head'));
		add_action('wp_head', array('dy_Public', 'meta_tags'));
		add_filter('get_the_excerpt', array('dy_Public', 'modify_excerpt'));
		add_filter('term_description', array('dy_Public', 'modify_term_description'));
		add_action('wp_head', array('dy_Public', 'location_category_canonical'));
		add_filter('jetpack_enable_open_graph', array('dy_Public', 'deque_jetpack'));
		add_filter('dy_package_details', array(&$this, 'details'));
		add_action('dy_package_description', array(&$this, 'description'));
		add_action('dy_show_coupons', array(&$this, 'show_coupons'));
		add_filter('minimal_description', array(&$this, 'meta_description'));
	}

	public function create_alert($row) {
		return '<p class="strong minimal_alert">'.esc_html($row).'</p>';
	}	
	public function create_alert_cb($output)
	{
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			if(is_array($dy_request_invalids))
			{
				if(count($dy_request_invalids) > 0)
				{
					$output = implode('', array_map(array(&$this, 'create_alert'), $dy_request_invalids));
				}
			}
		}		
		
		return $output;
	}
	
	public function request_invalids()
	{
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			if(is_array($dy_request_invalids))
			{
				if(count($dy_request_invalids) > 0)
				{
					add_filter('dy_request_invalids', array(&$this, 'create_alert_cb'));					
				}
			}
		}
	}
	
	public static function enqueue_styles() {
		
		if(!is_404())
		{
			if(is_tax('package_category') ||is_tax('package_location') || is_post_type_archive('packages'))
			{
				self::css();
			}
			else if(is_singular('packages'))
			{
				self::css();
				self::datepickerCSS();
			}
			else
			{
				global $post;
				
				if(isset($post))
				{
					if(has_shortcode( $post->post_content, 'packages') || has_shortcode( $post->post_content, 'package_contact'))
					{
						self::css();
					}					
				}			
			}
		}
	}
	
	public static function css()
	{
		wp_enqueue_style('minimalLayout', plugin_dir_url( __FILE__ ) . 'css/minimal-layout.css', array(), '', 'all');
		wp_add_inline_style('minimalLayout', self::get_inline_css('dynamicpackages-public'));
	}
	
	public static function get_inline_css($sheet)
	{
		ob_start();
		require_once(dirname( __FILE__ ) . '/css/'.$sheet.'.css');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}	

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public static function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in dynamicpackages_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The dynamicpackages_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */	 
		global $post;
		$dep = array( 'jquery', 'landing-cookies');
		$ipgeolocation = null;
		$enqueue_public = false;
		$enqueue_archive = false;
		$enqueue_recaptcha = false;
		$enqueue_sha512 = false;
		$enqueue_datepicker = false;
		
		wp_enqueue_script('landing-cookies', plugin_dir_url( __FILE__ ) . 'js/cookies.js', array('jquery'), '', true);
		
		if(isset($post))
		{
			if(is_singular('packages') || is_page())
			{
				$enqueue_public = true;
				
				if(is_booking_page() || has_shortcode( $post->post_content, 'package_contact'))
				{
					$enqueue_recaptcha = true;
				}

				if(!is_booking_page())
				{
					$enqueue_sha512 = true;
				}				
				
				if(!is_booking_page())
				{
					$enqueue_datepicker = true;
				}
			}			
		}

		if(is_tax('package_category') || is_tax('package_location') || is_post_type_archive('packages') || (is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'packages')))
		{
			$enqueue_archive = true;
		}		
		
		if($enqueue_recaptcha)
		{
			wp_enqueue_script('invisible-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=dy_recaptcha&render=explicit', array(), 'async_defer', true );
			array_push($dep, 'invisible-recaptcha');
		}
		if($enqueue_sha512)
		{
			wp_enqueue_script('sha512', plugin_dir_url( __FILE__ ) . 'js/sha512.js', array(), 'async_defer', true );
				array_push($dep, 'sha512');
		}
		
		if($enqueue_datepicker)
		{
			self::datepickerJS();
		}		
		
		if($enqueue_public)
		{
			wp_enqueue_script('dynamicpackages', plugin_dir_url( __FILE__ ) . 'js/dynamicpackages-public.js', $dep, time(), true );
			
			wp_add_inline_script('dynamicpackages', self::booking_head(), 'before');
			
			wp_add_inline_script('dynamicpackages', dy_Public::recaptcha_sitekey(), 'before');
			
 			wp_add_inline_script('dynamicpackages', 'function dy_ipgeolocation(){ return "'.esc_html(get_option('ipgeolocation')).'";}', 'before');

			 wp_add_inline_script('dynamicpackages', 'function dy_getTheId(){ return "'.esc_html(get_the_ID()).'";}', 'before');
		}
		
		if($enqueue_archive)
		{
			wp_enqueue_script('dynamicpackages-archive', plugin_dir_url( __FILE__ ) . 'js/dynamicpackages-archives.js', array('jquery'), '', true );
		}
		
		wp_enqueue_script('minimal-fontawesome', 'https://use.fontawesome.com/releases/v5.3.1/js/all.js?async=async', '', '', true);
	}
	
	public static function datepickerCSS()
	{
		wp_enqueue_style( 'picker-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.css', array(), 'jetcharters', 'all');
		wp_add_inline_style('picker-css', self::get_inline_css('picker/default.date'));
		wp_add_inline_style('picker-css', self::get_inline_css('picker/default.time'));		
	}
	
	public static function datepickerJS()
	{
		//pikadate
		wp_enqueue_script( 'picker-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.js', array('jquery'), '3.5.6', true);
		wp_enqueue_script( 'picker-date-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.date.js', array('jquery', 'picker-js'), '3.5.6', true);
		wp_enqueue_script( 'picker-time-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.time.js',array('jquery', 'picker-js'), '3.5.6', true);	
		wp_enqueue_script( 'picker-legacy', plugin_dir_url( __FILE__ ) . 'js/picker/legacy.js', array('jquery', 'picker-js'), '3.5.6', true);

		$picker_translation = 'js/picker/translations/'.substr(get_locale(), 0, -3).'.js';
				
		if(file_exists(dirname( __FILE__ ).'/'.$picker_translation))
		{
			wp_enqueue_script( 'picker-time-translation', plugin_dir_url( __FILE__ ).$picker_translation, array('jquery', 'picker-js'), '3.5.6', true);
		}		
	}
		

	public static function global_vars()
	{
		$date_from = package_field('package_booking_from');
		$date_to = package_field('package_booking_to');
		$min = package_field('package_min_persons');
		$max = package_field('package_max_persons');
		$auto_booking = package_field('package_auto_booking');
		$by_hour = package_field('package_by_hour');
		$min_hour = package_field('package_min_hour');
		$max_hour = package_field('package_max_hour');
		
		$GLOBALS['price_chart'] = dy_utilities::get_price_chart();
		$GLOBALS['min'] = ($min) ? $min : 1;
		$GLOBALS['max'] = ($max) ? $max : 1;
		$GLOBALS['date_from'] = ($date_from) ? $date_from : 0;
		$GLOBALS['date_to'] = ($date_to) ? $date_to : 365;					
		$GLOBALS['auto_booking'] = ($auto_booking) ? $auto_booking : 0;
		$GLOBALS['by_hour'] = ($by_hour) ? $by_hour : 0;		
		$GLOBALS['min_hour'] = ($min_hour) ? $min_hour : '6:00 AM';	
		$GLOBALS['max_hour'] = ($max_hour) ? $max_hour : '6:00 PM';
	}
	public static function meta_tags()
	{
		if(is_singular('packages'))
		{
			ob_start();
			require_once(dirname( __FILE__ ) . '/partials/meta-tags.php');
			$content = ob_get_contents();
			ob_end_clean();	
			echo $content;
		}
	}
	public static function package_template($template)
	{
		if(is_tax('package_terms_conditions') || is_tax('package_location') || is_tax('package_category') || 'packages' == get_post_type())
		{
			$new_template = locate_template( array( 'page.php' ) );
			return $new_template;			
		}	
		return $template;
	}
	public static function booking_sidebar()
	{
		ob_start();
		require_once(dirname( __FILE__ ) . '/partials/quote-form.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public static function the_content($content)
	{
		global $post;
		global $polylang;
		$dy_request_invalids = apply_filters('dy_request_invalids', null);
		
		if($dy_request_invalids)
		{
			$content = $dy_request_invalids;
		}
		else
		{
			if(is_tax('package_location') || is_tax('package_category'))
			{
				ob_start();
				require_once(dirname( __FILE__ ) . '/partials/archive.php');
				$content = ob_get_contents();
				ob_end_clean();
			}
			else if(is_tax('package_terms_conditions'))
			{
				$Parsedown = new Parsedown();
				$content = $Parsedown->text(get_term(get_queried_object()->term_id)->description);
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
						
						if(package_field($subpackage_name) != '')
						{
							$content = '<h2>'.esc_html(package_field($subpackage_name)).'</h2>' . $content;
						}
						
						$parent_content = do_blocks(get_post($post->post_parent)->post_content);
						
						$content .= $parent_content;
					}
					
					$GLOBALS['new_content'] = $content;
					
					ob_start();
					require_once(dirname( __FILE__ ) . '/partials/single.php');
					$content = ob_get_contents();
					ob_end_clean();	

				}
			}			
		}
		
		return $content;
	}

	public static function disabled_dates()
	{
		$disable = array();
		$disable['disable'] = array();
		$days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
		
		if(package_field('package_event_date') == '')
		{
			for($x = 0; $x < count($days); $x++)
			{
				if(intval(package_field('package_day_'.$days[$x] )) == 1)
				{
					array_push($disable['disable'], $x+1);
				}
			}

			$time = date('Y-m-d');
			$from = intval(package_field('package_booking_from'));
			
			if($from == 0)
			{
				$from = true;
			}
			
			$to = intval(package_field('package_booking_to'));
			
			$disable['min'] = $from;
			$disable['max'] = $to;	
			$disabled_dates = array();
			$get_disabled_dates = json_decode(html_entity_decode(package_field('package_disabled_dates' )), true);
			$global_disabled_dates = json_decode(html_entity_decode(get_option('dy_disabled_dates' )), true);
			$get_enabled_dates = json_decode(html_entity_decode(package_field('package_enabled_dates' )), true);
			
			if(is_array($global_disabled_dates))
			{
				if(array_key_exists('disabled_dates', $global_disabled_dates))
				{
					$global_disabled_dates = $global_disabled_dates['disabled_dates'];
										
					for($x = 0; $x < count($global_disabled_dates); $x++)
					{
						$disabled_dates[] = $global_disabled_dates[$x];
					}
				}
			}
			
			if(is_array($get_disabled_dates))
			{
				if(array_key_exists('disabled_dates', $get_disabled_dates))
				{		
					$get_disabled_dates = $get_disabled_dates['disabled_dates'];
											
					for($x = 0; $x < count($get_disabled_dates); $x++){
						$disabled_dates[] = $get_disabled_dates[$x];
					}
				}
			}				
			
			if(is_array($disabled_dates))
			{
				for($x = 0; $x < count($disabled_dates); $x++)
				{
					if($disabled_dates[$x][0] && $disabled_dates[$x][1])
					{
						$period = new DatePeriod(
							 new DateTime($disabled_dates[$x][0]),
							 new DateInterval('P1D'),
							 new DateTime(date('Y-m-d', strtotime($disabled_dates[$x][1] . ' +1 day')))
						);
						
						$range = array();
						$range_fix = array();
						
						foreach ($period as $key => $value)
						{
							$this_date = $value->format('Y-m-d');
							$this_date = explode("-", $this_date);
							$this_date = array_map('intval', $this_date);
							$this_date = array_map(function($arr, $keys){
								if($keys == 1)
								{
									$arr = $arr - 1;
								}
								return $arr;
							}, $this_date, array_keys($this_date));
							$disable['disable'][] = $this_date;
						}						
					}
				}			
			}
		
			$api_disabled_endpoint = package_field('package_disabled_dates_api');
			
			if (filter_var($api_disabled_endpoint, FILTER_VALIDATE_URL) !== false)
			{
				$api_disabled_dates = wp_remote_get($api_disabled_endpoint);
				
				if(is_array($api_disabled_dates))
				{	
					if(array_key_exists('body', $api_disabled_dates))
					{
						$api_disabled_dates = json_decode($api_disabled_dates['body']);
						
						if(is_array($api_disabled_dates))
						{	
							for($x = 0; $x < count($api_disabled_dates); $x++)
							{
								if(dy_validators::is_date($api_disabled_dates[$x]))
								{
									$api_date = $api_disabled_dates[$x];
									$api_date = explode("-", $api_date);
									$api_date = array_map('intval', $api_date);
									$api_date = array_map(function($arr, $keys){
										if($keys == 1)
										{
											$arr = $arr - 1;
										}
										return $arr;
									}, $api_date, array_keys($api_date));
									$disable['disable'][] = $api_date;									
								}
							}
						}
					}					
				}
			}
			
			$enabled_dates = array();
			
			if(is_array($get_enabled_dates))
			{
				if(array_key_exists('enabled_dates', $get_enabled_dates))
				{		
					$get_enabled_dates = $get_enabled_dates['enabled_dates'];
										
					for($x = 0; $x < count($get_enabled_dates); $x++){
						$enabled_dates[] = $get_enabled_dates[$x];
					}
				}				
			}
			
			if(is_array($enabled_dates))
			{
				for($x = 0; $x < count($enabled_dates); $x++)
				{
					if($enabled_dates[$x][0] && $enabled_dates[$x][1])
					{
						$period = new DatePeriod(
							 new DateTime($enabled_dates[$x][0]),
							 new DateInterval('P1D'),
							 new DateTime(date('Y-m-d', strtotime($enabled_dates[$x][1] . ' +1 day')))
						);
						
						$range = array();
						$range_fix = array();
						
						foreach ($period as $key => $value)
						{
							$this_date = $value->format('Y-m-d');
							$valid_date = true;
							
							if(isset($api_disabled_dates))
							{
								if(is_array($api_disabled_dates))
								{
									if(in_array($this_date, $api_disabled_dates))
									{
										$valid_date = false;
									}
								}								
							}
							
							if($valid_date)
							{
								$this_date = explode("-", $this_date);
								$this_date = array_map('intval', $this_date);
								$this_date = array_map(function($arr, $keys){
									if($keys == 1)
									{
										$arr = $arr - 1;
									}							
									return $arr;
								}, $this_date, array_keys($this_date));
								
								$this_date[] = 'inverted';
								
								$disable['disable'][] = $this_date;								
							}
						}						
					}					
				}			
			}
			
			if(count($disable) > 0)
			{
				return $disable;
			}				
		}
	
	}
	
	public static function booking_head()
	{		
		if(is_singular('packages'))
		{
			$script = null;
			$by_hour = package_field('package_by_hour');		
			$min_hour = package_field('package_min_hour');		
			$max_hour = package_field('package_max_hour');
			
			if(intval(package_field('package_max_persons' )) > 0)
			{
				$script .= 'function booking_max(){return '.esc_html(package_field('package_max_persons' )).';}';
			}
			
			if($by_hour == 1)
			{
				$allowed_hours = array();
				
				if($min_hour != '')
				{
					$min_hour = strtotime($min_hour);
					array_push($allowed_hours, array(intval(date('H', $min_hour)), intval(date('i', $min_hour))));
				}
				if($max_hour != '')
				{
					$max_hour = strtotime($max_hour);
					array_push($allowed_hours, array(intval(date('H', $max_hour)), intval(date('i', $max_hour))));					
				}
				
				$script .= 'function booking_allowed_hours(){return '.json_encode($allowed_hours).';}';	
			}			
			
			$script .= 'function dy_url() { return "'.esc_url(plugin_dir_url( dirname(__FILE__) )).'";}';
			$script .= 'function dy_permalink() { return "'.esc_url(get_the_permalink()).'";}';
			
			return $script;
		}
	}
	
	public static function wp_title($title)
	{
		global $polylang;
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			$title = __('Error', 'dynamicpackages');
		}
		else
		{
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
							
							if($polylang)
							{
								$packages_in = pll__('Packages in');
							}
							
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
				
				global $post;
				if($post->post_parent > 0)
				{
					remove_action('wp_head', 'rel_canonical');
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
					
					if(isset($polylang))
					{
						$title = pll__('Find Packages').': ';
					}
					
					if(isset($_GET['package_search']))
					{
						if($_GET['package_search'] != '')
						{
							$search = strtolower(sanitize_text_field($_GET['package_search']));
							$search = preg_replace('/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $search);
							$search =  preg_replace('/\s+/', ' ', $search);
							$search =  substr($search, 0, 25);
							$title .= '"'.$search.'" ';						
						}
					}					
					
					if(isset($_GET['package_category']))
					{
						if($_GET['package_category'] != 'any' && $_GET['package_category'] != '')
						{
							$category = get_term_by('slug', sanitize_text_field($_GET['package_category']), 'package_category');
							
							if(is_object($category))
							{
								$title .= $category->name;							
							}
						}
					}				
					
					if(isset($_GET['package_location']))
					{
						if($_GET['package_location'] != 'any' && $_GET['package_location'] != '')
						{
							$location = get_term_by('slug', sanitize_text_field($_GET['package_location']), 'package_location');
							
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

					if(isset($_GET['package_sort']))
					{
						if($_GET['package_sort'] == 'new')
						{
							$title .= ' ('.__('Newest', 'dynamicpackages').')';
						}
						else if($_GET['package_sort'] == 'low')
						{
							$title .= ' ('.__('low to high', 'dynamicpackages').')';
						}
						else if($_GET['package_sort'] == 'high')
						{
							$title .= ' ('.__('high to low', 'dynamicpackages').')';
						}	
						else if($_GET['package_sort'] == 'today')
						{
							$title .= ' ('.__('today', 'dynamicpackages').')';
						}
						else if($_GET['package_sort'] == 'tomorrow')
						{
							$title .= ' ('.__('tomorrow', 'dynamicpackages').')';
						}					
						else if($_GET['package_sort'] == 'week')
						{
							$title .= ' ('.__('next 7 days', 'dynamicpackages').')';
						}	
						else if($_GET['package_sort'] == 'month')
						{
							$title .= ' ('.__('next 30 days', 'dynamicpackages').')';
						}						
					}
					
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
					
					if($paged > 1)
					{
						$title .= ' | '.__('Page', 'dynamicpackages').' '.$paged;
					}
					
					
					$title .= ' | '.get_bloginfo( 'name');
				}
			}			
		}

		return $title;
	}
	
	public static function modify_title($title)
	{
		global $post;
		global $polylang;
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			$title = __('Error', 'dynamicpackages');
		}
		else
		{
			if(in_the_loop())
			{
				
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
							
							if($polylang)
							{
								$packages_in = pll__('Packages in');
							}
							
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
						$our_awesome = __('Booking Page', 'dynamicpackages');
						
						if($polylang)
						{
							$our_awesome = pll__('Booking Page');
						}
						
						$title = '<span class="linkcolor">'. $our_awesome.'</span>: <span data-id="package-title">'. $title .'</span>';
					}			
					else
					{
						$title = '<span itemprop="name">'. $title.'</span>';
					}
					
				}
				elseif(is_page())
				{
					if(dy_validators::validate_category_location())
					{
						$location = '';
						$category = '';
						$title = __('Find Packages').': ';
						
						if(isset($polylang))
						{
							$title = pll__('Find Packages').': ';
						}

						if(isset($_GET['package_search']))
						{
							if($_GET['package_search'] != '')
							{
								$search = strtolower(sanitize_text_field($_GET['package_search']));
								$search = preg_replace('/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $search);
								$search =  preg_replace('/\s+/', ' ', $search);
								$search =  substr($search, 0, 25);
								$title .= '"'.$search.'" ';							
							}
						}						
								
						if(isset($_GET['package_category']))
						{
							if($_GET['package_category'] != 'any' && $_GET['package_category'] != '')
							{
								$category = get_term_by('slug', sanitize_text_field($_GET['package_category']), 'package_category');
								
								if(is_object($category))
								{
									$title .= $category->name;
								}
							}
						}					
						
						if(isset($_GET['package_location']))
						{
							if($_GET['package_location'] != 'any' && ($_GET['package_location'] != ''))
							{
								$location = get_term_by('slug', sanitize_text_field($_GET['package_location']), 'package_location');
								
								if(is_object($location))
								{
									if(is_object($category))
									{
										$title .= ' ';
									}
									
									$title .= $location->name;

									if(intval($location->parent) > 0)
									{
										$location_parent = get_term_by('id', $location->parent, 'package_location');
										$title .= ', '.$location_parent->name;
									}								
								}
							}	
						}
						if(isset($_GET['package_sort']))
						{
							if($_GET['package_sort'] == 'new')
							{
								$title .= esc_html(' ('.__('Newest', 'dynamicpackages').')');
							}
							else if($_GET['package_sort'] == 'low')
							{
								$title .= esc_html(' ('.__('low to high', 'dynamicpackages').')');
							}
							else if($_GET['package_sort'] == 'high')
							{
								$title .= esc_html(' ('.__('high to low', 'dynamicpackages').')');
							}		
							else if($_GET['package_sort'] == 'today')
							{
								$title .= esc_html(' ('.__('today', 'dynamicpackages').')');
							}
							else if($_GET['package_sort'] == 'tomorrow')
							{
								$title .= esc_html(' ('.__('tomorrow', 'dynamicpackages').')');
							}						
							else if($_GET['package_sort'] == 'week')
							{
								$title .= esc_html(' ('.__('next 7 days', 'dynamicpackages').')');
							}	
							else if($_GET['package_sort'] == 'month')
							{
								$title .= esc_html(' ('.__('next 30 days', 'dynamicpackages').')');
							}						
						}				
					}
					
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
					
					if($paged > 1)
					{
						$title .= ' | '.__('Page', 'dynamicpackages').' '.$paged;
					}			
				}			
			}			
		}
		
		return $title;
	}
	
	public static function modify_tax_title($title)
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
		$the_id = get_the_ID();
		$which_var = $name.'_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$price_type = package_field('package_fixed_price');
			$package_type = package_field('package_package_type');
			$duration = package_field('package_duration');
			$duration_unit = package_field('package_length_unit');
			$output = '';
			
			if(intval($price_type) == 0)
			{
				$output = __('Per Person', 'dynamicpackages').' ';
			}
			if($package_type == 1)
			{
				$output .= __(' / ', 'dynamicpackages').self::duration_label($duration_unit, 1);
			}
			if(dy_utilities::increase_by_hour())
			{
				$output .= __('Per Hour', 'dynamicpackages');
			}
			if(dy_utilities::increase_by_day())
			{
				$output .=__('Per Day', 'dynamicpackages');
			}
			if(dy_validators::is_package_transport())
			{
				$output .=__('One-way', 'dynamicpackages');
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}
	
	public static function duration_label($unit, $value)
	{
		//duration_label(unit number, duration value);
		$singular = array(__('Minute', 'dynamicpackages'), __('Hour', 'dynamicpackages'), __('Day', 'dynamicpackages'), __('Night', 'dynamicpackages'), __('Week', 'dynamicpackages'));
		$plural = array(__('Minutes', 'dynamicpackages'), __('Hours', 'dynamicpackages'), __('Days', 'dynamicpackages'), __('Nights', 'dynamicpackages'), __('Weeks', 'dynamicpackages'));
		$output = '';
		
		$label = $singular;
		
		if($value > 1)
		{
			$label = $plural;
		}
		
		
		return $label[$unit];
	}
	
	public static function show_duration($max = false)
	{
		
		$duration = package_field('package_duration');
		$duration_label = package_field('package_duration');
		$duration_unit = package_field('package_length_unit');
		$duration_max = package_field('package_duration_max');	
		
		if($duration != '' && $duration_unit != '')
		{
			if(dy_utilities::increase_by_hour() ||dy_utilities::increase_by_day() || intval($duration_unit) == 2 || intval($duration_unit) == 3)
			{
				if(dy_utilities::get_min_nights() != null)
				{
					$duration = dy_utilities::get_min_nights();
				}
			}
				
			if(!is_booking_page())
			{
				if($duration_max > $duration)
				{
					$duration_label = $duration;
					
					if($max === true)
					{
						$duration_label .= ' - '.$duration_max;
					}
				}			
			}
			else
			{
				$duration = dy_utilities::get_min_nights();
				$duration_label = $duration;
			}
			
			
			$duration_label_max = ($duration_max > $duration) ? $duration_max : $duration;
			$duration_label .= ' '.self::duration_label($duration_unit, $duration_label_max);
		}
		else
		{
			$duration_label = '';
		}
		return $duration_label;
	}
	
	public static function show_min_duration()
	{
		
		$tp_duration = package_field('package_duration');
		$tp_duration_unit = package_field('package_length_unit');
		$tp_duration_max = package_field('package_duration_max');		
		
		if(dy_utilities::increase_by_hour() ||dy_utilities::increase_by_day() || intval($tp_duration_unit) == 2 || intval($tp_duration_unit) == 3)
		{
			if(dy_utilities::get_min_nights() != null)
			{
				$tp_duration = dy_utilities::get_min_nights();
			}
		}
		
		$labels_singular = array(__('Minute', 'dynamicpackages'), __('Hour', 'dynamicpackages'), __('Day', 'dynamicpackages'), __('Night', 'dynamicpackages'), __('Week', 'dynamicpackages'));
		
		$labels_plural = array(__('Minutes', 'dynamicpackages'), __('Hours', 'dynamicpackages'), __('Days', 'dynamicpackages'), __('Nights', 'dynamicpackages'), __('Weeks', 'dynamicpackages'));		
		
		if($tp_duration != '' && $tp_duration_unit != '')
		{
			
			if($tp_duration == 1)
			{
				if($tp_duration_max > $tp_duration)
				{
					$output = $tp_duration.' '.$labels_plural[$tp_duration_unit];
				}
				else
				{
					$output = $tp_duration.' '.$labels_singular[$tp_duration_unit];
				}
			}
			else
			{
				$output = $tp_duration.' '.$labels_plural[$tp_duration_unit];
			}			
		}
		else
		{
			$output = null;
		}
		return $output;
	}	
	
	
	public static function get_location_list_ul($this_post)
	{
		$termid = $this_post->ID;
		
		if(property_exists($this_post, 'post_parent') && !has_term('', 'package_location', $termid))
		{
			$termid = $this_post->post_parent;
		}
		
		$label = '<p class="strong">'.esc_html(__('Places of Interest:', 'dynamicpackages')).'</p><ul class="tp_location"><li><i class="fas fa-map-marker" ></i> ';
		$locations = get_the_term_list( $termid, 'package_location', $label, '</li><li><i class="fas fa-map-marker" ></i> ', '</li></ul>');
		echo $locations;
	}
	public static function get_category_list_ul($this_post)
	{
		$termid = $this_post->ID;

		if(property_exists($this_post, 'post_parent') && !has_term('', 'package_category', $termid))
		{
			$termid = $this_post->post_parent;
		}		
		
		$label = '<p class="strong">'.esc_html(__('Categories:', 'dynamicpackages')).'</p><ul class="tp_location"><li><i class="fas fa-tags"></i> ';
		$locations = get_the_term_list( $termid, 'package_category', $label, '</li><li><i class="fas fa-tags"></i> ', '</li></ul>');
		echo $locations;
	}	
	public static function get_terms_conditions($post_id = null)
	{
		$terms_conditions = array();
		
		if($post_id == null)
		{
			global $post;
		}
		else
		{
			$post = get_post($post_id);
		}
		
		if(isset($post))
		{
			if(property_exists($post, 'ID'))
			{
				$termid = $post->ID;
				
				if(property_exists($post, 'post_parent'))
				{
					$termid = $post->post_parent;
				}		
				
				$terms = get_the_terms( $termid, 'package_terms_conditions');

				
				if($terms)
				{
					for($x = 0; $x < count($terms); $x++)
					{
						array_push($terms_conditions, $terms[$x]);
					}			
				}	
			}			
		}
		
		return $terms_conditions;
	}
	public static function get_terms_conditions_list($this_post)
	{
		$termid = $this_post->ID;
		
		if(property_exists($this_post, 'post_parent'))
		{
			$termid = $this_post->post_parent;
		}		
		
		$label = '<p class="strong">'.esc_html(__('Terms & Conditions:', 'dynamicpackages')).'</p><ul class="tp_location"><li><i class="fas fa-exclamation-triangle" ></i> ';
		$terms_conditions = get_the_term_list( $termid, 'package_terms_conditions', $label, '</li><li><i class="fas fa-exclamation-triangle" ></i> ', '</li></ul>');
		echo $terms_conditions;		
	}	
	public static function get_included_list($this_post)
	{
		$termid = $this_post->ID;
		$output = '';
		
		if(property_exists($this_post, 'post_parent') && !has_term('', 'package_included', $termid))
		{
			$termid = $this_post->post_parent;
		}
		
		$included = get_the_terms( $termid, 'package_included');
				
		$included_array = array();
		
		if($included)
		{
			for($x = 0; $x < count($included); $x++)
			{
				array_push($included_array, $included[$x]->name);
			}			
		}
		
		if(count($included_array))
		{
			$output = '<p class="strong">'.esc_html(__('Included:', 'dynamicpackages')).'</p>';
			$output .= '<ul class="tp_included"><li><i class="fas fa-check linkcolor" ></i> ';
			$output .= implode('</li><li><i class="fas fa-check linkcolor" ></i> ', $included_array);
			$output .= '</li></ul>';
		}
		return $output;
	}
	
	public static function get_not_included_list($this_post)
	{
		$termid = $this_post->ID;
		$output = '';
		
		if(property_exists($this_post, 'post_parent') && !has_term('', 'package_not_included', $termid))
		{
			$termid = $this_post->post_parent;
		}		
		
		$included = get_the_terms( $termid, 'package_not_included');
				
		$included_array = array();
		
		if($included)
		{
			for($x = 0; $x < count($included); $x++)
			{
				array_push($included_array, $included[$x]->name);
			}			
		}
		
		if(count($included_array))
		{
			$output = '<p class="strong">'.esc_html(__('Not Included:', 'dynamicpackages')).'</p>';
			$output .= '<ul class="tp_included"><li><i class="fas fa-times" ></i> ';
			$output .= implode('</li><li><i class="fas fa-times" ></i> ', $included_array);
			$output .= '</li></ul>';
		}
		return $output;
	}
	
	public static function get_categories($classes = '')
	{
		$terms = get_terms( array(
			'taxonomy' => 'package_category',
			'hide_empty' => true
		));
		
		$output = '';
		$ul_class = '';
		$li_class = '';
		$a_class = '';
		
		if(is_array($classes))
		{
			if(count($classes) > 0)
			{
				$output = '';
				
				if(array_key_exists('ul', $classes))
				{
					if($classes['ul'] != '')
					{
						$ul_class = $classes['ul'];
					}
				}
				if(array_key_exists('li', $classes))
				{
					if($classes['li'] != '')
					{
						$li_class = $classes['li'];
					}
				}	
				if(array_key_exists('a', $classes))
				{
					if($classes['a'] != '')
					{
						$a_class = $classes['a'];
					}
				}				
			}					
		}	
		
		if (!empty($terms) && ! is_wp_error($terms))
		{
			$ul =  '<ul class="'.esc_html($ul_class).'">';
			
			$output .= $ul;
			
			foreach ( $terms as $term )
			{
				$output .= '<li class="'.esc_html($li_class).'"><a class="'.esc_html($a_class).'" href="' . esc_url( get_term_link( $term ) ) . '">'.esc_html($term->name).'</a></li> ';
			}
			
			$output .= '</ul>';
		}
		else
		{
			$output = null;
		}

		return $output;
		
	}
	public static function get_all_locations($classes = '', $id = '')
	{		
	
		if($id != '')
		{
			$terms = wp_get_post_terms($id, 'package_location');
		}
		else
		{
			$args = array();
			$args['taxonomy'] = 'package_location';
			$args['hide_empty'] = true;
			$terms = get_terms($args);
		}
		
		
		
		$output = '';
		
		$ul_class = '';
		$li_class = '';
		$a_class = '';
		
		if(is_array($classes))
		{
			if(count($classes) > 0)
			{
				$output = '';
				
				if(array_key_exists('ul', $classes))
				{
					if($classes['ul'] != '')
					{
						$ul_class = $classes['ul'];
					}
				}
				if(array_key_exists('li', $classes))
				{
					if($classes['li'] != '')
					{
						$li_class = $classes['li'];
					}
				}	
				if(array_key_exists('a', $classes))
				{
					if($classes['a'] != '')
					{
						$a_class = $classes['a'];
					}
				}				
			}					
		}

		if (!empty($terms) && ! is_wp_error($terms))
		{
			$ul =  '<ul class="'.esc_html($ul_class).'">';
			
			$output .= $ul;
			
			foreach ( $terms as $term )
			{
				$output .= '<li class="'.esc_html($li_class).'"><a class="'.esc_html($a_class).'" href="' . esc_url( get_term_link( $term ) ) . '">'.esc_html($term->name).'</a></li> ';
			}
			
			$output .= '</ul>';
		}
		else
		{
			$output = null;
		}

		return $output;		

	}	
	
	public static function fix_multiple_tax( $query )
	{
		if((is_tax('package_location') || is_tax('package_category') || is_tax('package_terms_conditions')) && $query->is_main_query())
		{
			$query->set( 'posts_per_page', 1 );
		}
	}
		
	public static function people_restriction()
	{
		return __('Send us your request and we will send you the quote shortly.', 'dynamicpackages');
	}
	public static function hour_restriction()
	{
		return __('Invalid Hour', 'dynamicpackages');
	}	
	public static function date_restriction()
	{
		$min_range = date_i18n(get_option('date_format'), dy_utilities::min_range());
		$max_range = date_i18n(get_option('date_format'), dy_utilities::max_range());

		return __('Bookings are only available between', 'dynamicpackages').' '.$min_range.' '.__('and', 'dynamicpackages').' '.$max_range;
	}
	public static function restrictions()
	{
		$output = '<p class="strong">'.esc_html(__('Restrictions', 'dynamicpackages')).':</p>';
		$output .= '<p class="minimal_success"><i class="fas fa-exclamation-circle" ></i> '.esc_html(self::people_restriction()).'</p>';
		
		echo $output;
	}
	
	public static function date()
	{
		$date = null;
		
		if(isset($_REQUEST['booking_date']))
		{
			$date = sanitize_text_field($_REQUEST['booking_date']);
		}
		if(package_field('package_event_date') != '')
		{
			$date = package_field('package_event_date');
		}
		
		return $date;
	}	
	
	public static function description()
	{	
		global $post;
		$output = null;
		
		if(isset($post) && isset($_REQUEST['booking_date']))
		{
			$departure_date = dy_utilities::format_date(dy_utilities::booking_date());
			$start_hour = (dy_utilities::hour()) ? ' '.__('@', 'dynamicpackages').' '.dy_utilities::hour() : null;
			$itinerary = $departure_date.$start_hour;
			$end_date = (isset($_REQUEST['end_date'])) ? dy_utilities::format_date(dy_utilities::end_date()) : null;
			$pax_discount = (isset($_REQUEST['pax_discount'])) ? intval(sanitize_text_field($_REQUEST['pax_discount'])) : 0;
			$discount = (package_field('package_discount' )) ? package_field('package_discount' ) : 0;
			$free = (package_field('package_free')) ? package_field('package_free') : 0;
			$adults = intval(sanitize_text_field($_REQUEST['pax_regular']));
			$people = array();
			$people['adults'] = $adults;
			$pax_free = (isset($_REQUEST['pax_free'])) ? intval(sanitize_text_field($_REQUEST['pax_free'])) : 0;
			
			if($pax_discount > 0)
			{
				$people['discount'] = intval(sanitize_text_field($_REQUEST['pax_discount']));
			}
			
			if($pax_free > 0)
			{
				$people['free'] = intval(sanitize_text_field($_REQUEST['pax_free']));
			}			
	
			
			$participants = array(__('person', 'dynamicpackages'), __('persons', 'dynamicpackages'));
			
			if(array_key_exists('free', $people) || array_key_exists('discount', $people))
			{
				$participants = array(__('adult', 'dynamicpackages'), __('adults', 'dynamicpackages'));
			}
			
			$labels_singular = array($participants[0], __('child under', 'dynamicpackages'));
			$labels_plural = array($participants[1], __('children under', 'dynamicpackages'));
			$labels = $labels_singular;
			$people_imp = array();
			
			foreach($people as $k => $v)
			{
				if($v > 0)
				{
					$text = null;
					if($v > 1)
					{
						$labels = $labels_plural;
					}
					
					if($k == 'adults')
					{
						$text = $v.' '.$labels[0];
					}
					if($k == 'discount')
					{
						$text = $v.' '.$labels[1].' '.$discount.' '.__('years old', 'dynamicpackages');
					}	
					if($k == 'free')
					{
						$text = $v.' '.$labels[1].' '.$free.' '.__('years old', 'dynamicpackages');
					}
					array_push($people_imp, $text);
				}
			}
			
			$people_imp = implode(', ', $people_imp);
			
			if(dy_validators::is_package_transport() && isset($_REQUEST['end_date']))
			{
				$itinerary = __('Departure', 'dynamicpackages') .' '. $departure_date;
				
				if(strlen($_REQUEST['end_date']) > 5)
				{
					$description = __('Round trip', 'dynamicpackages');
					$itinerary .= ' | ' . __('Return', 'dynamicpackages') . ' ' . $end_date;
				}
				else
				{
					$description = __('One-way', 'dynamicpackages');
				}
			}
			else
			{
				$description = self::show_duration();
			}
					
			$description .= ' | ' . $post->post_title;
			$description .= ' ('.$itinerary.'): ';
			$description .= $people_imp;			
			$output = dy_utilities::remove_emoji($description);			
		}		
	
		return $output;
	}
	
	public static function show_badge()
	{
		$output = null;
		$code = package_field('package_badge');
		
		if($code > 0)
		{
			$color = package_field('package_badge_color');
			$messages = array(null, __('Best Seller', 'dynamicpackages'), __('New', 'dynamicpackages'), __('Offer', 'dynamicpackages'), __('Featured', 'dynamicpackages'), __('Last Minute Deal', 'dynamicpackages') );
			
			$output = '<span class="small semibold corner-ribbon '.esc_html($color).'">'.esc_html($messages[$code]).'</span>';
		}
		
		echo $output;
	}
	
	public static function children_package()
	{
		global $post;
		global $polylang;
		
		if(!dy_validators::is_child() && isset($post))
		{
			$duration = package_field('package_duration');
			$duration_unit = package_field('package_length_unit');
			$label = __('Packages', 'dynamicpackages');
			
			if($duration_unit == 3)
			{
				$label = __('Accommodations', 'dynamicpackages');
			}
			
			$args = array(
				'post_parent' => $post->ID,
				'post_type'   => 'packages', 
				'numberposts' => -1
			); 
			
			$children_array = get_children($args);
			$output = null;
			
			if(is_array($children_array))
			{
				if(count($children_array) > 0)
				{
					$make_null = false;
					$output .= '<table class="pure-table pure-table-bordered"><thead class="text-center"><tr><th colspan="3"><strong>'.esc_html(self::count_child()).'</strong> '.esc_html($label).':</th></tr></thead><tbody class="small">';
					
					foreach($children_array as $item)
					{
						if(property_exists($item, 'post_name'))
						{
							if($item->post_name != '')
							{
								
								$starting_at = intval(dy_utilities::starting_at($item->ID));
								$subpackage_name = 'package_child_title';
								$button_label = ($starting_at > 0) ? '$' . $starting_at : __('Rates', 'dynamicpackages');
								
								if(isset($polylang))
								{
									$subpackage_name .= '_'.pll_get_post_language($item->ID);
								}
								
								$subpackage_name = package_field($subpackage_name, $item->ID);
								
								if($subpackage_name == '')
								{
									$subpackage_name = $item->post_title;
								}
								
								$output .= '<tr>';
								$output .= '<td>'.esc_html($subpackage_name).'</td>';
								$output .= '<td class="text-center">'.esc_html(package_field('package_max_persons', $item->ID)).' <i class="fas fa-male"></i></td>';
								$output .= '<td><a class="small pure-button pure-button-primary rounded block width-100 borderbox" href="'.esc_url(rtrim(get_the_permalink(), '/').'/'.$item->post_name.'/').'">'.esc_html($button_label).'</a></td>';
								$output .= '</tr>';							
							}
							else
							{
								$make_null = true;
							}
						}
						else
						{
							$make_null = true;
						}
					}
					
					$output .= "</tbody></table>";
					
					if($make_null === true)
					{
						$output = '';
					}
					
					echo $output;			
				}
			}			
		}
	}
	
	public static function modify_excerpt($excerpt)
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
				global $post;
				$excerpt = null;
				
				if(!in_the_loop())
				{
					$excerpt .= ' '.__('Starting at', 'dynamicpackages');
					
					if(intval(dy_utilities::starting_at()) > 0)
					{
						$excerpt .= ' '.dy_utilities::currency_symbol().intval(dy_utilities::starting_at()).' '.self::price_type().'. ';
						
						if(package_field('package_payment') > 0 && package_field('package_deposit' ) > 0)
						{
							$excerpt .= __('Book it with a', 'dynamicpackages').' '.package_field('package_deposit' ).'% '.__('deposit', 'dynamicpackages').'. ';
						}
					}
					
				}
				else
				{
					$excerpt = self::show_duration(true) . ' - ';
				}		
								
				$excerpt .= $post->post_excerpt;

			}
		}
		elseif(is_page())
		{
			global $post;
			
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
	
	public static function deque_jetpack()
	{	
		if(is_page() && dy_validators::validate_category_location())
		{	
			remove_action( 'wp_head', 'rel_canonical');
			return false;
		}
		
		if(is_singular('packages'))
		{
			global $post;
						
			if($post->post_parent > 0)
			{
				remove_action( 'wp_head', 'rel_canonical');
				return false;				
			}
		}
	}
	public static function location_category_canonical()
	{
		if(dy_validators::validate_category_location())
		{
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$url = get_the_permalink().'?';
			$url_var = array();
			
			if($paged > 1)
			{
				$url_var['paged'] = sanitize_text_field($paged);
			}
		
			if(isset($_GET['package_location']))
			{
				if($_GET['package_location'] != 'any'){
					$url_var['package_location'] = sanitize_text_field($_GET['package_location']);
				}
			}
			if(isset($_GET['package_category']))
			{
				if($_GET['package_category'] != 'any')
				{
					$url_var['package_category'] = sanitize_text_field($_GET['package_category']);
				}
			}
			if(isset($_GET['package_sort']))
			{
				if($_GET['package_sort'] != 'any')
				{
					if($_GET['package_sort'] == 'new' || $_GET['package_sort'] == 'low' || $_GET['package_sort'] == 'high' || $_GET['package_sort'] == 'today' || $_GET['package_sort'] == 'tomorrow' || $_GET['package_sort'] == 'week' || $_GET['package_sort'] == 'month')
					{
						$url_var['package_sort'] = sanitize_text_field($_GET['package_sort']);
					}					
				}
			}
			if(isset($_GET['package_search']))
			{
				if($_GET['package_search'] != '')
				{
					$search = strtolower(sanitize_text_field($_GET['package_search']));
					$search = preg_replace('/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $search);
					$search =  preg_replace('/\s+/', ' ', $search);				
					$search =  substr($search, 0, 25);
					$url_var['package_search'] = $search;					
				}
			}			

			$url_var = http_build_query($url_var);
			
			echo '<link rel="canonical" href="'.esc_url($url.$url_var).'" />';
		}
	}
	
	public static function modify_term_description($description)
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
	

	
	public static function get_all_coupons()
	{
		global $get_all_coupons;
		$output = null;
		
		if(isset($get_all_coupons))
		{
			$output = $get_all_coupons;
		}
		else
		{
			$coupons = json_decode(html_entity_decode(package_field('package_coupons' )), true);
			
			if(array_key_exists('coupons', $coupons))
			{
				$output = $coupons['coupons'];
				$GLOBALS['get_all_coupons'] = $output;
			}			
		}
		return $output;
	}

	public static function enabled_days()
	{
		$output = '';
		$days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
		$labels = array(__('Mon', 'dynamicpackages'), __('Tue', 'dynamicpackages'), __('Wed', 'dynamicpackages'), __('Thu', 'dynamicpackages'), __('Fri', 'dynamicpackages'), __('Sat', 'dynamicpackages'), __('Sun', 'dynamicpackages'));
		$labels_lon = array(__('Monday', 'dynamicpackages'), __('Tuesday', 'dynamicpackages'), __('Wednesday', 'dynamicpackages'), __('Thursday', 'dynamicpackages'), __('Friday', 'dynamicpackages'), __('Saturday', 'dynamicpackages'), __('Sunday', 'dynamicpackages'));		
		$enabled_days = array();
		$enabled_days_lon = array();
		
		for($x = 0; $x < count($days); $x++)
		{
			$day = strtolower('package_day_'.$days[$x]);
			
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
		
		return $output;
	}
	
	public static function icon($icon)
	{
		$output = null;
		
		if($icon == 'calendar')
		{
			$output .= (is_checkout_page()) ? '*' : '<i class="fas fa-calendar"></i>';
		}
		else if($icon == 'clock')
		{
			$output .= (is_checkout_page()) ? '*' : '<i class="fas fa-clock"></i>';
		}
		else if($icon == 'marker')
		{
			$output .= (is_checkout_page()) ? '*' : '<i class="fas fa-map-marker"></i>';
		}
		
		return $output;
	}
	
	public static function details()
	{
		global $dy_is_archive;
		$is_archive = (isset($dy_is_archive)) ? true : false;
		$output = null;
		$booking_date = (dy_utilities::booking_date()) ? dy_utilities::format_date(dy_utilities::booking_date()) : null;
		$end_date = (dy_utilities::end_date()) ? dy_utilities::format_date(dy_utilities::end_date()) : null;
		
		$is_transport = dy_validators::is_package_transport();
		$is_checkout_page = is_checkout_page();
		$is_booking_page = is_booking_page();
		
		$args = array(
			'enabled_days' => array('calendar', self::enabled_days()),
			'schedule' => array('clock', __('Schedule', 'dynamicpackages').' '.package_field('package_min_hour' ).' - '.package_field('package_max_hour' )),
			'label_departure' => array(null, __('Departure', 'dynamicpackages')),
			'booking_date' => array('calendar', $booking_date),
			'duration' => array('clock', self::show_duration()),
			'check_in' => array('clock', __('Check-in', 'dynamicpackages').' '.package_field('package_check_in_hour' )),
			'start_hour' => array('clock', __('Hour', 'dynamicpackages').' '.dy_utilities::hour()),
			'start_address' => array('marker', package_field('package_start_address')),
			'label_return' => array(null, __('Return', 'dynamicpackages')),
			'end_date' => array('calendar', $end_date),
			'check_in_end_hour' => array('clock', __('Check-in', 'dynamicpackages').' '.package_field('package_check_in_end_hour')),
			'return_hour' => array('clock', __('Hour', 'dynamicpackages').' '. package_field('package_return_hour')),
			'return_address' => array('clock', package_field('package_return_address'))
			
		);
		
		if(!self::enabled_days())
		{
			unset($args['enabled_days']);
		}
		if(!$booking_date)
		{
			unset($args['booking_date']);
		}
		if(!package_field('package_min_hour' ) && !package_field('package_max_hour'))
		{
			unset($args['schedule']);
		}
		if($is_checkout_page || $is_booking_page)
		{
			unset($args['enabled_days']);
		}
		if(!package_field('package_check_in_hour'))
		{
			unset($args['check_in']);
		}
		if(!dy_utilities::hour())
		{
			unset($args['start_hour']);
		}
		if(!package_field('package_start_address'))
		{
			unset($args['start_address']);
		}
		if(!$end_date)
		{
			//unset($args['label_return']);
			unset($args['end_date']);
			//unset($args['return_hour']);
			//unset($args['check_in_end_hour']);
			//unset($args['return_address']);
		}
		if($is_transport)
		{
			unset($args['duration']);
		}
		if(!package_field('package_check_in_end_hour'))
		{
			unset($args['check_in_end_hour']);
		}
		if(!package_field('package_return_hour'))
		{
			unset($args['return_hour']);
		}
		if(!package_field('package_return_address'))
		{
			unset($args['return_address']);
		}
		if(!$is_transport || is_page() || is_tax())
		{
			if(!$is_booking_page && !$is_checkout_page)
			{
				unset($args['duration']);
			}
			
			unset($args['label_departure']);
			unset($args['label_return']);
			unset($args['end_date']);
			unset($args['check_in_end_hour']);
			unset($args['return_hour']);
			unset($args['return_address']);
		}
		
		if($is_archive)
		{
			unset($args['check_in']);
			
			if(get_option('dy_archive_hide_start_address'))
			{
				unset($args['start_address']);
			}
			if(get_option('dy_archive_hide_enabled_days'))
			{
				unset($args['enabled_days']);
			}
		}
		
		foreach($args as $k => $v)
		{
			$output .= '<div class="dy_pad bottom-5">';
			$output .= ($v[0]) ? self::icon($v[0]) .' '. esc_html($v[1]) : '<strong>'.esc_html($v[1]).'</strong>';
			$output .= '</div>';
		}
		
		return $output;
	}
	
	public static function event_date_update($the_id)
	{
		$output = null;
		global $polylang;
		global $post;
		
		if(isset($polylang))
		{
			if(pll_current_language($post->post_name) != pll_default_language())
			{
				$the_id = pll_get_post(get_the_ID(), pll_default_language());
			}
		}
		
		if(package_field('package_event_date') != '')
		{
			$output = package_field('package_event_date');
		}
		else
		{
			$today = strtotime('today');
			$last_day = strtotime("+365 days", $today);
			$from = package_field('package_booking_from');
			$to = package_field('package_booking_to');
			$week_days = dy_utilities::get_week_days_list();
			
			if(intval($from) > 0)
			{
				$today = strtotime("+ {$from} days", $today);
			}
			if(intval($to) > 0)
			{
				$last_day = strtotime("+ {$to} days", $today);
			}
			
			$today = date('Y-m-d', $today);
			$last_day = date('Y-m-d', $last_day);
			
			$new_range = array();
			$range = dy_utilities::get_date_range($today, $last_day);
			$disabled_range = dy_utilities::get_disabled_range();
			
			for($x = 0; $x < count($range); $x++)
			{
				if(!in_array($range[$x], $disabled_range))
				{
					$day = date('N', strtotime($range[$x]));
					
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
					$output = $new_range[0];
				}
			}
			
			if($output != '')
			{
				update_post_meta($the_id, 'package_date', $output);
			}	
		}
		
		return $output;
	}

	public static function recaptcha_sitekey()
	{
		return 'function dy_recaptcha_sitekey(){ return "'.esc_html(get_option('captcha_site_key')).'"; }';
	}
	public static function show_event_date()
	{
		$output = '';
		
		if(package_field('package_event_date') != '')
		{
			$date = strtotime(package_field('package_date'));
			$today = strtotime('today');
			$tomorrow = strtotime('tomorrow');
			
			if($date == $today)
			{
				$output = '<span class="dy_event_date strong uppercase small block padding-5">';	
				$output .= __('today', 'dynamicpackages');
				$output .= '</span>';
			}
			elseif($date == $tomorrow)
			{
				$output = '<span class="dy_event_date strong uppercase small block padding-5">';	
				$output .= __('tomorrow', 'dynamicpackages');
				$output .= '</span>';
			}
			else
			{
				$date = date_i18n('M d', $date);
				$output = '<span class="dy_event_date strong uppercase small block padding-5">';	
				$output .= esc_html($date);	
				$output .= '</span>';				
			}
		}
		else
		{
			if(isset($_GET['package_sort']))
			{
				if($_GET['package_sort'] == 'today' || $_GET['package_sort'] == 'tomorrow' || $_GET['package_sort'] == 'week' || $_GET['package_sort'] == 'month')
				{
					$date = strtotime(package_field('package_date'));
					
					if($_GET['package_sort'] == 'today')
					{
						$label = __('today', 'dynamicpackages');
					}
					elseif($_GET['package_sort'] == 'tomorrow')
					{
						$label = __('tomorrow', 'dynamicpackages');
					}
					elseif($_GET['package_sort'] == 'week')
					{
						$label = __('next 7 days', 'dynamicpackages');
					}
					elseif($_GET['package_sort'] == 'month')
					{
						$label = __('next 30 days', 'dynamicpackages');
					}
					
					
					$output = '<span class="dy_event_date strong uppercase small block padding-5">';	
					$output .= esc_html($label);
					$output .= '</span>';	
				}
			}
		}
		echo $output;
	}
	
	public static function show_coupons()
	{
		if(dy_validators::has_coupon())
		{
			$duration_unit = package_field('package_length_unit');
			$coupons = self::get_all_coupons();
			$output = null;			
						
			if(is_array($coupons))
			{
				for($x = 0; $x < count($coupons); $x++)
				{
					if($coupons[$x][3] == 'true' && $coupons[$x][0])
					{						
						$expiration = new DateTime($coupons[$x][2]);
						$expiration->setTime(0,0,0);
						$expiration = $expiration->getTimestamp();	
						
						if($expiration >= strtotime('today midnight'))
						{
							$expiration = '';
							$valid = true;
							
							if($coupons[$x][2] != '')
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
								
								if(isset($coupons[$x][4]) && !dy_validators::is_package_transport() && !dy_validators::is_package_single_day())
								{
									if(is_numeric($coupons[$x][4]))
									{
										$label .= '<br/><small>' . sprintf(__('This coupon is valid for booking of minimum %s %s.', 'dynamicpackages'), esc_html($coupons[$x][4]), esc_html(self::duration_label($duration_unit, $coupons[$x][4]))).'</small>';
									}
								}
								
								if($expiration != '')
								{
									$label .= '<br/><small>'.esc_html(__('Offer valid until', 'dynamicpackages'));
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
	public static function count_child($this_id = null)
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
	public static function return_parent()
	{
		global $post;
		
		if(isset($post) && dy_validators::is_child())
		{
			$label = __('Similar packages', 'dynamicpackages');
			
			if(package_field('package_length_unit') == 3)
			{
				$label = __('Similar accommodations', 'dynamicpackages');
			}
			
			
			echo '<a class="pure-button rounded block width-100 borderbox" href="'.esc_url(get_the_permalink($post->post_parent)).'"><strong>'.esc_html(self::count_child($post->post_parent)).'</strong> '.esc_html($label).'</a>';			
			
		}
	}
	
	public static function meta_description($description)
	{
		if(is_singular('packages'))
		{
			$starting_at = (dy_validators::has_children()) ? dy_utilities::starting_at_archive() : dy_utilities::starting_at();
			
			if($starting_at > 0)
			{
				$description = (empty($description)) ? get_the_title() : $description;
				$description = rtrim(trim($description), '.') . '. ' . __('From', 'dynamicpackages') . ' ' . dy_utilities::currency_symbol().$starting_at.' '.self::price_type() . '.';
			}			
		}
		
		return $description;
	}
}