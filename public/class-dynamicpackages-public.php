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

		add_action('init', array($this, 'init'));

		//scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), DY_IS_PACKAGE_PAGE_PRIORITY);

		//redirect
		add_action('template_redirect', array($this, 'template_redirect'));
		add_filter('post_type_link', array($this, 'post_type_link'), DY_IS_PACKAGE_PAGE_PRIORITY, 2);

		//template
		add_filter('template_include', array($this, 'package_template'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('the_content', array($this, 'the_content'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('pre_get_document_title', array($this, 'wp_title'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('the_title', array($this, 'the_title'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('single_term_title', array($this, 'modify_tax_title'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('get_the_excerpt', array($this, 'get_the_excerpt'), DY_IS_PACKAGE_PAGE_PRIORITY);
		add_filter('term_description', array($this, 'modify_term_description'));
		add_action('pre_get_posts', array($this, 'set_one_tax_per_page'));


		//packages
		add_filter('dy_details', array($this, 'details'));
		add_action('dy_description', array($this, 'description'));
		add_action('dy_show_coupons', array($this, 'show_coupons'));
		add_filter('minimal_description', array($this, 'meta_description'));
		add_filter('dy_price_type', array($this, 'price_type'));
		add_filter('dy_booking_sidebar', array($this, 'booking_sidebar'));
		add_action('dy_children_package', array($this, 'children_package'));
		add_action('dy_similar_packages_link', array($this, 'similar_packages_link'));
		add_action('dy_get_terms_conditions_list', array($this, 'get_terms_conditions_list'));
		add_action('dy_get_included_list', array($this, 'get_included_list'));
		add_action('dy_get_not_included_list', array($this, 'get_not_included_list'));
		add_action('dy_get_category_list', array($this, 'get_category_list'));
		add_action('dy_get_location_list', array($this, 'get_location_list'));
		add_action('dy_show_badge', array($this, 'show_badge'));
		add_action('dy_show_event_date', array($this, 'show_event_date'));
		add_action('dy_edit_link', array($this, 'edit_link'));
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
		

	
	public function package_template(string $template): string
	{
		$is_package_page = is_tax([
			'package_terms_conditions',
			'package_location',
			'package_category',
		]) || get_post_type() === 'packages';

		if (!$is_package_page) {
			return $template;
		}

		return locate_template(['page.php']) ?: $template;
	}

	public function booking_sidebar(): string
	{
		ob_start();

		require $this->dirname_file . '/partials/quote-form.php';

		return (string) ob_get_clean();
	}

	public function the_content(mixed $content): string
	{
		$content = is_string($content) ? $content : '';

		if (is_tax([
			'package_location',
			'package_category',
		])) {
			ob_start();

			require $this->dirname_file . '/partials/archive.php';

			return (string) ob_get_clean();
		}

		if (is_tax('package_terms_conditions')) {
			$term = get_queried_object();

			if (!$term instanceof WP_Term) {
				return $content;
			}

			$parsedown = new Parsedown();

			return (string) $parsedown->text($term->description);
		}

		if (is_singular('packages') && !is_booking_page()) {
			$partial_content = $content;

			ob_start();

			require $this->dirname_file . '/partials/single.php';

			return (string) ob_get_clean();
		}

		return $content;
	}
	
	public function wp_title(mixed $title): string
	{
		$title = is_string($title) ? $title : '';

		if (!is_booking_page()) {
			return $title;
		}

		$post = get_queried_object();

		if (!$post instanceof WP_Post) {
			return $title;
		}

		return sprintf(
			'%s %s | %s',
			__('Online Booking', 'dynamicpackages'),
			$post->post_title,
			get_bloginfo('name')
		);
	}
	
	public function the_title(mixed $title): string
	{
		$title = is_string($title) ? $title : '';

		if (!in_the_loop() || !is_booking_page()) {
			return $title;
		}

		return sprintf(
			__('Booking Page: %s', 'dynamicpackages'),
			$title
		);
	}
	
	public function modify_tax_title(string $title): string
	{
		if (!is_tax('package_terms_conditions') || !in_the_loop()) {
			return $title;
		}

		return sprintf(
			'<span class="linkcolor">%s</span>',
			$title
		);
	}

	
	public static function price_type(bool $force_per_person = false): string
	{
		$name      = 'dy_price_type';
		$the_id    = get_dy_id();
		$cache_key = sprintf(
			'%s_%s_%d',
			$name,
			$the_id,
			(int) $force_per_person
		);

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$price_type    = (int) package_field('package_fixed_price');
		$package_type  = dy_utilities::get_package_type($the_id);
		$duration_unit = (int) package_field('package_length_unit');

		$output = ($price_type === 0 || $force_per_person)
			? __('per person', 'dynamicpackages')
			: '';

		$suffix = match ($package_type) {
			'multi-day' => sprintf(
				'%s %s',
				__('per', 'dynamicpackages'),
				dy_utilities::duration_label($duration_unit, 1)
			),
			'rental-per-hour' => __('per hour', 'dynamicpackages'),
			'rental-per-day'  => __('per day', 'dynamicpackages'),
			'transport'       => __('one-way', 'dynamicpackages'),
			default           => '',
		};

		if ($output !== '' && $suffix !== '') {
			$output .= ' ';
		}

		$output .= $suffix;

		return self::$cache[$cache_key] = $output;
	}

	private function render_tax_list(
		string $taxonomy,
		string $label,
		bool $hierarchical,
		string $icon
	): void {
		$cache_key = 'dy_' . $taxonomy . '_list';

		self::$cache[$cache_key] ??= dy_utilities::get_tax_list(
			$taxonomy,
			$label,
			$hierarchical,
			$icon
		);

		echo self::$cache[$cache_key];
	}

	public function get_location_list(): void
	{
		$this->render_tax_list(
			'package_location',
			__('Places of Interest:', 'dynamicpackages'),
			true,
			'dashicons dashicons-location'
		);
	}

	public function get_category_list(): void
	{
		$this->render_tax_list(
			'package_category',
			__('Categories:', 'dynamicpackages'),
			true,
			'dashicons dashicons-tag'
		);
	}

	public function get_terms_conditions_list(): void
	{
		$this->render_tax_list(
			'package_terms_conditions',
			__('Terms & Conditions:', 'dynamicpackages'),
			true,
			'dashicons dashicons-warning'
		);
	}

	public function get_included_list(): void
	{
		$this->render_tax_list(
			'package_included',
			__('Included:', 'dynamicpackages'),
			false,
			'dashicons dashicons-yes'
		);
	}

	public function get_not_included_list(): void
	{
		$this->render_tax_list(
			'package_not_included',
			__('Not Included:', 'dynamicpackages'),
			false,
			'dashicons dashicons-no'
		);
	}
		
	public function set_one_tax_per_page(\WP_Query $query): void
	{
		if (
			!$query->is_main_query() ||
			!$query->is_tax([
				'package_location',
				'package_category',
				'package_terms_conditions',
			])
		) {
			return;
		}

		$query->set('posts_per_page', 1);
	}
	
	public static function description() {

		//in this example free of cost if for infants under centain age and discount if four children older than infant but under center age
		// the correct example of adult with children is "Round trip | Ferry to Saboga Island (Departure April 12, 2025 | Return April 13, 2025): 1 adult, 1 child under 4 years old"
		// the correct example of adult with chidlren and infant is "Round trip | Ferry to Saboga Island (Departure April 12, 2025 | Return April 13, 2025): 1 adult, 1 infant under 4 years old, 1 child under 11 years old"

		global $post;
	
		// Guard: nothing to do without a post or booking date
		if ( empty( $post ) || empty( $_REQUEST['start_date'] ) ) {
			return '';
		}

		$name = 'dy_description_str';
		$the_id = get_dy_id();
		$cache_key = $name.'_'.$the_id;

		if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }
	
		// Core flags & data
		$isTransport    = dy_utilities::get_package_type() === 'transport';
		$routeRaw       = sanitize_text_field( $_REQUEST['route'] ?? '' );
		$modify_route    = $isTransport && $routeRaw === '1';
	
		$startShort     = package_field( 'package_start_address_short' );
		$returnShort    = package_field( 'package_return_address_short' );
	
		// Dates & hours
		$depDate        = dy_utilities::format_date( dy_utilities::start_date() );
		$depHour        = dy_utilities::start_hour() ? '@ ' . dy_utilities::start_hour() : '';
	
		$endRaw         = sanitize_text_field( $_REQUEST['end_date'] ?? '' );
		$hasReturn      = $endRaw && is_valid_date( $endRaw );
		$retDate        = $hasReturn ? dy_utilities::format_date( dy_utilities::end_date() ) : '';
		$retHour        = ( $hasReturn && dy_utilities::end_hour() ) ? '@ ' . dy_utilities::end_hour() : '';
	
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
	
	
	public function show_badge(): void
	{
		$code = (int) package_field('package_badge');

		if ($code <= 0) {
			return;
		}

		$message = match ($code) {
			1 => __('Best Seller', 'dynamicpackages'),
			2 => __('New', 'dynamicpackages'),
			3 => __('Offer', 'dynamicpackages'),
			4 => __('Featured', 'dynamicpackages'),
			5 => __('Last Minute Deal', 'dynamicpackages'),
			default => '',
		};

		if ($message === '') {
			return;
		}

		$color = package_field('package_badge_color');

		printf(
			'<small class="dy_badge_class %s">%s</small>',
			esc_attr($color),
			esc_html($message)
		);
	}

	
	public function children_package()
	{
		$cache_key = 'dy_children_package';
		$output = '';

        if (array_key_exists($cache_key, self::$cache)) {
            echo self::$cache[$cache_key];
			return;
        }

		global $post;
		
		if(!dy_validators::is_child() && ($post instanceof WP_Post))
		{
			$header_name = 'package_child_title_'.$this->current_language;
			$hide_prices = (int) package_field('package_show_pricing', $post->ID) === 1;

			$header_title = package_field($header_name, $post->ID);
			$label = (empty($header_title)) 
				? __('Packages', 'dynamicpackages') 
				: sprintf(
					__('%s available %s', 'dynamicpackages'), 
					$this->count_child(), 
					$header_title
				);
			
			$args = array(
				'post_parent' => $post->ID,
				'post_type'   => 'packages', 
				'numberposts' => -1
			); 
			
			$children_array = (array) get_children($args);

			if(empty($children_array)) return '';

			$has_rows = false;
			$rows_arr = [];
			
			foreach($children_array as $item)
			{
				if(!property_exists($item, 'post_name') || empty($item->post_name)) continue;
				
					$row = '';
					$starting_at = (int) dy_utilities::starting_at($item->ID);
					$subpackage_name = 'package_child_title_'.$this->current_language;
					$button_label = ($starting_at > 0 && $hide_prices === false) ? '$' . $starting_at : __('Rates', 'dynamicpackages');
					
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
			
			if (!empty($rows_arr)) {

				usort($rows_arr, function($a, $b) {
					return $a['price'] <=> $b['price'];
				});

				$rows = implode('', array_column($rows_arr, 'row'));

				$price_type_label = sprintf(__('Prices %s', 'dynamicpackages'), (string) apply_filters('dy_price_type', ''));

				$output .= sprintf(
					'<table class="pure-table pure-table-bordered bottom-20 width-100">'
					. '<thead class="text-center"><tr><th colspan="3">%s - <small class="semibold text-muted">%s</small></th></tr></thead>'
					. '<tbody class="small">%s</tbody>'
					. '</table>',
					esc_html($label),
					esc_html($price_type_label),
					$rows
				);
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		echo $output;
		return;
	}
	
	public function get_the_excerpt(mixed $excerpt): string
	{
		$excerpt = is_string($excerpt) ? $excerpt : '';

		if (!is_singular('packages')) {
			return $excerpt;
		}

		if (is_booking_page()) {
			return '';
		}

		if (in_the_loop()) {
			$duration = trim((string) dy_utilities::show_duration(true));

			return $duration !== ''
				? $duration . ' - ' . $excerpt
				: $excerpt;
		}

		$starting_at = (float) dy_utilities::starting_at();

		if ($starting_at <= 0) {
			return $excerpt;
		}

		$price_type = trim((string) apply_filters('dy_price_type', false));

		$prefix = sprintf(
			'%s %s%s. ',
			__('Starting at', 'dynamicpackages'),
			wrap_money_full($starting_at),
			$price_type !== '' ? ' ' . $price_type : ''
		);

		$package_payment = absint(package_field('package_payment'));
		$package_deposit = absint(package_field('package_deposit'));

		if ($package_payment > 0 && $package_deposit > 0) {
			$prefix .= sprintf(
				'%s %d%% %s. ',
				__('Book it with a', 'dynamicpackages'),
				$package_deposit,
				__('deposit', 'dynamicpackages')
			);
		}

		return $prefix . $excerpt;
	}
		



	
	public function modify_term_description(string $description): string
	{
		return is_tax([
			'package_terms_conditions',
			'package_location',
			'package_category',
		]) ? '' : $description;
	}
	
	
	public function details()
	{

		$name = 'dy_details_list';
		$the_id = get_dy_id();
		$cache_key = $name.'_'.$the_id;

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		global $dy_is_archive;
		$is_archive = (isset($dy_is_archive)) ? true : false;
		$start_date = (dy_utilities::start_date()) ? dy_utilities::format_date(dy_utilities::start_date()) : null;
		$end_date = (dy_utilities::end_date()) ? dy_utilities::format_date(dy_utilities::end_date()) : null;
		
		$is_transport = dy_utilities::get_package_type($the_id) === 'transport';
		$is_confirmation_page = is_confirmation_page();
		$is_booking_page = is_booking_page();
		$min_hour = package_field('package_min_hour');
		$max_hour = package_field('package_max_hour');
		$check_in_hour = package_field('package_check_in_hour');
		$start_hour = dy_utilities::start_hour();
		$start_address = package_field('package_start_address');
		$start_address_short = package_field('package_start_address_short');
		$check_in_end_hour = package_field('package_check_in_end_hour');
		$return_address = package_field('package_return_address');
		$return_address_short = package_field('package_return_address_short');

		
		$end_hour = dy_utilities::end_hour();
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
		else if($is_transport && $start_hour && $end_hour && !$is_package_by_hour)
		{
			$is_transport_fixed = true;
			$schedule = sprintf(__('Departure %s - Return %s', 'dynamicpackages'), $start_hour, $end_hour);
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
		$first_hour     = $sa ? $end_hour          : $start_hour;
		$second_hour    = $sa ? $start_hour           : $end_hour;
		$first_checkin  = $sa ? $check_in_end_hour    : $check_in_hour;
		$second_checkin = $sa ? $check_in_hour        : $check_in_end_hour;

		// Build once
		$route = [
			'label_departure'    => [ null,     $label_departure ],
			'start_date'       => [ 'calendar',$start_date ],
			'check_in'           => [ 'clock',   __('Check‑in', 'dynamicpackages') . ' ' . $first_checkin ],
			'start_hour'         => [ 'clock',   __('Departing', 'dynamicpackages') . ' ' . $first_hour ],
			'start_address'      => [ 'location',$first_address ],
			'label_return'       => [ null,     $label_return ],
			'end_date'           => [ 'calendar',$end_date ],
			'check_in_end_hour'  => [ 'clock',   __('Check‑in', 'dynamicpackages') . ' ' . $second_checkin ],
			'end_hour'        => [ 'clock',   __('Returning', 'dynamicpackages') . ' ' . $second_hour ],
			'return_address'     => [ 'location',$second_address ],
		];

		// Merge in one go
		$args = array_merge($args, $route);


		$req = [];
		$show_labels = false;

		if($is_archive || is_page() || is_tax())
		{
			if($schedule) $req[] = 'schedule';
			if($show_max_persons) $req[] = 'max_persons';
			if(dy_utilities::start_hour() && $is_transport_fixed === false) $req[] = 'start_hour';
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
			if(dy_utilities::start_hour()) $req[] = 'start_hour';
			if($start_address) $req[] = 'start_address';

			if($is_transport)
			{
				$req[] = 'label_return';
				$req[] = 'end_date';
				if($end_hour) $req[] = 'end_hour';
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

			if($start_date) $req[] = 'start_date';
			if($check_in_hour) $req[] = 'check_in';
			if(dy_utilities::start_hour()) $req[] = 'start_hour';
			if($start_address) $req[] = 'start_address';

			if(isset($_REQUEST['end_date']))
			{
				if($_REQUEST['end_date'] !== '')
				{
					$req[] = 'label_return';
					$req[] = 'end_date';
					if($end_hour) $req[] = 'end_hour';
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

						if(is_valid_date((string) $coupons[$x][2]))
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

	public function count_child($the_id = null) : int {
		if(empty($the_id))
		{
			global $post;
			$the_id = $post instanceof WP_Post ? $post->ID : 0;
		}

		if(empty($the_id))
		{
			return 0;
		}

		$cache_key = 'dy_count_child_' . $the_id;

		if(array_key_exists($cache_key, self::$cache))
		{
			return self::$cache[$cache_key];
		}

		$pages = get_pages(array('child_of' => $the_id, 'post_type' => 'packages'));

		return self::$cache[$cache_key] = is_array($pages) ? count($pages) : 0;
	}

	public function similar_packages_link(): void
	{
		global $post;

		if(!dy_validators::is_child($post->ID)
		) {
			return;
		}

		$cache_key = 'dy_similar_packages_link_' . $post->ID;

		if(array_key_exists($cache_key, self::$cache)) {
			echo self::$cache[$cache_key];
			return;
		}

		$label = !empty($this->current_language)
			? package_field(
				'package_child_title_' . $this->current_language,
				$post->post_parent
			)
			: '';

		$similar_packages_label = $label !== ''
			? $label
			: __('Similar packages', 'dynamicpackages');

		$output = sprintf(
			'<div class="bottom-20"><a class="pure-button rounded block width-100 borderbox strong" href="%s"><span class="dashicons dashicons-arrow-left"></span>%s %s</a></div>',
			esc_url(get_permalink($post->post_parent)),
			esc_html($this->count_child($post->post_parent)),
			esc_html($similar_packages_label)
		);

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

		if (array_key_exists($cache_key, self::$cache)) {
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
