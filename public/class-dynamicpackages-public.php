<?php


class Dynamic_Packages_Public {


	private $plugin_name;
	private $version;

	public function __construct() {
		
		$this->args();
		$this->init();
	}

	public function args()
	{
		$this->plugin_dir_url_file = plugin_dir_url( __FILE__ );
		$this->plugin_dir_url_dir = plugin_dir_url( __DIR__ );
		$this->dirname_file = dirname( __FILE__ );
	}
	
	public function init()
	{
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'), 11);
		add_filter('template_include', array(&$this, 'package_template'), 99);
		add_filter('the_content', array(&$this, 'the_content'), 100);
		add_filter('pre_get_document_title', array(&$this, 'wp_title'), 100);
		add_filter('wp_title', array(&$this, 'wp_title'), 100);
		add_filter('the_title', array(&$this, 'modify_title'), 100);
		add_filter('single_term_title', array(&$this, 'modify_tax_title'));
		add_action('pre_get_posts', array(&$this, 'set_one_tax_per_page'));
		add_action('wp_head', array(&$this, 'meta_tags'));
		add_filter('get_the_excerpt', array(&$this, 'modify_excerpt'));
		add_filter('term_description', array(&$this, 'modify_term_description'));
		add_action('wp_head', array(&$this, 'location_category_canonical'));
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
	}

	
	public function enqueue_styles() {
		
		if(!is_404())
		{
			if(is_tax('package_category') ||is_tax('package_location') || is_post_type_archive('packages'))
			{
				$this->css();
			}
			else if(is_singular('packages'))
			{
				$this->css();
				$this->datepickerCSS();
			}
			else
			{
				global $post;
				
				if(isset($post))
				{
					if(has_shortcode( $post->post_content, 'packages') || has_shortcode( $post->post_content, 'package_contact'))
					{
						$this->css();
					}					
				}			
			}
		}
	}
	
	public function css()
	{
		wp_enqueue_style('minimalLayout', $this->plugin_dir_url_file . 'css/minimal-layout.css', array(), '', 'all');
		wp_add_inline_style('minimalLayout', $this->get_inline_css('dynamicpackages-public'));
	}
	
	public function get_inline_css($sheet)
	{
		ob_start();
		require_once($this->dirname_file . '/css/'.$sheet.'.css');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}	

	public function enqueue_scripts() {
 
		global $post;
		$strings = array();
		$dep = array( 'jquery', 'landing-cookies');
		$ipgeolocation = null;
		$enqueue_public = false;
		$enqueue_archive = false;
		$enqueue_recaptcha = false;
		$enqueue_sha512 = false;
		$enqueue_datepicker = false;
		
		wp_enqueue_script('landing-cookies', $this->plugin_dir_url_file . 'js/cookies.js', array('jquery'), '', true);
		
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
			wp_enqueue_script('sha512', $this->plugin_dir_url_file . 'js/sha512.js', array(), 'async_defer', true );
				array_push($dep, 'sha512');
		}
		
		if($enqueue_datepicker)
		{
			$this->datepickerJS();
		}		
		
		if($enqueue_public)
		{

			
			$strings['recaptchaSiteKey'] = get_option('captcha_site_key');
			$strings['postId'] = get_the_ID();
			$strings['ipGeolocation'] = get_option('ipgeolocation');
			$strings['textCopiedToClipBoard'] = __('Copied to Clipboard!', 'dynamicpackages');
			$strings['pluginDirUrl'] = esc_url($this->plugin_dir_url_dir);
			$strings['permaLink'] = esc_url(get_the_permalink());
			$strings['booking_allowed_hours'] = $this->booking_allowed_hours();

			wp_enqueue_script('dynamicpackages', $this->plugin_dir_url_file . 'js/dynamicpackages-public.js', $dep, time(), true );
			wp_add_inline_script('dynamicpackages', 'function dyStrings(){ return '.json_encode($strings).';}', 'before');
		}
		
		if($enqueue_archive)
		{
			wp_enqueue_script('dynamicpackages-archive', $this->plugin_dir_url_file . 'js/dynamicpackages-archives.js', array('jquery'), time(), true );
		}
		
