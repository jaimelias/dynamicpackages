<?php

//class-dynamicpackages-export-post-types.php
class Dynamicpackages_Export_Post_Types{

    public function __construct($version)
    {
        add_filter('dy_export_post_types', array(&$this, 'get_fields'));
    }

    public function get_fields($post)
    {
        $current_language = current_language();
        $redirect_url = package_field('package_redirect_url_' . $current_language);
        
        if($this->filter_by_language() || !empty($redirect_url) || dy_utilities::starting_at() === 0 || dy_validators::has_children())
        {
            $post['exclude'] = true;
            return $post;
        }

        if(dy_validators::is_child())
        {
            $parent_content = get_post_field('post_content', $post['post_parent']);
            $post['post_content'] .= '\n\n---\n\n' . html_to_plain_text(apply_filters('the_content', $parent_content));

            if(empty($post['post_excerpt']))
            {
                $parent_excerpt = get_post_field('post_excerpt', $post['post_parent']);
                $post['post_excerpt'] = $parent_excerpt;
            }
        }


        $post['itinerary'] = $post['post_content'];
        $post['itinerary_summary'] = $post['post_excerpt'];
        $post['booking_links'] = $post['links'];

        unset($post['links']);
        unset($post['date']);
        unset($post['modified']);
        unset($post['author']);
        unset($post['post_parent']);
        unset($post['type']);
        unset($post['post_content']);
        unset($post['post_excerpt']);

        $package_type = intval(package_field('package_package_type'));
        $duration_unit = intval(package_field('package_length_unit'));
        $min_duration = intval(package_field('package_duration'));
		$min_hour = intval(package_field('package_min_hour'));
		$max_hour = intval(package_field('package_max_hour'));
		$auto_booking = intval(package_field('package_auto_booking'));
		$payment_type = intval(package_field('package_payment'));
		$deposit = intval(package_field('package_deposit'));


        $package = [
            'max_capacity_per_booking' => package_field('package_max_persons'),
            'duration' => dy_utilities::show_duration(true),
            'check_in_hour' => package_field('package_check_in_hour'),
            'start_hour' => dy_utilities::hour(),
            'start_address' => package_field('package_start_address'),
            'package_type' => $this->package_type_label($package_type, $duration_unit)
        ];

        if($min_hour && $max_hour)
        {
            $package['booking_schedule'] = $min_hour . ' - '. $max_hour;
        }
    
        if (dy_validators::package_type_transport()) {
            $package += [
                'return_hour' => package_field('package_return_hour'),
                'return_check_in_hour' => package_field('package_check_in_end_hour'),
                'return_address' => package_field('package_return_address'),
            ];
        }
    
        $package['rates'] = [];
    
        $package_free = intval(package_field('package_free'));
        $package_discount = intval(package_field('package_discount'));
    
        if ($package_free > 0) {
            $package['rates']['free_children_until_age'] = $package_free;
        }
    
        $children_key_prefix = $package_free > 0 
            ? "children_from_" . ($package_free + 1) . "_up_to_" . $package_discount . "_years_old" 
            : "children_up_to_" . $package_discount . "_years_old";
        
        
        
       //base prices
        $price_chart = dy_utilities::get_hot_chart('package_price_chart');
        $price_key_name = $this->fixed_price_key_name($package_type, $duration_unit);
        $parsed_price_chart = $this->parse_price_chart($price_chart, 'price_chart', $children_key_prefix);

        if($package_type === 4)
        {
            $parsed_price_chart = $this->parse_transport_prices($this->parse_price_chart($price_chart, 'price_chart', $children_key_prefix));
            $package['rates']['prices_per_person_round_trip'] = $this->parse_transport_prices($this->parse_price_chart($price_chart, 'price_chart', $children_key_prefix), true);
        }

        $package['rates'][$price_key_name] = $parsed_price_chart;

        if($package_type === 1)
        {
            
            $occupancy_chart = dy_utilities::get_hot_chart('package_occupancy_chart');
            $occupancy_price_key_name = $this->occupancy_price_key_name($package_type, $duration_unit);

            $package['rates']['seasons_'.$occupancy_price_key_name] = [
                'season_chart_0' => [
                    'is_default_season' => true,
                    'name' => '',
                    'date_from' => '',
                    'date_to' => '',
                    'min_duration' => $min_duration .' '. $this->occupancy_duration_label($package_type, $duration_unit),
                    'prices' => $this->parse_price_chart($occupancy_chart, 'occupancy_chart', $children_key_prefix)
                ]
            ];


            $seasons_chart = dy_utilities::get_hot_chart('package_seasons_chart');
            $rates_by_season = $this->get_seasons_rates($seasons_chart, $children_key_prefix, $package_type, $duration_unit);

            $package['rates']['seasons_'.$occupancy_price_key_name] = array_merge(
                $package['rates']['seasons_'.$occupancy_price_key_name],
                $rates_by_season
            );
        }
        
        $surcharges = $this->get_surcharges($package_type);

        if(!empty($surcharges))
        {
            $package = array_merge($package, $surcharges);
        }



        $package['web_checkout'] = ($auto_booking === 1) ? 'available' : 'web not available';
        

        if($auto_booking)
        {
            $package['payment_type'] = ($payment_type === 1 && $deposit > 0) ? $deposit . '% deposit': 'full payment';
        }
        
    
        return array_merge($post, $package);
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

            $chart = dy_utilities::get_hot_chart('package_occupancy_chart');
            $season['prices'] = $this->parse_price_chart($chart, 'occupancy_chart'.$season_id, $children_key_prefix);
            $output[$season_id] = $season;
        }
        
