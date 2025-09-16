<?php

#[\AllowDynamicProperties]
class Dynamicpackages_Export_Post_Types{

    private static $cache = [];

    public function __construct($version)
    {
        add_action('wp', array(&$this, 'export_single_file'));
        add_filter('wp_headers', array(&$this, 'single_file_headers'), 999);
        add_action('rest_api_init', array(&$this, 'rest_api_init'));

        $this->alt_formats = ['text', 'json', 'html', 'markdown'];

        $this->all_content_types = [
            'text' => 'text/plain; charset=UTF-8',
            'html' => 'text/html; charset=UTF-8',
            'markdown' => 'text/markdown; charset=UTF-8',
            'json' => 'application/json',
        ];
        $this->all_extensions = [
            'text' => 'txt',
            'json' => 'json',
            'html' => 'html',
            'markdown' => 'md',
        ];
        
        $this->format = 'text';
        $this->extension = 'txt';
        $this->content_type = $this->all_content_types[$this->format];
        
    }

    public function rest_api_init() {

        // Register the new endpoint for exporting post types
        register_rest_route('dy-core', 'training-data/packages', array(
            'methods' => 'GET',
            'callback' => array(&$this, 'query_training_data'),
            'permission_callback' => '__return_true'
        ));
    }

    public function  single_file_headers($headers)
    {
        if(is_singular('packages') && isset($_GET['training-data'])) {

            if(isset($_GET['format']) && in_array($_GET['format'],  $this->alt_formats)) {
                $this->format = sanitize_text_field($_GET['format']);
                $this->content_type = $this->all_content_types[$this->format];
                $this->extension = $this->all_extensions[$this->format];
            }

            $headers['Content-Type'] = $this->content_type;
        }

        return $headers;
    }

    public function export_single_file() {
        if(is_singular('packages') && isset($_GET['training-data'])) {
             global $post;
            exit($this->get_training_content($post));
        }
    }

    public function query_training_data() {

        if(isset($_GET['format']) && in_array($_GET['format'],  $this->alt_formats)) {
            $this->format = sanitize_text_field($_GET['format']);
            $this->content_type = $this->all_content_types[$this->format];
            $this->extension = $this->all_extensions[$this->format];
        }

        $default_language = (string) default_language();
        $languages = (array) get_languages();

        $filter_lang = (string) ( isset($_GET['lang']) &&  in_array(sanitize_text_field($_GET['lang']), $languages)) 
                        ? sanitize_text_field($_GET['lang'])
                        : default_language();


        
        $args = array(
            'post_type'      => 'packages',
            'posts_per_page' => -1,
            'lang' => $filter_lang,
            'meta_query'     => array(
                array(
                    'key'     => 'package_training_data',
                    'value'   => '1',
                    'compare' => '='
                ),
            ),
        );

        $query = new WP_Query($args);
        $data = [];


        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post();

                $file_data = (object) [];
                $file_data->file_name = sanitize_title($post->post_title) . '-' . $post->ID . '.txt';
                $file_data->content = $this->get_training_content($post);

                $data[] = $file_data;
            }