		wp_enqueue_script('minimal-fontawesome', 'https://use.fontawesome.com/releases/v5.3.1/js/all.js?async=async', '', '', true);
	}
	
	public function datepickerCSS()
	{
		wp_enqueue_style( 'picker-css', $this->plugin_dir_url_file . 'css/picker/default.css', array(), '3.6.2', 'all');
		wp_add_inline_style('picker-css', $this->get_inline_css('picker/default.date'));
		wp_add_inline_style('picker-css', $this->get_inline_css('picker/default.time'));
	}
	
	public function datepickerJS()
	{
		//pikadate
		wp_enqueue_script( 'picker-js', $this->plugin_dir_url_file . 'js/picker/picker.js', array('jquery'), '3.6.2', true);
		wp_enqueue_script( 'picker-date-js', $this->plugin_dir_url_file . 'js/picker/picker.date.js', array('jquery', 'picker-js'), '3.6.2', true);
		wp_enqueue_script( 'picker-time-js', $this->plugin_dir_url_file . 'js/picker/picker.time.js',array('jquery', 'picker-js'), '3.6.2', true);	
		wp_enqueue_script( 'picker-legacy', $this->plugin_dir_url_file . 'js/picker/legacy.js', array('jquery', 'picker-js'), '3.6.2', true);

		$picker_translation = 'js/picker/translations/'.get_locale().'.js';
				
		if(file_exists($this->dirname_file.'/'.$picker_translation))
		{
			wp_enqueue_script( 'picker-time-translation', $this->plugin_dir_url_file.$picker_translation, array('jquery', 'picker-js'), '3.6.2', true);
		}		
	}
		
	public function meta_tags()
	{
		if(is_singular('packages'))
		{
			ob_start();
			require_once($this->dirname_file . '/partials/meta-tags.php');
			$content = ob_get_contents();
			ob_end_clean();	
			echo $content;
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
		$dy_request_invalids = apply_filters('dy_request_invalids', null);
		
		if($dy_request_invalids)
		{
			$content = $dy_request_invalids;
		}
		else
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
					require_once($this->dirname_file . '/partials/single.php');
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
		$days = dy_utilities::get_week_days_abbr();
		
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
	
	public function booking_allowed_hours()
	{	

		$output = array();

		if(is_singular('packages'))
		{
			$by_hour = intval(package_field('package_by_hour'));		
			$min_hour = package_field('package_min_hour');		
			$max_hour = package_field('package_max_hour');
			
			if($by_hour === 1)
			{				
				if($min_hour !== '')
				{
					$min_hour = strtotime($min_hour);
					$output[] = array(intval(date('H', $min_hour)), intval(date('i', $min_hour)));
				}
				if($max_hour !== '')
				{
					$max_hour = strtotime($max_hour);
					$output[] = array(intval(date('H', $max_hour)), intval(date('i', $max_hour)));			
				}
			}
			
			return $output;
		}
	}
	
	public function wp_title($title)
	{
		
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			$title = __('Error', 'dynamicpackages');
		}
		else
		{
			global $polylang;
			global $post;

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
					
					if(isset($_GET['keywords']))
					{
						$keywords_param = sanitize_text_field($_GET['keywords']);

						if($keywords_param !== '')
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

						if($category_param !== 'any' && $category_param !== '')
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

						if($location_param !== 'any' && $location_param !== '')
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
	
	public function modify_title($title)
	{
		global $dy_request_invalids;
		
		if(isset($dy_request_invalids))
		{
			$title = __('Error', 'dynamicpackages');
		}
		else
		{
			global $post;

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
						$title = '<span class="linkcolor">'. esc_html(__('Booking Page', 'dynamicpackages')) .'</span>: <span data-id="package-title">'. $title .'</span>';
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

						if(isset($_GET['keywords']))
						{
							$keywords_param = sanitize_text_field($_GET['keywords']);

							if($keywords_param != '')
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

							if($category_param !== 'any' && $category_param !== '')
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

							if($location_param !== 'any' && $location_param !== '')
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
				$output .= __(' / ', 'dynamicpackages').dy_utilities::duration_label($duration_unit, 1);
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

		$which_var = 'dy_get_terms_conditions';
		global $$which_var;

		if(isset($$which_var))
		{
			$terms_conditions = $$which_var;
		}
		else
		{
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

			$GLOBALS[$which_var] = $terms_conditions;
		}
		
		return $terms_conditions;
	}
	public function get_terms_conditions_list()
	{
		global $post;
		$output = '';
		$termid = $post->ID;
		
		if(property_exists($post, 'post_parent'))
		{
			$termid = $post->post_parent;
		}
		
		$included = get_the_terms( $termid, 'package_terms_conditions');	
		$included_array = array();

		if($included)
		{
			for($x = 0; $x < count($included); $x++)
			{
				array_push($included_array, $included[$x]->name);
			}			
		}
		
		$label = '<p class="strong">'.esc_html(__('Terms & Conditions:', 'dynamicpackages')).'</p><ul class="tp_location"><li><i class="fas fa-exclamation-triangle" ></i> ';
		$output = get_the_term_list( $termid, 'package_terms_conditions', $label, '</li><li><i class="fas fa-exclamation-triangle" ></i> ', '</li></ul>');
		echo $output;	
	}	
	public function get_included_list()
	{
		global $post;
		$termid =$post->ID;
		$output = '';
		
		if(property_exists($post, 'post_parent') && !has_term('', 'package_included', $termid))
		{
			$termid = $post->post_parent;
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
		echo $output;
	}
	
	public function get_not_included_list()
	{
		global $post;
		$termid = $post->ID;
		$output = '';
		
		if(property_exists($post, 'post_parent') && !has_term('', 'package_not_included', $termid))
		{
			$termid = $post->post_parent;
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
		echo $output;
	}
	
	public function set_one_tax_per_page( $query )
	{
		if((is_tax('package_location') || is_tax('package_category') || is_tax('package_terms_conditions')) && $query->is_main_query())
		{
			$query->set( 'posts_per_page', 1 );
		}
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
				$description = dy_utilities::show_duration();
			}
					
			$description .= ' | ' . $post->post_title;
			$description .= ' ('.$itinerary.'): ';
			$description .= $people_imp;			
			$output = $description;			
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
	
	public function children_package()
	{
		$which_var = 'dy_children_package';
		global $$which_var;
		$output = '';

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			global $post;
			global $polylang;		
			
			if(!dy_validators::is_child() && isset($post))
			{
				$duration = package_field('package_duration');
				$duration_unit = package_field('package_length_unit');
				$label = __('Packages', 'dynamicpackages');
				
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
						$rows = '';
						
						foreach($children_array as $item)
						{
							if(property_exists($item, 'post_name'))
							{
								if($item->post_name != '')
								{
									$has_rows = true;
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
									
									$rows .= '<tr>';
									$rows .= '<td>'.esc_html($subpackage_name).'</td>';
									$rows .= '<td class="text-center">'.esc_html(package_field('package_max_persons', $item->ID)).' <i class="fas fa-male"></i></td>';
									$rows .= '<td><a class="small pure-button pure-button-primary rounded block width-100 borderbox" href="'.esc_url(rtrim(get_the_permalink(), '/').'/'.$item->post_name.'/').'">'.esc_html($button_label).'</a></td>';
									$rows .= '</tr>';							
								}
							}
						}
						
						if($has_rows === true)
						{
							$output .= '<table class="pure-table pure-table-bordered"><thead class="text-center"><tr><th colspan="3"><strong>'.esc_html($this->count_child()).'</strong> '.esc_html($label).':</th></tr></thead><tbody class="small">'.$rows.'</tbody></table>';
						}		
					}
				}			
			}

			$GLOBALS[$which_var] = $output;
		}


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
						$excerpt .= ' '.dy_utilities::currency_symbol().intval(dy_utilities::starting_at()).' '.apply_filters('dy_price_type', null).'. ';
						
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
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$url = get_the_permalink().'?';
			$url_var = array();
			
			if($paged > 1)
			{
				$url_var['paged'] = sanitize_text_field($paged);
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
				if($_GET['sort'] == 'new' || $_GET['sort'] == 'low' || $_GET['sort'] == 'high' || $_GET['sort'] == 'today' || $_GET['sort'] == 'tomorrow' || $_GET['sort'] == 'week' || $_GET['sort'] == 'month')
				{
					$url_var['sort'] = sanitize_text_field($_GET['sort']);
				}
			}
			if(isset($_GET['keywords']))
			{
				$search = strtolower(sanitize_text_field($_GET['keywords']));
				$search = preg_replace('/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ\s]/', '', $search);
				$search =  preg_replace('/\s+/', ' ', $search);				
				$search =  substr($search, 0, 25);
				$url_var['keywords'] = $search;
			}			

			$url_var = http_build_query($url_var);
			
			echo '<link rel="canonical" href="'.esc_url($url.$url_var).'" />';
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
		$output = null;
		
		if(isset($get_all_coupons))
		{
			$output = $get_all_coupons;
		}
		else
		{
			$coupons = json_decode(html_entity_decode(package_field('package_coupons')), true);
			
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
		
		return $output;
	}
	
	public function icon($icon)
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
	
	public function details()
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
			'enabled_days' => array('calendar', $this->enabled_days()),
			'schedule' => array('clock', __('Schedule', 'dynamicpackages').' '.package_field('package_min_hour' ).' - '.package_field('package_max_hour' )),
			'label_departure' => array(null, __('Departure', 'dynamicpackages')),
			'booking_date' => array('calendar', $booking_date),
			'duration' => array('clock', dy_utilities::show_duration()),
			'check_in' => array('clock', __('Check-in', 'dynamicpackages').' '.package_field('package_check_in_hour' )),
			'start_hour' => array('clock', __('Hour', 'dynamicpackages').' '.dy_utilities::hour()),
			'start_address' => array('marker', package_field('package_start_address')),
			'label_return' => array(null, __('Return', 'dynamicpackages')),
			'end_date' => array('calendar', $end_date),
			'check_in_end_hour' => array('clock', __('Check-in', 'dynamicpackages').' '.package_field('package_check_in_end_hour')),
			'return_hour' => array('clock', __('Hour', 'dynamicpackages').' '. package_field('package_return_hour')),
			'return_address' => array('clock', package_field('package_return_address'))
			
		);
		
		if(!$this->enabled_days())
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
		if(!$end_date && $is_transport && (is_booking_page() || is_checkout_page()))
		{
			unset($args['end_date']);
			unset($args['label_return']);
			unset($args['return_hour']);
			unset($args['check_in_end_hour']);
			unset($args['return_address']);
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

		if(is_booking_page() || is_checkout_page())
		{
			unset($args['schedule']);
		}
		
		foreach($args as $k => $v)
		{
			$output .= '<div class="dy_pad bottom-5">';
			$output .= ($v[0]) ? $this->icon($v[0]) .' '. esc_html($v[1]) : '<strong>'.esc_html($v[1]).'</strong>';
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
					
					
					$output = '<span class="dy_event_date strong uppercase small block padding-5">';	
					$output .= esc_html($label);
					$output .= '</span>';	
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
										$label .= '<br/><small>' . sprintf(__('This coupon is valid for booking of minimum %s %s.', 'dynamicpackages'), esc_html($coupons[$x][4]), esc_html(dy_utilities::duration_label($duration_unit, $coupons[$x][4]))).'</small>';
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
		$which_var = 'dy_similar_packages_link';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			global $post;
			
			if(isset($post) && dy_validators::is_child())
			{
				$output = '<a class="pure-button rounded block width-100 borderbox" href="'.esc_url(get_the_permalink($post->post_parent)).'"><strong>'.esc_html($this->count_child($post->post_parent)).'</strong> '.esc_html(__('Similar packages', 'dynamicpackages')).'</a>';			
			}

			$GLOBALS[$which_var] = $output;
		}

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
				$description = rtrim(trim($description), '.') . '. ' . __('From', 'dynamicpackages') . ' ' . dy_utilities::currency_symbol().$starting_at.' '.apply_filters('dy_price_type', null) . '.';
			}			
		}
		
		return $description;
	}

	public function event_arr()
	{
		$output = array();
		$which_var = 'event_arr';
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{		
			$package_start_address = package_field('package_start_address');
			$package_start_hour = package_field('package_start_hour');

			if($package_start_address !== '' && $package_start_hour !== '')
			{
				$package_event_date = package_field('package_event_date');
				
				if($package_event_date !== '')
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

			$GLOBALS[$which_var] = $output;
		}
		
		return $output;
	}
}