        return $output;
    }

    public function get_surcharges($package_type)
    {
        $output = [];
        $week_days = dy_utilities::get_week_days_abbr();
        $week_day_surcharges = [];
        

        for($x = 0; $x < count($week_days); $x++)
        {
            $surcharge = intval(package_field('package_week_day_surcharge_'.$week_days[$x]));

            if($surcharge > 0)
            {
                $week_day_surcharges[$week_days[$x]] = $surcharge;
            }
        }

        if(!empty($week_day_surcharges))
        {
            $output['percent_surcharges_by_weekday'] = $week_day_surcharges;
        }


        if($package_type === 4)
        {
            $one_way_surcharges = package_field('package_one_way_surcharge');
        }

        return $output;
    }

    public function parse_price_chart($price_chart, $price_chart_key, $children_key_prefix)
    {
        $output = [];

        if (is_array($price_chart) && isset($price_chart[$price_chart_key])) {
            foreach ($price_chart[$price_chart_key] as $index => $price_row) {
                $adult_key = ($index + 1) . '_adult';
                $child_key = ($index + 1) . '_child';
    
                if (!empty($price_row[0])) {
                    $output['adults'][$adult_key] = $price_row[0];
                }
    
                if (!empty($price_row[1])) {
                    $output[$children_key_prefix][$child_key] = $price_row[1];
                }
            }
        }

        return $output;
    }

    public function package_type_label($package_type, $duration_unit)
    {
        $output = '';

        if($package_type === 0)
        {
            $output = 'One Day Trip';
        }
        else if($package_type === 1)
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
        if($package_type === 2)
        {
            $output = 'Multi-day Rental';
        }
        if($package_type === 3)
        {
            $output = 'Multi-hour Rental';
        }
        if($package_type === 4)
        {
            $output = 'Transport';
        }

		return $output;      
    }
    

    public function fixed_price_key_name($package_type, $duration_unit)
    {
        $output = 'fixed_price_per_person';

        if($package_type === 2)
        {
            $output = 'rental_price_per_person_per_day';
        }
        if($package_type === 3)
        {
            $output = 'rental_price_per_person_per_hour';
        }
        if($package_type === 4)
        {
            $output = 'price_per_person_one_way';
        }

        return $output;
    }

	public function occupancy_price_key_name($package_type, $duration_unit)
	{
        $output = '';

        if($package_type === 1)
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

        if($package_type === 1)
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

    public function parse_transport_prices($prices_chart, $is_round_trip = false) {

        $surcharge = intval(package_field('package_one_way_surcharge'));

        foreach ($prices_chart as $category => &$subCategory) {
            foreach ($subCategory as $key => &$price) {

                if($is_round_trip)
                {
                    $price *= 2;
                }
                if($surcharge > 0)
                {
                    $price += ($surcharge / 100) * $price;
                }
            }
        }
        return $prices_chart;
    }


    public function filter_by_language()
    {
        $current_language = current_language();

        if(isset($_REQUEST['language']))
        {
            if(!empty($_REQUEST['language']))
            {
                $languages = get_languages();

                if(in_array($_REQUEST['language'],  $languages))
                {
                    if($current_language === $_REQUEST['language'])
                    {
                        return true;
                    }
                }
                else
                {
                    throw new Error('invalid language in Dynamicpackages_Export_Post_Types::get_fields');
                }
            }
        }
    }

}

?>