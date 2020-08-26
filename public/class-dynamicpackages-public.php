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
class dynamicpackages_Public {

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
		add_action('wp_enqueue_scripts', array('dynamicpackages_Public', 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array('dynamicpackages_Public', 'enqueue_scripts'), 11);
		add_action('pre_get_posts', array('dynamicpackages_Public', 'global_vars'));
		add_filter( 'template_include', array('dynamicpackages_Public', 'package_template'), 99);
		add_filter( 'the_content', array('dynamicpackages_Public', 'filter_content'), 100);
		add_filter( 'pre_get_document_title', array('dynamicpackages_Public', 'modify_wp_title'), 100);
		add_filter( 'wp_title', array('dynamicpackages_Public', 'modify_wp_title'), 100);
		add_filter("the_title", array('dynamicpackages_Public', 'modify_title'), 100);
		add_filter("single_term_title", array('dynamicpackages_Public', 'modify_tax_title'));
		add_action('pre_get_posts', array('dynamicpackages_Public', 'fix_multiple_tax'));
		add_action('wp_head', array('dynamicpackages_Public', 'booking_head'));
		add_action('wp_head', array('dynamicpackages_Public', 'meta_tags'));
		add_filter("get_the_excerpt", array('dynamicpackages_Public', 'modify_excerpt'));
		add_filter("term_description", array('dynamicpackages_Public', 'modify_term_description'));
		add_action('wp_head', array('dynamicpackages_Public', 'location_category_canonical'));
		add_filter('jetpack_enable_open_graph', array('dynamicpackages_Public', 'deque_jetpack'));
		add_filter('package_details', array('dynamicpackages_Public', 'details_add'));
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
				
				if(is_object($post))
				{
					if(has_shortcode( $post->post_content, 'packages'))
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
		
		self::cf7_dequeue_recaptcha();
		
		if(is_singular('packages'))
		{
			$dep = array( 'jquery', 'landing-cookies');			
			wp_enqueue_script('landing-cookies', plugin_dir_url( __FILE__ ) . 'js/cookies.js', array( 'jquery'), '', true );
			
			if(is_booking_page() || dynamicpackages_Validators::validate_quote())
			{
				wp_enqueue_script('invisible-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=dy_recaptcha&render=explicit', array(), 'async_defer', true );
				array_push($dep, 'invisible-recaptcha');
			}

			if(!is_booking_page())
			{
				wp_enqueue_script('sha512', plugin_dir_url( __FILE__ ) . 'js/sha512.js', array(), 'async_defer', true );
				array_push($dep, 'sha512');
			}	
			
			wp_enqueue_script('dynamicpackages', plugin_dir_url( __FILE__ ) . 'js/dynamicpackages-public.js', $dep, time(), true );			
			wp_add_inline_script('dynamicpackages', self::booking_head(), 'before');	

			wp_add_inline_script('dynamicpackages', dynamicpackages_Public::recaptcha_sitekey(), 'before');					
			
			if(!is_booking_page())
			{
				self::datepickerJS();

				if(get_theme_mod('min_sharethis'))
				{
					wp_enqueue_script( 'min_sharethis');
				}
			}			
		}
		
		if(is_tax('package_category') ||is_tax('package_location') || is_post_type_archive('packages') || (is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'packages')))
		{
			wp_enqueue_script('dynamicpackages-archive', plugin_dir_url( __FILE__ ) . 'js/dynamicpackages-archives.js', array( 'jquery'), time(), true );
		}
		
		$ipgeolocation = null;
		
		if(get_option('ipgeolocation') != null)
		{
			$ipgeolocation = get_option('ipgeolocation');
		}
		wp_add_inline_script( 'dynamicpackages', 'function dy_ipgeolocation(){ return "'.esc_html($ipgeolocation).'";}', 'before');
		
		wp_enqueue_script( 'minimal-fontawesome', 'https://use.fontawesome.com/releases/v5.3.1/js/all.js?async=async', '', '', true);		
		
	}
	
