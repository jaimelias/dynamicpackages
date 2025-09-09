<?php

//class-dynamicpackages-export-post-types.php
class Dynamicpackages_Export_Post_Types{

    private static $cache = [];

    public function __construct($version)
    {
        add_action('wp', array(&$this, 'export'));
        add_filter('wp_headers', array(&$this, 'wp_headers'), 999);
    }

    public function  wp_headers($headers)
    {
        if(is_singular('packages') && isset($_GET['training-data'])) {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        return $headers;
    }

    public function export() {

        if(is_singular('packages') && isset($_GET['training-data']))
        {
            global $post;

            $training_obj = $this->get_training_obj($post);
            $description = $training_obj->description;

            unset($training_obj->description);
            $top_level_prefix = '# ';
            $output = concatenate_object($training_obj, "# ", "- ", "\n\n");

            $output .= "\n# DESCRIPTION:" . $description;

            exit($output);
        }

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

        if(dy_validators::is_child())
        {
            $parent_content = trim(get_post_field('post_content', $post->post_parent));

            $service_description = (!empty($parent_content)) 
                ? dy_format_blocks($parent_content, 'text') . "\n\n---\n\n" . $service_description
                : $service_description;
        }

        $languages = (array) get_languages();
        $default_language = (string) default_language();
        $package_type = (string) dy_utilities::get_package_type($post->ID);
        $duration_unit = (int) package_field('package_length_unit');
        $min_duration = (int) package_field('package_duration');
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

        $included = (string) dy_utilities::implode_taxo_names('package_included');
        $not_included = (string) dy_utilities::implode_taxo_names('package_not_included');
        $is_transport = $package_type === 'transport';

        $start_time = dy_utilities::hour();

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $domain = $_SERVER['HTTP_HOST'];

        $package = (object) [
            'name' => $post->post_title,
            'type' => $package_type,
            'description' => "\n\n" . $service_description,
            'max_participants_per_booking' => (int) package_field('package_max_persons'),
            'duration' => strtolower(dy_utilities::show_duration(true)),
            'rates' => [],
            'web_checkout' => ($auto_booking === 0 || dy_utilities::starting_at() === 0 ) ? 'not available' : 'available',
            'reservation_links_by_language' => [],
            'enabled_days_of_the_week' => strtolower(dy_utilities::enabled_days()),
            'included' => dy_utilities::implode_taxo_names('package_included'),
            'not_included' => dy_utilities::implode_taxo_names('package_not_included'),
            'protocol' => $protocol,
            'domain' => $domain,
            'protocol' => $protocol,
        ];

        if(!empty($start_time)) {
            if($is_transport) {
                $package->departure_time = $start_time;
            } else {
                $package->start_time = $start_time;
            }
        }

        if(!empty($included))
        {
            $package->included = $included;
        }
        if(!empty($not_included))
        {
            $package->not_included = $not_included;
        }

        if(!empty($check_in_hour))
        {
            if($is_transport) {
                $package->departure_check_in_time = $check_in_hour;
            }
            else {
                $package->check_in_time = $check_in_hour;
            }
        }
        if(!empty($start_address_short) && $is_transport)
        {
            $package->origin = $start_address_short;
        }
        if(!empty($start_address))
        {
            if($is_transport)
            {
                $package->origin_address = $start_address;
            } else {
                $package->start_address = $start_address;
            }
        }

        if($by_hour === 1 && !empty($min_hour) && !empty($max_hour))
        {
            $package->booking_schedule = $min_hour . ' - '. $max_hour;
        }

        if ($package_type === 'transport') {

            if(!empty($return_hour))
            {
                $package->return_time =  $return_hour;
            }

            if(!empty($return_check_in_hour)) {
                $package->return_check_in_time = $return_check_in_hour;
            }

            if(!empty($return_address_short)) {

                $package->destination = $return_address_short;
            }

            if(!empty($return_address)) {

                $package->destination_address = $return_address;
            }
        }
    
        $package_free = (int) package_field('package_free');
        $package_discount = (int) package_field('package_discount');
    
        if ($package_free > 0) {
            $package->rates['free_children_until_age'] = $package_free;
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
            $package->rates[$price_key_name] = $parsed_price_chart;
        }
        

        if($package_type === 'transport')
        {
            $prices_per_person_round_trip = $this->parse_transport_prices($parsed_price_chart, true);

            if(!empty($prices_per_person_round_trip)) {
                $package->rates['prices_per_person_round_trip'] = $prices_per_person_round_trip;
            }
        }

        if($package_type === 'multi-day')
        {
            
            $occupancy_chart = dy_utilities::get_package_hot_chart('package_occupancy_chart');
            $occupancy_price_key_name = $this->occupancy_price_key_name($package_type, $duration_unit);

            $package->rates['seasons_'.$occupancy_price_key_name] = [
                'season_chart_0' => [
                    'is_default_season' => true,
                    'name' => '',
                    'date_from' => '',
                    'date_to' => '',
                    'min_duration' => $min_duration .' '. $this->occupancy_duration_label($package_type, $duration_unit),
                    'prices' => $this->parse_price_chart($occupancy_chart, 'occupancy_chart', $children_key_prefix)
                ]
            ];


            $seasons_chart = dy_utilities::get_package_hot_chart('package_seasons_chart');
            $rates_by_season = $this->get_seasons_rates($seasons_chart, $children_key_prefix, $package_type, $duration_unit);

            $package->rates['seasons_'.$occupancy_price_key_name] = array_merge(
                $package->rates['seasons_'.$occupancy_price_key_name],
                $rates_by_season
            );
        }
        
        $surcharges = $this->get_surcharges($package_type);

        if(count(get_object_vars($surcharges)) > 0) {
            $package->surcharges = $surcharges;
        }

        if($auto_booking)
        {
            $package->payment_type = ($payment_type === 1 && $deposit > 0) ? $deposit . '% deposit': 'full payment';
        }

        if(isset($polylang))
        {
            foreach ($languages as $language) {

                $lang_post_id = pll_get_post($post->ID, $language);
            
                if ($language === $default_language || $lang_post_id > 0) {
                    //$package->reservation_links_by_language[$language] = get_permalink($lang_post_id);
                }
            }
        }
        else
        {
            //$package->reservation_links_by_language[$current_language] = get_permalink($post->ID);
        }

        //unset $package->rates if only free_children_until_age is available

        if(array_key_exists('free_children_until_age', $package->rates) && count($package->rates) === 1) {
            unset($package->rates);
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

        return $output;
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

        // If the whole chart is just a scalar, transform and return.
        if (!is_array($prices_chart)) {
            return $applyPricing($prices_chart);
        }

        // Otherwise walk categories; each category can be an array or a scalar.
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

        return $prices_chart;
    }
}

?>