            wp_reset_postdata();
        }

        // --- Write to temp, zip, download, cleanup ---
        if ( empty( $data ) ) {
            wp_die( 'No files to export.' );
        }

        if ( ! class_exists( 'ZipArchive' ) ) {
            wp_die( 'PHP ZipArchive is not available on this server.' );
        }

        // Create a unique temp directory
        $tmp_dir = trailingslashit( get_temp_dir() ) . 'training-export-' . wp_generate_uuid4();
        if ( ! wp_mkdir_p( $tmp_dir ) ) {
            wp_die( 'Could not create temp directory.' );
        }

        // Helper: normalize file name to desired extension while preserving subdirs
        $normalize_filename = static function( $original, $ext ) {
            $original = ltrim((string) $original, '/'); // avoid absolute paths
            $dir  = dirname($original);
            $base = pathinfo($original, PATHINFO_FILENAME);
            $base = sanitize_file_name($base);
            $rel  = ('.' === $dir) ? $base : trailingslashit($dir) . $base;
            return $rel . '.' . $ext;
        };

        // Helper: transform content per format
        $transform_content = static function( $content, $format ) {
            switch ($format) {
                case 'json':
                    // If already JSON-looking, keep as-is; otherwise encode
                    if (is_array($content) || is_object($content)) {
                        return json_encode($content, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                    }
                    $str = (string) $content;
                    $trim = ltrim($str);
                    if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                        return $str; // assume valid JSON provided
                    }
                    // Encode plain text as a JSON string
                    return json_encode($str, JSON_UNESCAPED_UNICODE);

                case 'html':
                    $str = (string) $content;
                    if (stripos($str, '<html') !== false) {
                        return $str; // already a full HTML doc
                    }
                    // Wrap fragment in a minimal HTML document
                    return "<!doctype html>\n<html lang=\"en\">\n<head>\n<meta charset=\"utf-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>Export</title>\n</head>\n<body>\n" . $str . "\n</body>\n</html>\n";

                case 'text':
                default:
                    return (string) $content;
            }
        };

        // Write each file with normalized extension and transformed content
        foreach ( $data as $f ) {
            // Compute normalized path inside tmp dir
            $rel = $normalize_filename( $f->file_name ?? 'export', $this->extension );
            $path = $tmp_dir . '/' . $rel;

            // Ensure parent dir exists
            if ( ! is_dir( dirname( $path ) ) ) {
                wp_mkdir_p( dirname( $path ) );
            }

            // Transform content per selected format
            $payload = $transform_content( $f->content ?? '', $format );

            file_put_contents( $path, $payload );
        }

        // Create ZIP
        $zip_path = $tmp_dir . '.zip';
        $zip = new ZipArchive();
        if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
            // Clean temp files before bailing
            foreach ( glob( $tmp_dir . '/*' ) as $p ) { @unlink( $p ); }
            @rmdir( $tmp_dir );
            wp_die( 'Could not create ZIP file.' );
        }

        // Add files to ZIP (preserve subdirs)
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmp_dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ( $iterator as $file ) {
            if ( $file->isFile() ) {
                $local = ltrim(str_replace($tmp_dir, '', $file->getPathname()), '/');
                $zip->addFile( $file->getPathname(), $local );
            }
        }
        $zip->close();

        // Stream ZIP to browser
        if ( file_exists( $zip_path ) ) {
            if ( ob_get_length() ) { ob_end_clean(); }

            nocache_headers();
            header( 'Content-Type: application/zip' );
            header( 'Content-Disposition: attachment; filename="' . basename( $zip_path ) . '"' );
            header( 'Content-Length: ' . filesize( $zip_path ) );
            header( 'X-Robots-Tag: noindex, nofollow', true );

            flush();
            readfile( $zip_path );
        }

        // Cleanup temp files/dirs
        foreach ( glob( $tmp_dir . '/**/*', GLOB_BRACE ) as $p ) { @unlink( $p ); }
        @rmdir( $tmp_dir );
        @unlink( $zip_path );
        exit;

    }

    public function get_training_content($post) {

        $output = '';
        $training_obj = $this->get_training_obj($post);

        if($this->format === 'json') return json_encode($training_obj);

        $service_name = $training_obj->service_name;
        unset($training_obj->service_name);

        if(in_array($this->format, ['text', 'markdown'])) {
            $output .= "# {$service_name}\n";
            $output .= concatenate_object_to_text($training_obj, "* ", "- ", "\n");
        }
        else if($this->format === 'html') {
            $output .= '<!DOCTYPE html><html><head><title>'.esc_html($service_name).'</title></head><body>';
            $output .= '<h1>'.esc_html($service_name).'</h1>';
            $output .= concatenate_object_to_html($training_obj);
            $output .= "</body></html>";
        }

        return $output;
    }

    public function get_training_obj($post)
    {
        $current_language = current_language();

        $redirect_url = package_field('package_redirect_url_' . $current_language);

        if(!empty($redirect_url)) {
            return (object) [];
        }

        global $polylang;

        $service_description = dy_format_blocks($post->post_content, 'text');
        $languages = (array) get_languages();
        $default_language = (string) default_language();
        $package_type = (string) dy_utilities::get_package_type($post->ID);
        $duration_unit = (int) package_field('package_length_unit');
        $duration_value_label = strtolower((string) dy_utilities::show_duration(false));
        $duration_restrictions = strtolower((string) dy_utilities::show_duration(true));
        $duration_max = (int) package_field('package_duration_max');
        $by_hour = (int) package_field('package_by_hour');
		$min_hour = package_field('package_min_hour');
		$max_hour = package_field('package_max_hour');
		$auto_booking = (int) package_field('package_auto_booking');
		$payment_type = (int) package_field('package_payment');
		$deposit = (int) package_field('package_deposit');
        $check_in_hour = (string) package_field('package_check_in_hour');
        $start_address = (string) package_field('package_start_address');
        $start_address_short = (string) package_field('package_start_address_short');
        $return_hour = (string) package_field('package_return_hour');
        $return_check_in_hour = (string) package_field('package_check_in_end_hour');
        $return_address = (string) package_field('package_return_address');
        $return_address_short = (string) package_field('package_return_address_short');

        $min_persons = (int) package_field('package_min_persons');
        $max_persons = (int) package_field('package_max_persons');

        $included = (string) dy_utilities::implode_taxo_names('package_included');
        $not_included = (string) dy_utilities::implode_taxo_names('package_not_included');
        $categories = (string) dy_utilities::implode_taxo_names('package_category');
        $is_transport = $package_type === 'transport';
        $start_time = dy_utilities::hour();
        $starting_at = (float) dy_utilities::starting_at();
        $price_display_format = strtolower(apply_filters('dy_price_type', null));
        $starting_at_display = currency_symbol() . $starting_at . ' ' . currency_name() . ' ' .$price_display_format;

        $hash = sha1((string) $post->ID . $_SERVER['HTTP_HOST']);
        $service_id = strtoupper(substr($hash, 0, 12));

        $is_web_checkout_enabled = ($auto_booking === 1 && $starting_at > 0 );
        
        $package = (object) [
            'service_id' => $service_id,
            'service_name' => $this->clean_title_string($post->post_title),
            'service_type' => $package_type,
            'service_min_persons_per_booking' => $min_persons,
            'service_max_persons_per_booking' => $max_persons,
            'service_duration' => $duration_value_label,
            'service_starting_at_price' => $starting_at_display,
            'service_rates' => [],
            'service_web_checkout' => ($is_web_checkout_enabled) ? 'available' : 'not available',
            'service_links_by_language' => [],
            'service_name_translations' => [],
            'service_enabled_days_of_the_week' => dy_utilities::enabled_days(true),
            'service_hidden_rules' => []
            //'service_description' => "\n\n" . $service_description
        ];

        $fixed_price = (int) package_field('package_fixed_price');

        if($starting_at <= 0) {
            unset($package->service_starting_at_price);
        }
        else {
            if($fixed_price === 1)  {
                $package->service_hidden_rules[] = sprintf('Never show the prices per person directly to the client. Instead show the starting at price (%s) or the calculated total from {SERVICE_RATES}.', $starting_at_display);
                $package->service_hidden_rules[] = "Always disclose the max capacity ({$max_persons} persons) of this service together with the starting at price.";
            } else {
                $package->service_hidden_rules[] = 'Always show the prices per person to the client.';
                $package->service_hidden_rules[] = 'Always label prices as per person.';
                $package->service_hidden_rules[] = 'Never disclose the maximum capacity of passengers.';
            }

        }

        if(in_array($package_type, ['multi-day', 'rental-per-day', 'rental-per-hour'])) {

            $package->service_duration_restrictions = ($duration_max === 0) ? "{$duration_restrictions} minimum" : $duration_restrictions;
        }

        if(!empty($categories))
        {
            $package->service_categories = $categories;
        }
        if(!empty($included))
        {
            $package->service_included = $included;
        }
        if(!empty($not_included))
        {
            $package->service_not_included = $not_included;
        }

        if ($is_transport) {

            $package->service_hidden_rules[] = 'If the client does not specify whether the transport is one-way or round trip, show prices for both (one-way and round-trip).';
            $package->service_hidden_rules[] = 'If the client requests one-way transport, show only one-way prices.';
            $package->service_hidden_rules[] = 'If the client requests round trip transport, show only round-trip prices.';
            $package->service_hidden_rules[] = 'Always label prices clearly as one-way or round trip.';



            $package->routes =  [];

            $origin_route = (object) array(
                'route_origin' => $start_address_short ?? $start_address,
                'route_destination' => $return_address_short ?? $return_address,
                'route_check_in_time' => $start_time,
                'route_departure_time' => $check_in_hour,
                'route_origin_address' => $start_address,
                'route_destination_address' => $return_address,
                'route_one_way_duration' => $duration_value_label
            );
            
            $destination_route =  (object) array(
                'route_origin' => $return_address_short ?? $return_address,
                'route_destination' => $start_address_short ?? $start_address,
                'route_check_in_time' => $return_check_in_hour,
                'route_departure_time' => $return_hour,
                'route_origin_address' => $return_address,
                'route_destination_address' => $start_address,
                'route_one_way_duration' => $duration_value_label
            );

             if($by_hour === 1 && !empty($min_hour) && !empty($max_hour))
             {
                unset($origin_route->route_check_in_time);
                unset($origin_route->route_departure_time);
                unset($destination_route->route_check_in_time);
                unset($destination_route->route_departure_time);

                $hours_range = "{$min_hour} - {$max_hour}";

                $origin_route->route_reservation_hours = $hours_range;
                $destination_route->route_reservation_hours = $hours_range;
             }

             $package->routes['route_1'] = $origin_route;
             $package->routes['route_2'] = $destination_route;

        } else {
            if(!empty($start_time)) {
                $package->service_start_time = $start_time;
            }
            if(!empty($check_in_hour))
            {
                $package->service_check_in_time = $check_in_hour;
            }
            if(!empty($start_address))
            {
                $package->service_start_address = $start_address;
            }

            if($by_hour === 1 && !empty($min_hour) && !empty($max_hour))
            {
                $package->service_booking_schedule = $min_hour . ' - '. $max_hour;
            }
        }
    
        $package_free = (int) package_field('package_free');
        $package_discount = (int) package_field('package_discount');
    
        if ($package_free > 0) {
            $package->service_rates['free_children_until_age'] = $package_free;
        }
    
        $children_key_prefix = $package_free > 0 
            ? "children_from_" . ($package_free + 1) . "_up_to_" . $package_discount . "_years_old" 
            : "children_up_to_" . $package_discount . "_years_old";
        
       //base prices
        $price_chart = dy_utilities::get_package_hot_chart('package_price_chart');
        $price_key_name = $this->fixed_price_key_name($package_type, $duration_unit);
        $parsed_price_chart = $this->parse_price_chart($price_chart, 'price_chart', $children_key_prefix);

        if(!empty($parsed_price_chart))
        {
            $package->service_rates[$price_key_name] = $parsed_price_chart;
        }
        

        if($is_transport)
        {
            $prices_per_person_round_trip = $this->parse_transport_prices($parsed_price_chart, true);

            if(!empty($prices_per_person_round_trip)) {
                $package->service_rates['prices_per_person_round_trip'] = $prices_per_person_round_trip;
            }
        }

        if($package_type === 'multi-day')
        {
            
            $occupancy_chart = dy_utilities::get_package_hot_chart('package_occupancy_chart');
            $occupancy_price_key_name = $this->occupancy_price_key_name($package_type, $duration_unit);

            $package->service_rates['seasons_'.$occupancy_price_key_name] = [
                'season_chart_0' => [
                    'is_default_season' => true,
                    'name' => '',
                    'date_from' => '',
                    'date_to' => '',
                    'min_duration' => $duration_value_label .' '. $this->occupancy_duration_label($package_type, $duration_unit),
                    'prices' => $this->parse_price_chart($occupancy_chart, 'occupancy_chart', $children_key_prefix)
                ]
            ];


            $seasons_chart = dy_utilities::get_package_hot_chart('package_seasons_chart');
            $rates_by_season = $this->get_seasons_rates($seasons_chart, $children_key_prefix, $package_type, $duration_unit);

            $package->service_rates['seasons_'.$occupancy_price_key_name] = array_merge(
                $package->service_rates['seasons_'.$occupancy_price_key_name],
                $rates_by_season
            );
        }
        
        $surcharges = $this->get_surcharges($package_type);

        if(count(get_object_vars($surcharges)) > 0) {

            

            if(property_exists($surcharges, 'percent_surcharges_by_weekday')) {

                $surcharges_str = json_encode($surcharges->percent_surcharges_by_weekday);

                if($package_type === 'transport') {
                    $package->service_hidden_rules[] = "If the departure or return date fall on a surcharge day of the week {percent_surcharges_by_weekday} ({$surcharges_str}), add the surcharge only for that trip segment.";
                }
                else if(in_array($package_type, ['rental-per-hour', 'one-day'])) {
                    $package->service_hidden_rules[] = "If the booking date fall on a surcharge day of the week {percent_surcharges_by_weekday} ({$surcharges_str}), add the surcharge on that date.";
                }
                else if(in_array($package_type, ['multi-day', 'rental-per-day'])) {
                    $package->service_hidden_rules[] = "Add the surcharge {percent_surcharges_by_weekday} ({$surcharges_str}) to every night that fall on a surcharge day of the week, but skip the check-out day.";
                }
            }


            $package->service_surcharges = $surcharges;
        }

        if($auto_booking)
        {
            $package->service_payment_type = ($payment_type === 1 && $deposit > 0) ? $deposit . '% deposit': 'full payment';
        }


        if(isset($polylang))
        {
            foreach ($languages as $language) {

                $lang_post_id = pll_get_post($post->ID, $language);
                $lang_name = (class_exists('Locale')) ?  \Locale::getDisplayLanguage($language, $default_language) : $language; 
            
                if ($language === $default_language || $lang_post_id > 0) {
                    $package->service_links_by_language[$lang_name] = get_permalink($lang_post_id);
                }

                if($lang_post_id > 0) {
                    $package->service_name_translations[$lang_name] = $this->clean_title_string(get_the_title($lang_post_id));
                }
            }
        }
        else
        {
            $package->service_links_by_language[$current_language] = get_permalink($post->ID);
        }

        //unset $package->service_rates if only free_children_until_age is available
        if(array_key_exists('free_children_until_age', $package->service_rates) && count($package->service_rates) === 1) {
            unset($package->service_rates);
        }
        
        return $package;
    }


    public function get_seasons_rates($seasons_chart, $children_key_prefix, $package_type, $duration_unit)
    {
        if(!is_array($seasons_chart)){
            return [];
        }

        if(!array_key_exists('seasons_chart', $seasons_chart))
        {
            return [];
        }

        $output = [];

        for($x = 0; $x < count($seasons_chart['seasons_chart']); $x++)
        {

            $this_season = $seasons_chart['seasons_chart'][$x];

            $season_id = $this_season[4];

            $season = [
                'is_default_season' => false,
                'name' => $this_season[0],
                'date_from' => $this_season[1],
                'date_to' => $this_season[2],
                'min_duration' => $this_season[3] .' '. $this->occupancy_duration_label($package_type, $duration_unit)
            ];

            $chart = dy_utilities::get_package_hot_chart('package_occupancy_chart');
            $season['prices'] = $this->parse_price_chart($chart, 'occupancy_chart'.$season_id, $children_key_prefix);
            $output[$season_id] = $season;
        }
        
        return $output;
    }

    public function get_surcharges($package_type)
    {
        $output = (object) array();
        $week_days = dy_utilities::get_week_days_abbr();
        $week_day_surcharges = [];
        

        for($x = 0; $x < count($week_days); $x++)
        {
            $surcharge = intval(package_field('package_week_day_surcharge_'.$week_days[$x]));

            if($surcharge > 0)
            {
                $week_day_surcharges[$week_days[$x]] = (string) $surcharge . '%';
            }
        }

        if(!empty($week_day_surcharges))
        {
            $output->percent_surcharges_by_weekday = $week_day_surcharges;
        }


        if($package_type === 'transport')
        {
            $one_way_surcharges = (int) package_field('package_one_way_surcharge');

            if($one_way_surcharges > 0)
            {
                $output->one_way_surcharge = (string) $one_way_surcharges . '% surcharge if this service if booked only one-way.';
            }
        }

        return $output;
    }

    public function parse_price_chart($price_chart, $price_chart_key, $children_key_prefix)
    {
        $output = [];

        if (!is_array($price_chart) || !isset($price_chart[$price_chart_key])) {
            return $output;
        }

        $rows = $price_chart[$price_chart_key];

        // Iterate only if rows are an array to avoid warnings on invalid types
        if (is_array($rows)) {
            foreach ($rows as $i => $price_row) {
                $n = $i + 1;

                if (!empty($price_row[0])) { // keep empty() to skip "0" like original
                    $output['adults']["{$n}_adult"] = $price_row[0];
                }

                if (!empty($price_row[1])) {
                    $output[$children_key_prefix]["{$n}_child"] = $price_row[1];
                }
            }
        }

        // Collapse buckets if first == last (same logic, minimal overhead)
        foreach (['adults', $children_key_prefix] as $bucketKey) {
            if (isset($output[$bucketKey]) && !empty($output[$bucketKey]) && is_array($output[$bucketKey])) {
                $bucket = &$output[$bucketKey];
                $first  = reset($bucket);
                $last   = end($bucket);

                if (is_numeric($first) && is_numeric($last) && (float)$first == (float)$last) {
                    $output[$bucketKey] = 0 + $first; // cast to int/float without changing value
                }
            }
        }

        // Wrap numeric leaves with money()
        $wrapMoneyDeep = function ($val) use (&$wrapMoneyDeep) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $val[$k] = $wrapMoneyDeep($v);
                }
                return $val;
            }
            return is_numeric($val) ? (string) (currency_symbol() . money(0 + $val) . ' ' . currency_name()) : $val;
        };

        return $wrapMoneyDeep($output);
    }

    public function parse_transport_prices($prices_chart, $is_round_trip = false)
    {
        // $prices_chart can now have category values that are either arrays OR scalars.
        $surcharge = intval(package_field('package_one_way_surcharge')); // percent

        // Helper to apply round trip and surcharge safely on any numeric-ish value
        $applyPricing = function ($value) use ($is_round_trip, $surcharge) {
            if (!is_numeric($value)) {
                return $value; // leave non-numeric untouched
            }

            $price = floatval($value);

            if ($is_round_trip) {
                $price *= 2;
            }
            if ($surcharge > 0) {
                $price += ($surcharge / 100) * $price;
            }

            return $price;
        };

        // Compute first (keep math on numbers), then wrap numerics with money()
        if (!is_array($prices_chart)) {
            $result = $applyPricing($prices_chart);
        } else {
            foreach ($prices_chart as $category => &$sub) {
                if (is_array($sub)) {
                    foreach ($sub as &$price) {
                        $price = $applyPricing($price);
                    }
                    unset($price); // break reference
                } else {
                    $sub = $applyPricing($sub);
                }
            }
            unset($sub); // break reference
            $result = $prices_chart;
        }

        // Wrap numeric leaves with money()
        $wrapMoneyDeep = function ($val) use (&$wrapMoneyDeep) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $val[$k] = $wrapMoneyDeep($v);
                }
                return $val;
            }
            return is_numeric($val) ?  (string) (currency_symbol().money(0 + $val) . ' ' . currency_name())  : $val;
        };

        return $wrapMoneyDeep($result);
    }


    public function package_type_label($package_type, $duration_unit)
    {
        $output = '';

        if($package_type === 'one-day')
        {
            $output = 'One Day Trip';
        }
        else if($package_type === 'multi-day')
        {
            if($duration_unit === 2)
            {
                $output = 'Multi-day Trip: day based';
            }
            else if($duration_unit === 3)
            {
                $output = 'Multi-day Trip: night based';
            }
            else if($duration_unit === 4)
            {
                $output = 'Multi-day Trip: week based';
            }
        }
        if($package_type === 'rental-per-day')
        {
            $output = 'Multi-day Rental';
        }
        if($package_type === 'rental-per-hour')
        {
            $output = 'Multi-hour Rental';
        }
        if($package_type === 'transport')
        {
            $output = 'Transport';
        }

		return $output;      
    }
    

    public function fixed_price_key_name($package_type, $duration_unit)
    {
        $output = 'fixed_price_per_person';

        if($package_type === 'rental-per-day')
        {
            $output = 'rental_price_per_person_per_day';
        }
        if($package_type === 'rental-per-hour')
        {
            $output = 'rental_price_per_person_per_hour';
        }
        if($package_type === 'transport')
        {
            $output = 'price_per_person_one_way';
        }

        return $output;
    }

	public function occupancy_price_key_name($package_type, $duration_unit)
	{
        $output = '';

        if($package_type === 'multi-day')
        {
            if($duration_unit === 2)
            {
                $output = 'price_per_person_per_day';
            }
            else if($duration_unit === 3)
            {
                $output = 'price_per_person_per_night';
            }
            else if($duration_unit === 4)
            {
                $output = 'price_per_person_per_week';
            }
        }

		return $output;
	}
    
    
    public function occupancy_duration_label($package_type, $duration_unit)
    {
        $output = '';

        if($package_type === 'rental-per-day')
        {
            if($duration_unit === 2)
            {
                $output = 'day';
            }
            else if($duration_unit === 3)
            {
                $output = 'night';
            }
            else if($duration_unit === 4)
            {
                $output = 'week';
            }
        }

		return $output;      
    }


    public function clean_title_string($input) {
        // 1. Decodificar entidades HTML
        $decoded = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Mantener solo letras (incluyendo acentos), números y espacios
        $clean = preg_replace('/[^\p{L}\p{N} ]+/u', '', $decoded);

        // 3. Colapsar espacios múltiples en uno solo y recortar bordes
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        return $clean;
    }

}

?>