	public static function cf7_dequeue_recaptcha()
	{
		$dequeu = true;
		
		if(is_singular())
		{
			global $post;
			
			if(has_shortcode($post->post_content, 'contact-form-7'))
			{
				$dequeu = false;
			}
		}
		
		if($dequeu === true)
		{
			wp_dequeue_script('google-recaptcha');
		}
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
	
	
	public static function get_min_nights()
	{
		if(is_booking_page() && isset($_GET['booking_date']))
		{
			$duration = intval(package_field('package_duration'));
			
			if(isset($_GET['booking_extra']))
			{
				if($_GET['booking_extra'] > $duration)
				{
					$duration = intval(sanitize_text_field($_GET['booking_extra']));
				}
			}
			
			$seasons = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			
			if($seasons != null)
			{
				if(array_key_exists('seasons_chart', $seasons))
				{
					$seasons = $seasons['seasons_chart'];
					
					for($d = 1; $d < $duration; $d++)
					{
						$booking_date = sanitize_text_field($_GET['booking_date']);
						$new_date = strtotime($booking_date . " +$d days");
						
						for($x = 0; $x < count($seasons); $x++)
						{
							$from_season = strtotime($seasons[$x][1]);
							$to_season = strtotime($seasons[$x][2]);
					
							if($new_date >= $from_season && $new_date <= $to_season)
							{
								if($seasons[$x][3] > $duration)
								{
									$duration = $seasons[$x][3];
								}
							}			
						}
					}
				}
			}
			$output = $duration;
			return $output;	
		}
	}	
	public static function get_season($booking_date)
	{
		if(is_booking_page())
		{
			$season = 'price_chart';
			$seasons = self::get_season_chart();
					
			if($seasons != null)
			{
				if(array_key_exists('seasons_chart', $seasons))
				{
					$seasons = $seasons['seasons_chart'];

					$booking_date = strtotime(sanitize_text_field($booking_date));
						
					for($x = 0; $x < count($seasons); $x++)
					{
						$from_season = strtotime($seasons[$x][1]);
						$to_season = strtotime($seasons[$x][2]);
				
						if($booking_date >= $from_season && $booking_date <= $to_season)
						{
							$last_cell = count($seasons[$x]) - 1;
							$season = $seasons[$x][$last_cell];
						}			
					}
				}
			}	
			$output = $season;
			return $output;			
		}
	}
	public static function get_price_chart($the_id = '')
	{
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}
		
		$output = null;
		$which_var = '$price_chart_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$price_chart = json_decode(html_entity_decode(package_field('package_price_chart', $the_id)), true);
		
			if(is_array($price_chart))
			{
				if(array_key_exists('price_chart', $price_chart))
				{
					$GLOBALS[$which_var] = $price_chart['price_chart'];
					$output = $price_chart['price_chart'];
				}
			}			
		}
		return $output;
	}
	
	public static function get_occupancy_chart($the_id = '')
	{
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}		
		
		$output = null;
		$which_var = '$occupancy_chart_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$chart = json_decode(html_entity_decode(package_field('package_occupancy_chart', $the_id)), true);
			$GLOBALS[$which_var] = $chart;
			$output = $chart;
		}
		
		return $output;
	}
	public static function get_season_chart()
	{
		//package_seasons_chart
		$output = null;
		$which_var = '$seasons_chart_'.get_the_ID();
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$chart = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			$GLOBALS[$which_var] = $chart;
			$output = $chart;
		}
		
		return $output;
	}
	public static function get_price_per_night()
	{
		if(isset($_GET['booking_date']))
		{
			$sum = 0;
			$package_occupancy_chart = json_decode(html_entity_decode(package_field('package_occupancy_chart' )), true);
			$duration = self::get_min_nights();
			$seasons = json_decode(html_entity_decode(package_field('package_seasons_chart' )), true);
			$booking_date = sanitize_text_field($_GET['booking_date']);

			$seasons_array = array();
						
			for($d = 0; $d < $duration; $d++)
			{
				$new_date = date('Y-m-d', strtotime($booking_date . " +$d days"));
				$is_season = self::get_season($new_date);
				
				if($is_season == 'price_chart')
				{
					$occupancy_key = 'occupancy_chart';
				}
				else
				{
					$occupancy_key = 'price_chart'.$is_season;
				}
				
				array_push($seasons_array, $occupancy_key);	
			}
			
			
			for($s = 0; $s < count($seasons_array); $s++)
			{
				if(array_key_exists($s, $seasons_array) && $package_occupancy_chart != '')
				{
					if(array_key_exists($seasons_array[$s], $package_occupancy_chart))
					{
						for($a = 0;  $a < count($package_occupancy_chart[$seasons_array[$s]]); $a++)
						{
							if(floatval(sanitize_text_field($_GET['pax_regular'])) == ($a+1))
							{	
								if($package_occupancy_chart[$seasons_array[$s]][$a][0] != '')
								{
									//total occupancy price
									$each_adult = floatval($package_occupancy_chart[$seasons_array[$s]][$a][0]);
									$sum = $each_adult * floatval(sanitize_text_field($_GET['pax_regular']));
									
									//total children discounts
									if(isset($_GET['pax_discount']))
									{
										if($_GET['pax_discount'] > 0 && $package_occupancy_chart[$seasons_array[$s]][$a][1] != '')
										{
											$each_child = floatval($package_occupancy_chart[$seasons_array[$s]][$a][1]);
											$sum = $sum + ($each_child * floatval(sanitize_text_field($_GET['pax_discount'])));										}
									}
									
									$sum = $sum * $duration;
								}
							}	
						}						
					}
				}
			}
			

			return $sum;			
		}
	}

	public static function get_price_adults()
	{
		if(is_booking_page())
		{
			$sum = 0;
			$base_price = 0;
			$occupancy_price = 0;			
			$price_chart = self::get_price_chart();
			$occupancy_chart = self::get_occupancy_chart();	
			$duration = floatval(self::get_min_nights());
			$seasons = self::get_season_chart();
			$booking_date = sanitize_text_field($_GET['booking_date']);
			$seasons_array = array();
						
			for($d = 0; $d < $duration; $d++)
			{
				$new_date = date('Y-m-d', strtotime($booking_date . " +$d days"));
				$is_season = self::get_season($new_date);
				
				if($is_season == 'price_chart')
				{
					$occupancy_key = 'occupancy_chart';
				}
				else
				{
					$occupancy_key = 'price_chart'.$is_season;
				}
				
				if(package_field('package_package_type' ) == 1)
				{
					array_push($seasons_array, $occupancy_key);	
				}
			}

			for ($x = 0; $x < count($price_chart); $x++)
			{
				if(floatval(sanitize_text_field($_GET['pax_regular'])) == ($x+1))
				{
					if($price_chart[$x][0] != '')
					{
						$base_price = floatval($price_chart[$x][0]);
					}
				}
			}
			
			for($s = 0; $s < count($seasons_array); $s++)
			{
				if(array_key_exists($s, $seasons_array))
				{
					if(array_key_exists($seasons_array[$s], $occupancy_chart))
					{
						for($a = 0;  $a < count($occupancy_chart[$seasons_array[$s]]); $a++)
						{
							if(floatval(sanitize_text_field($_GET['pax_regular'])) == ($a+1))
							{
								if($occupancy_chart[$seasons_array[$s]][$a][0] != '')
								{
									$occupancy_price = floatval($occupancy_chart[$seasons_array[$s]][$a][0]);
									$occupancy_price = $occupancy_price * $duration;
								}
							}		
						}						
					}
				}
			}
			
			$sum = $base_price + $occupancy_price;
			
			if(dynamicpackages_Validators::valid_coupon())
			{
				$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
			}

			return $sum;			
		}
	}	


	
	public static function get_price_discount()
	{
		if(is_booking_page())
		{
			$sum = 0;
			$base_price = 0;
			$occupancy_price = 0;			
			$price_chart = self::get_price_chart();
			$package_occupancy_chart = self::get_occupancy_chart();	
			$duration = self::get_min_nights();
			$seasons = self::get_season_chart();
			$booking_date = sanitize_text_field($_GET['booking_date']);
			$seasons_array = array();
		
			for($d = 0; $d < $duration; $d++)
			{
				$new_date = date('Y-m-d', strtotime($booking_date . " +$d days"));
				$is_season = self::get_season($new_date);
				
				if($is_season == 'price_chart')
				{
					$occupancy_key = 'occupancy_chart';
				}
				else
				{
					$occupancy_key = 'price_chart'.$is_season;
				}
				
				array_push($seasons_array, $occupancy_key);	
			}
			
			for($x = 0; $x < count($price_chart); $x++)
			{
				if(isset($_GET['pax_discount']))
				{
					if(floatval(sanitize_text_field($_GET['pax_discount'])) == floatval(($x+1)))
					{
						$base_price = 0;
						
						if($price_chart[$x][1] != '')
						{
							$base_price = floatval($price_chart[$x][1]);
						}
					}					
				}
			}
			
			for($s = 0; $s < count($seasons_array); $s++)
			{
				if(array_key_exists($s, $seasons_array))
				{
					if($seasons_array[$s] != '' && $package_occupancy_chart != '')
					{
						if(array_key_exists($seasons_array[$s], $package_occupancy_chart))
						{
							for($a = 0;  $a < count($package_occupancy_chart[$seasons_array[$s]]); $a++)
							{
								if(isset($_GET['pax_discount']))
								{
									if(floatval(sanitize_text_field($_GET['pax_discount'])) == floatval(($a+1)))
									{
										$occupancy_price = 0;
										
										if($package_occupancy_chart[$seasons_array[$s]][$a][1] != '')
										{
											$occupancy_price = floatval($package_occupancy_chart[$seasons_array[$s]][$a][1]);
											$occupancy_price = $occupancy_price * $duration;
										}		
									}			
								}		
							}						
						}						
					}
				}				
			}
			
			$sum = $base_price + $occupancy_price;
			
			if(dynamicpackages_Validators::valid_coupon())
			{
				$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
			}			
			
			return $sum;			
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
		
		$GLOBALS['price_chart'] = self::get_price_chart();
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
	public static function booking_date()
	{
		if(isset($_GET['booking_date']))
		{
			return strtotime(sanitize_text_field($_GET['booking_date']));
		}
	}
	public static function min_range()
	{
		global $date_from;
		$min_range = strtotime("+ {$date_from} days", strtotime('today midnight'));
		//fix first day
		return strtotime("- 1 days", $min_range);	
	}
	public static function max_range()
	{
		global $date_to;
		return strtotime("+ {$date_to} days", strtotime('today midnight'));		
	}	
	public static function filter_content($content)
	{
		global $post;
		global $polylang;
		
		if(is_tax('package_location') || is_tax('package_category'))
		{
			ob_start();
			require_once(dirname( __FILE__ ) . '/partials/archive.php');
			$content = ob_get_contents();
			ob_end_clean();
		}
		if(is_tax('package_terms_conditions'))
		{
			$content = wpautop(get_term(get_queried_object()->term_id)->description);
		}
		if(is_singular('packages'))
		{
			if(isset($_GET['booking_date']))
			{
				if(is_booking_page())
				{
					if(dynamicpackages_Validators::validate_hash())
					{
						$pax_regular = intval(sanitize_text_field($_GET['pax_regular']));			
						$sum_people = $pax_regular;	

						if(isset($_GET['pax_discount']))
						{
							$sum_people = $sum_people + intval(sanitize_text_field($_GET['pax_discount']));
						}
						if(isset($_GET['pax_free']))
						{
							$sum_people = $sum_people + intval(sanitize_text_field($_GET['pax_free']));
						}					
						
						if(isset($_GET['booking_date']))
						{
							if(sanitize_text_field($_GET['booking_date']) == '')
							{
								$content = '<p class="minimal_alert"><strong>'.esc_html(self::hour_restriction()).'</strong></p>';		
							}
							else
							{
								if($pax_regular < package_field('package_min_persons') || $sum_people > package_field('package_max_persons'))
								{
									$content = '<p class="minimal_success strong">'.esc_html(self::people_restriction()).'</p>';
									$content .= '<h2>'.__('Contact The Experts', 'dynamicpackages').' - '.__('Request Quote', 'dynamicpackages').'</h2>';
									$content .= self::booking_sidebar();							
								}
								else
								{
									ob_start();
									require_once(plugin_dir_path( __DIR__  ) . 'gateways/checkout_page.php');
									$content = ob_get_contents();
									ob_end_clean();									
								}	
							}
						}
						else
						{
							ob_start();
							require_once(plugin_dir_path( __DIR__ ) . 'gateways/checkout_page.php');
							$content = ob_get_contents();
							ob_end_clean();						
						}					
					}
					else
					{
						$content = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Request', 'dynamicpackages')).'</strong></p>';
					}
				}
				else
				{
					$content = null;
					
					$content .= '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Request', 'dynamicpackages')).'</strong></p>';
				}
			}
			else if(dynamicpackages_Validators::is_checkout_or_quote())
			{
				if(dynamicpackages_Validators::validate_checkout())
				{
					$content = dynamicpackages_Checkout::checkout();
				}
				elseif(dynamicpackages_Validators::validate_quote())
				{
					if(dynamicpackages_Validators::validate_recaptcha())
					{
						$headers = array('Content-type: text/html');
						array_push($headers, 'Reply-To: '.sanitize_text_field($_POST['fname']).' '.sanitize_text_field($_POST['lastname']).' <'.sanitize_text_field($_POST['email']).'>');
						$body = __('New Request from', 'dynamicpackages');
						$body .= ' ';
						$body .= sanitize_text_field($_POST['fname']) .' '.sanitize_text_field($_POST['lastname']);
						$body .= ',<br/><br/>';
						$body .= sanitize_text_field($_POST['description']);
						$body .= '<br/><br/>';
						$body .= __('Name', 'dynamicpackages').': '.sanitize_text_field($_POST['fname']).' '.sanitize_text_field($_POST['lastname']);
						$body .= '<br/>';
						$body .= __('Email', 'dynamicpackages').': '.sanitize_text_field($_POST['email']);
						$body .= '<br/>';
						$body .= __('Phone', 'dynamicpackages').': '.sanitize_text_field($_POST['phone']);
						
						wp_mail(get_option('admin_email'), esc_html(sanitize_text_field($_POST['fname']).': '. sanitize_text_field($_POST['description'])), $body, $headers);
						dynamicpackages_Checkout::webhook('tp_quote_webhook', json_encode($_POST));
						$content = '<p class="minimal_success"><strong>'.esc_html( __('Thank you for contacting us. Our staff will be in touch with you soon.', 'dynamicpackages')).'</strong></p>';
						
					}
					else
					{
						$content = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Recaptcha', 'dynamicpackages')).'</strong></p>';
					}
				}
				else
				{
					$content = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Request', 'dynamicpackages')).'</strong></p>';
				}
			}
			else
			{
				
				if(dynamicpackages_Validators::is_child())
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
					
					$parent_content = get_post($post->post_parent)->post_content;
					$content .= $parent_content;
				}
				
				$GLOBALS['new_content'] = $content;
				
				ob_start();
				require_once(dirname( __FILE__ ) . '/partials/single.php');
				$content = ob_get_contents();
				ob_end_clean();				
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

			$get_dates = json_decode(html_entity_decode(package_field('package_disabled_dates' )), true);
			
			if(array_key_exists('disabled_dates', $get_dates))
			{		
				$disabled_dates = $get_dates['disabled_dates'];
						
				for($x = 0; $x < count($disabled_dates); $x++)
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
						
						array_push($disable['disable'], $this_date);
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
	
	public static function modify_wp_title($title)
	{
		global $polylang;
		
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
			$title .= ' | '.esc_html(get_bloginfo( 'name' ));
		}
		if(is_singular('packages'))
		{
			if(is_booking_page())
			{
				$title = esc_html(__('Online Booking', 'dynamicpackages')).' '.esc_html(get_the_title()).' | '.esc_html(get_bloginfo( 'name' ));
			}
			
			elseif(dynamicpackages_Validators::validate_checkout())
			{
				$title = esc_html(__('Checkout', 'dynamicpackages')).' '.esc_html(get_the_title()).' | '.esc_html(get_bloginfo( 'name' ));
			}
			elseif(dynamicpackages_Validators::validate_quote())
			{
				$title = esc_html(__('Quote', 'dynamicpackages')).' '.esc_html(get_the_title()).' | '.esc_html(get_bloginfo( 'name' ));
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
						$title .= ' | '.esc_html(get_the_title()).' | '.esc_html(get_bloginfo( 'name' ));
					}
					
					wp_reset_postdata();
				}
			}				
		}
		elseif(is_page())
		{
			if(dynamicpackages_Validators::validate_category_location())
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
				
				$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
				
				if($paged > 1)
				{
					$title .= esc_html(' | '.__('Page', 'dynamicpackages').' '.$paged);
				}
				
				
				$title .= ' | '.get_bloginfo( 'name');
			}
		}		

		return esc_html($title);
	}
	
	public static function modify_title($title)
	{
		global $post;
		global $polylang;
		
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
						
						$title = esc_html($packages_in).' <span class="linkcolor">'.esc_html($title).'</span>';
						
						if(get_queried_object()->parent > 0)
						{
							$title .= ', '.esc_html(get_term(get_queried_object()->parent)->name);
						}						
					}
				}
			}
			
			if($post->post_type == 'packages')
			{
				global $post;
				
				if(is_booking_page())
				{
					$our_awesome = __('Booking Page', 'dynamicpackages');
					
					if($polylang)
					{
						$our_awesome = pll__('Booking Page');
					}
					
					$title = '<span class="linkcolor">'.esc_html($our_awesome).'</span>: <span data-id="package-title">'.esc_html($title).'</span> <span class="large linkcolor"></span>';
				}
				elseif(dynamicpackages_Validators::validate_checkout())
				{
					$title = '<span class="linkcolor">'.esc_html(__('Checkout', 'dynamicpackages')).'</span>';
				}
				elseif(dynamicpackages_Validators::validate_quote())
				{
					$title = '<span class="linkcolor">'.esc_html(__('Quote', 'dynamicpackages')).'</span>';
				}				
				else
				{
					$title = '<span itemprop="name">'.esc_html($title).'</span>';
				}
				
			}
			elseif(is_page())
			{
				if(dynamicpackages_Validators::validate_category_location())
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
					$title .= esc_html(' | '.__('Page', 'dynamicpackages').' '.$paged);
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
	public static function starting_at_archive($id = '')
	{
		$the_id = $id;
		
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}
		
		$name = 'dy_start_archive';
		$which_var = $name.'_'.$the_id;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$output = self::starting_at();
			
			if(dynamicpackages_Validators::has_children() && in_the_loop())
			{
				$prices = array();
				//dynamicpackages_Validators::has_children() returns the children obj
				$children = dynamicpackages_Validators::has_children();
				
				foreach ( $children as $child )
				{
					array_push($prices, self::starting_at($child->ID));
				}

				if(is_array($prices))
				 {
					if(count($prices) > 0)
					{
						 $output = min($prices);
					}
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}
		
		return $output;
	}
	public static function starting_at($id = '')
	{
		$the_id = $id;
		
		if($the_id == '')
		{
			$the_id = get_the_ID();
		}
		
		$output = 0;
		$name = 'dy_starting_at';
		$which_var = $name.'_'.$the_id;
		global $$which_var;		
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$prices = array();
			$max = intval(package_field('package_max_persons', $the_id));
			$min = intval(package_field('package_min_persons', $the_id));
			$duration = floatval(package_field('package_duration'));
			$price_chart = self::get_price_chart($the_id);
			$occupancy_chart = self::get_occupancy_chart($the_id);	
			$occupancy_chart = $occupancy_chart['occupancy_chart'];
			$price_type = package_field('package_starting_at_unit');
			$package_type = package_field('package_package_type');
			$mix = array();
					
			for($t = 0; $t < intval($max); $t++)
			{
				if($t >= ($min-1))
				{
					$base_price = 0;
					$occupancy_price = 0;
					
					if(is_array($price_chart))
					{
						if(isset($price_chart[$t][0]))
						{
							if($price_chart[$t][0] != '')
							{
								$base_price = floatval($price_chart[$t][0]);
							}
						}
					}
					if(is_array($occupancy_chart))
					{
						if(isset($occupancy_chart[$t][0]))
						{
							if($occupancy_chart[$t][0] != '')
							{
								$occupancy_price = floatval($occupancy_chart[$t][0]) * $duration;
							}
						}
					}
					
					$price = $base_price + $occupancy_price;
					
					if($price_type == 1)
					{
						$price = $price * intval($t+1);
					}
								
					array_push($prices, $price);				
				}
			}
							
			if(is_array($prices))
			{
				if(count($prices) > 0)
				{
					$output = floatval(min($prices));
				}
			}
			$GLOBALS[$which_var] = $output;
		}

		return $output;
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
			$price_type = package_field('package_starting_at_unit');
			$duration = package_field('package_duration');
			$duration_unit = package_field('package_length_unit');
			$output = '';
			
			if(intval($price_type) == 0)
			{
				$output = __('Per Person', 'dynamicpackages').' ';
			}
			
			if(self::increase_by_hour())
			{
				$output .= __('Per Hour', 'dynamicpackages');
			}
			else if(self::increase_by_day())
			{
				$output .=__('Per Day', 'dynamicpackages');
			}
			else
			{
				if(is_singular('packages'))
				{
					if($duration_unit == 0)
					{
						$output .= ' '.$duration.' '.self::duration_label(0, $duration);
					}
					else if($duration_unit == 1)
					{
						//hours
						$output .= ' '.$duration.' '.self::duration_label(1, $duration);
					}
					else if($duration_unit == 2)
					{
						//days
						$output .= ' '.$duration.' '.self::duration_label(2, $duration);
					}
					else if($duration_unit == 3)
					{
						//nights
						$output .= ' '.$duration.' '.self::duration_label(3, $duration);
					}
					else if($duration_unit == 4)
					{
						//weeks
						$output .= ' '.$duration.' '.self::duration_label(4, $duration);
					}						
				}				
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
			if(self::increase_by_hour() || self::increase_by_day() || intval($duration_unit) == 2 || intval($duration_unit) == 3)
			{
				if(self::get_min_nights() != null)
				{
					$duration = self::get_min_nights();
				}
			}
				
			if(!is_booking_page())
			{
				if($duration_max > $duration)
				{
					$duration_label = $duration;
					
					if($max === true)
					{
						$duration_label = __('Bookings', 'dynamicpackages').' '.$duration_label.' - '.$duration_max;
					}
				}			
			}
			else
			{
				$duration = self::get_min_nights();
				$duration_label = $duration;
			}
						
			$duration_label .= ' '.self::duration_label($duration_unit, $duration);
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
		
		if(self::increase_by_hour() || self::increase_by_day() || intval($tp_duration_unit) == 2 || intval($tp_duration_unit) == 3)
		{
			if(self::get_min_nights() != null)
			{
				$tp_duration = self::get_min_nights();
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
		
		if(array_key_exists('post_parent', $this_post) && !has_term('', 'package_location', $termid))
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

		if(array_key_exists('post_parent', $this_post) && !has_term('', 'package_category', $termid))
		{
			$termid = $this_post->post_parent;
		}		
		
		$label = '<p class="strong">'.esc_html(__('Categories:', 'dynamicpackages')).'</p><ul class="tp_location"><li><i class="fas fa-tags"></i> ';
		$locations = get_the_term_list( $termid, 'package_category', $label, '</li><li><i class="fas fa-tags"></i> ', '</li></ul>');
		echo $locations;
	}	
	public static function get_terms_conditions()
	{
		global $post;
		
		$termid = $post->ID;
		
		if(array_key_exists('post_parent', $post))
		{
			$termid = $post->post_parent;
		}		
		
		$terms = get_the_terms( $termid, 'package_terms_conditions');
				
		$terms_conditions = array();
		
		if($terms)
		{
			for($x = 0; $x < count($terms); $x++)
			{
				array_push($terms_conditions, $terms[$x]);
			}			
		}
		
		if(count($terms_conditions) > 0)
		{
			return $terms_conditions;
		}
		
	}
	public static function get_terms_conditions_list($this_post)
	{
		$termid = $this_post->ID;
		
		if(array_key_exists('post_parent', $this_post))
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
		
		if(array_key_exists('post_parent', $this_post) && !has_term('', 'package_included', $termid))
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
		
		if(array_key_exists('post_parent', $this_post) && !has_term('', 'package_not_included', $termid))
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
	
	public static function pax_num()
	{
		$output = 0;
		
		if(isset($_GET['pax_regular']))
		{
			$output = intval(sanitize_text_field($_GET['pax_regular']));
		}
		
		if(isset($_GET['pax_discount']))
		{
			$output = $output + intval(sanitize_text_field($_GET['pax_discount']));
		}
		
		if(isset($_GET['pax_free']))
		{
			$output = $output + intval(sanitize_text_field($_GET['pax_free']));
		}		
		
		return $output;
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
		$min_range = self::min_range();
		$min_range = date_i18n(get_option('date_format'), $min_range);

		$max_range = self::max_range();
		$max_range = date_i18n(get_option('date_format'), $max_range);

		return __('Bookings are only available between', 'dynamicpackages').' '.$min_range.' '.__('and', 'dynamicpackages').' '.$max_range;
	}
	public static function restrictions()
	{


		$output = '<p class="strong">'.esc_html(__('Restrictions', 'dynamicpackages')).':</p>';
		
		$output .= '<p class="minimal_success"><i class="fas fa-exclamation-circle" ></i> '.esc_html(self::people_restriction()).'</p>';
		
		echo $output;
	}
	
	public static function hour()
	{
		$hour = null;

		if(package_field('package_departure_hour' ))
		{
			if(package_field('package_departure_hour' ) != '')
			{
				$hour = package_field('package_departure_hour');
			}
		}
		
		if(isset($_GET['booking_hour']))
		{
			$hour = sanitize_text_field($_GET['booking_hour']);
		}
		
		return $hour;
	}
	public static function date()
	{
		$date = null;
		
		if(isset($_GET['booking_date']))
		{
			$date = sanitize_text_field($_GET['booking_date']);
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
		$date = date_i18n(get_option('date_format'), strtotime(sanitize_text_field($_GET['booking_date'])));
		$pax_discount = 0;
		$discount = 0;
		$free = 0;
		$adults = intval(sanitize_text_field($_GET['pax_regular']));
		$people = array();
		$people['adults'] = $adults;
		
		if(package_field('package_free' ) != '')
		{
			$free = package_field('package_free');
		}
		if(package_field('package_discount' ) != '')
		{
			$discount = package_field('package_discount');
		}
		
		if(isset($_GET['pax_discount']))
		{
			$pax_discount = intval($_GET['pax_discount']);
			
			if($pax_discount > 0)
			{
				$people['discount'] = intval(sanitize_text_field($_GET['pax_discount']));
			}
		}
		if(isset($_GET['pax_free']))
		{
			$pax_free = intval($_GET['pax_free']);
			
			if($pax_free > 0)
			{
				$people['free'] = intval(sanitize_text_field($_GET['pax_free']));
			}			
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
					$text = $v.' '.$labels[1].' '.$discount.' '.__('years old');
				}	
				if($k == 'free')
				{
					$text = $v.' '.$labels[1].' '.$free.' '.__('years old');
				}
				array_push($people_imp, $text);
			}
		}
		
		$people_imp = implode(', ', $people_imp);
		
		$description = self::show_duration().' - '.$post->post_title;
		$description .= ' ('.$date;
		
		if(self::hour() != '')
		{
			$description .= ' '.__('@', 'dynamicpackages').' '.self::hour();
		}
		
		$description .= ')';
		
		$description .= ': '.$people_imp;
		return $description;
	}	
	
	public static function tax()
	{
		$tax = get_option('dy_tax');
		$tax = floatval($tax['text_field_dynamicpackages_5']);
		return $tax;
	}

	public static function total($regular = '')
	{ 
		$which_var = 'dy_total_'.$regular;
		global $$which_var; 
		$total = 0;
		
		if(isset($$which_var))
		{
			$total = $$which_var;
		}
		else
		{
			if(is_booking_page())
			{
				$subtotal = self::subtotal();
				
				if($regular == 'regular')
				{
					$subtotal = self::subtotal_regular();
				}
				else
				{
					$total = $subtotal;
				}			
			}
			else
			{
				if(isset($_POST['total']))
				{
					$total = sanitize_text_field($_POST['total']);
				}
				else
				{
					$total = self::starting_at();
				}
			}
			
			if($total != 0)
			{
				$GLOBALS[$which_var] = $total;
			}
		}
		
		return $total;
	}

	public static function subtotal()
	{
		$sum = self::subtotal_regular();
		
		if(dynamicpackages_Validators::valid_coupon())
		{
			$sum = $sum * ((100 - floatval(self::get_coupon('discount'))) /100);
		}
		
		return $sum;
	}
	
	public static function subtotal_regular()
	{
		$price_chart = self::get_price_chart();	
		$sum = 0;
		$sum_adults = 0;
		$each_adult = 0;
		$sum_children = 0;
		$each_child = 0;
		$length_unit = package_field('package_length_unit');
	
	
		for($a = 0;  $a < count($price_chart); $a++)
		{
			if(floatval(sanitize_text_field($_GET['pax_regular'])) == ($a+1))
			{
				if($price_chart[$a][0] != '')
				{
					$each_adult = floatval($price_chart[$a][0]);
				}
				
				$sum_adults = $each_adult*floatval(sanitize_text_field($_GET['pax_regular']));
				$sum = $sum + $sum_adults;		
			}
			if(isset($_GET['pax_discount']))
			{
				if(floatval(sanitize_text_field($_GET['pax_discount'])) == floatval(($a+1)))
				{
					if(floatval($price_chart[$a][1]) > 0 && $price_chart[$a][1] != 0)
					{
						$each_child = floatval($price_chart[$a][1]);
						$sum_children = $each_child*floatval(sanitize_text_field($_GET['pax_discount']));
						$sum = $sum + $sum_children;
					}			
				}			
			}		
		}
		
		if(intval($length_unit) == 2 || intval($length_unit) == 3)
		{
			$sum = $sum + floatval(self::get_price_per_night());
		}

		if(self::increase_by_hour() || self::increase_by_day())
		{
			$sum = $sum * intval(sanitize_text_field($_GET['booking_extra']));
		}		
				
		return $sum;
	}	
	
	public static function increase_by_hour()
	{
		$package_type = intval(package_field('package_package_type' ));
		$min_duration = intval(package_field('package_duration' ));
		$max_duration = intval(package_field('package_duration_max' ));	
		$length_unit = package_field('package_length_unit');
		
		
		if($package_type == 3 && $min_duration == 1 && $length_unit == 1 && $max_duration > $min_duration)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public static function increase_by_day()
	{
		$package_type = intval(package_field('package_package_type' ));
		$min_duration = intval(package_field('package_duration' ));
		$max_duration = intval(package_field('package_duration_max' ));	
		$length_unit = package_field('package_length_unit');
		
		
		if($package_type == 2 && $min_duration == 1 && $length_unit == 2 && $max_duration > $min_duration)
		{
			return true;
		}
		else
		{
			return false;
		}
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
		
		if(!dynamicpackages_Validators::is_child() && isset($post))
		{
			$label = __('Packages', 'dynamicpackages');
			
			if(package_field('package_length_unit') == 3)
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
					$output .= '<table class="pure-table pure-table-bordered"><thead class="text-center"><tr><th colspan="3"><strong>'.esc_html(self::count_child()).'</strong> '.esc_html($label).':</th></tr></thead><tbody>';
					
					foreach($children_array as $item)
					{
						if(array_key_exists('post_name', $item))
						{
							if($item->post_name != '')
							{
								$subpackage_name = 'package_child_title';
								
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
								$output .= '<td><a class="small pure-button pure-button-primary rounded block width-100 borderbox" href="'.esc_url(get_the_permalink().$item->post_name.'/').'">'.esc_html(__('Rates', 'dynamicpackages')).'</a></td>';
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
					
					if(intval(self::starting_at()) > 0)
					{
						$excerpt .= ' '.dy_utilities::currency_symbol().intval(self::starting_at()).' '.self::price_type().'. ';
						
						if(package_field('package_payment') > 0 && package_field('package_deposit' ) > 0)
						{
							$excerpt .= __('Book it with a', 'dynamicpackages').' '.package_field('package_deposit' ).'% '.__('deposit', 'dynamicpackages').'. ';
						}
					}
					
				}
				else
				{
					$excerpt = '<span class="linkcolor strong small">'.esc_html(self::show_duration(true)).'</span> - ';
				}		
				
				$excerpt .= esc_html($post->post_excerpt);
				
			}
		}
		elseif(is_page())
		{
			global $post;
			
			if(is_object($post))
			{
				if(dynamicpackages_Validators::validate_category_location() && has_shortcode($post->post_content, 'packages'))
				{
					$excerpt = null;
				}				
			}
		}
		
		return $excerpt;
	}
	
	public static function deque_jetpack()
	{	
		if(is_page() && dynamicpackages_Validators::validate_category_location())
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
		if(dynamicpackages_Validators::validate_category_location())
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
	

	
	public static function get_deposit()
	{
		global $dy_get_deposit;
		$output =  25;
		
		if(isset($dy_get_deposit))
		{
			$output = $dy_get_deposit;
		}
		else
		{
			if(package_field('package_payment' ) == 1 && intval(package_field('package_auto_booking')) == 1)
			{
				if(floatval(package_field('package_deposit' )) > 0)
				{
					$output = package_field('package_deposit');
					
					if(isset($_GET['quote']))
					{
						$output = 0;
					}
				}
			}
			else
			{
				$output = 0;
			}
			
			$GLOBALS['dy_get_deposit'] = $output;
		}
		return $output;
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
	public static function get_coupon($option)
	{
		$which_var = $option.'_get_coupon';
		global $$which_var;
		$output = null;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$option = strtolower($option);
			$coupons = json_decode(html_entity_decode(package_field('package_coupons' )), true);
			$output = 'option not selected';
			$booking_coupon = strtolower(sanitize_text_field($_GET['booking_coupon']));
			$booking_coupon = preg_replace("/[^A-Za-z0-9 ]/", '', $booking_coupon);
			
			if(array_key_exists('coupons', $coupons))
			{
				$coupons = $coupons['coupons'];
				
				for($x = 0; $x < count($coupons); $x++)
				{
					if($booking_coupon == preg_replace("/[^A-Za-z0-9 ]/", '', strtolower($coupons[$x][0])))
					{
						if($option == 'code')
						{
							$output = $coupons[$x][0];
						}
						else if($option == 'discount')
						{
							$output = $coupons[$x][1];
						}	
						else if($option == 'expiration')
						{
							$output = $coupons[$x][2];
						}
						else if($option == 'from')
						{
							$output = $coupons[$x][3];
						}
						else if($option == 'to')
						{
							$output = $coupons[$x][4];
						}					
					}
				}
			}
			
			if($output != null)
			{
				$GLOBALS[$which_var] = $output;
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
	
	public static function details_add()
	{
		$output = array();
		
		if(!is_booking_page())
		{
			if(package_field('package_event_date') == '')
			{
				array_push($output, '<i class="fas fa-calendar"></i> '.esc_html(self::enabled_days()));
			}
			else
			{
				$booking_date = date_i18n(get_option('date_format'), strtotime(package_field('package_event_date')));
				array_push($output, '<i class="fas fa-calendar"></i> '.esc_html($booking_date));
			}
			
			if(package_field('package_min_hour' ) && package_field('package_max_hour' ))
			{
				array_push($output, '<i class="fas fa-clock"></i> '.esc_html(__('Schedule', 'dynamicpackages').' '.package_field('package_min_hour' ).' - '.package_field('package_max_hour' )));
			}
		}
		
		if(is_booking_page())
		{
			$booking_date = date_i18n(get_option('date_format'), self::booking_date());
			array_push($output, '<i class="fas fa-calendar"></i> '.esc_html($booking_date));
			array_push($output, '<i class="fas fa-clock"></i> '.esc_html(self::show_duration()));
		}
		
		if(is_singular('packages') && package_field('package_check_in_hour' ))
		{
			array_push($output, '<i class="fas fa-clock"></i> '.esc_html(__('Check-in', 'dynamicpackages').' '.package_field('package_check_in_hour' )));
		}
		if(self::hour() != '' && is_singular('packages'))
		{
			array_push($output, '<i class="fas fa-clock"></i> '.esc_html(__('Hour', 'dynamicpackages').' '.self::hour()));
		}
		if(package_field('package_departure_address' ))
		{
			array_push($output, '<i class="fas fa-map-marker"></i> '.esc_html(package_field('package_departure_address' )));
		}
		if(!is_booking_page())
		{
			$booking_date = date_i18n(get_option('date_format'), self::booking_date());
			array_push($output, '<i class="fas fa-check"></i> '.esc_html(self::show_duration(true)));
		}
		
		return $output;
	}
	public static function details_cb()
	{
		return apply_filters('package_details', array());
	}
	public static function details()
	{
		echo '<div class="dy_pad bottom-5">'.implode('</div><div class="dy_pad bottom-5">', self::details_cb()).'</div>';
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
			$week_days = self::get_week_days_list();
			
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
			$range = self::get_date_range($today, $last_day);
			$disabled_range = self::get_disabled_range();
			
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
	public static function get_date_range($from, $to)
	{
		$output = array();
		$from = new DateTime($from);
		$to = new DateTime($to);
		$to = $to->modify('+1 day');
		
		$range = new DatePeriod($from, new DateInterval('P1D'), $to);

		foreach ($range as $key => $value)
		{
			array_push($output, $value->format('Y-m-d'));
		}
		
		return $output;
	}
	public static function get_disabled_range()
	{
		$output = array();
		$disabled = json_decode(html_entity_decode(package_field('package_disabled_dates' )), true);
		
		if(array_key_exists('disabled_dates', $disabled))
		{		
			$disabled_dates = $disabled['disabled_dates'];
					
			for($x = 0; $x < count($disabled_dates); $x++)
			{
				$from = $disabled_dates[$x][0];
				$to = $disabled_dates[$x][1];
				array_push($output, self::get_date_range($from, $to));
			}
		}
		
		return self::arrayFlatten($output);
	}
	
	static function arrayFlatten($array) { 
		$output = array();
		
		for($x = 0; $x < count($array); $x++)
		{
			for($y = 0; $y < count($array[$x]); $y++)
			{
				array_push($output, $array[$x][$y]);
			}
		}
		return array_unique($output);
	}
	public static function get_week_days_list()
	{
		$output = array();
		$days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
		
		for($x = 0; $x < count($days); $x++)
		{
			if(intval(package_field('package_day_'.$days[$x] )) == 1)
			{
				array_push($output, $x+1);
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
		if(dynamicpackages_Validators::has_coupon())
		{
			$coupons = self::get_all_coupons();
			$output = null;
						
			if(is_array($coupons))
			{
				for($x = 0; $x < count($coupons); $x++)
				{
					if($coupons[$x][3] === true && $coupons[$x][0] != '')
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
								$coupon_hide = apply_filters('coupon_gateway_hide', '', $coupons[$x][0]);
								$output .= '<div class="large dy_coupon bottom-20 '.esc_html($coupon_hide).'">';
								$label .= esc_html(__('Get a', 'dynamicpackages'));
								$label .= ' <strong>'.esc_html($coupons[$x][1]).'%</strong>';
								$label .= ' '.esc_html(__('off using the coupon code', 'dynamicpackages'));
								$label .= ' <strong>'.strtoupper(esc_html($coupons[$x][0])).'</strong>.';
								
								if($expiration != '')
								{
									$label .= ' '.esc_html(__('Offer valid until', 'dynamicpackages'));
									$label .= ' '.esc_html(date_i18n(get_option('date_format' ), strtotime($coupons[$x][2]))).'.';
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
		
		if(isset($post) && dynamicpackages_Validators::is_child())
		{
			$label = __('Similar packages', 'dynamicpackages');
			
			if(package_field('package_length_unit') == 3)
			{
				$label = __('Similar accommodations', 'dynamicpackages');
			}
			
			
			echo '<a class="pure-button rounded block width-100 borderbox" href="'.esc_url(get_the_permalink($post->post_parent)).'"><strong>'.esc_html(self::count_child($post->post_parent)).'</strong> '.esc_html($label).'</a>';			
			
		}
	}
}