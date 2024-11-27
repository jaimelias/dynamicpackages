<?php

//class-dynamicpackages-export-post-types.php
class Dynamicpackages_Export_Post_Types{

    public function __construct($version)
    {
        add_filter('dy_export_post_types', array(&$this, 'get_fields'));
    }


    public function get_fields($post)
    {

        $package_type = intval(package_field('package_package_type'));

        $package = [
            'max_capacity_per_booking' => package_field('package_max_persons'),
            'duration' => dy_utilities::show_duration(true),
            'check_in_hour' => package_field('package_check_in_hour'),
            'start_hour' => dy_utilities::hour(),
            'start_address' => package_field('package_start_address'),
            'package_type' => $this->package_type_label()
        ];
    
        if (dy_validators::package_type_transport()) {
            $package += [
                'return_hour' => package_field('package_return_hour'),
                'return_check_in_hour' => package_field('package_check_in_end_hour'),
                'return_address' => package_field('package_return_address'),
            ];
        }
    
        $package['pricing'] = [
            'starting_at' => dy_utilities::starting_at()
        ];
    
        $package_free = intval(package_field('package_free'));
        $package_discount = intval(package_field('package_discount'));
    
        if ($package_free > 0) {
            $package['pricing']['free_children_until_age'] = $package_free;
        }
    
        $children_key_prefix = $package_free > 0 
            ? "children_from_" . ($package_free + 1) . "_up_to_" . $package_discount . "_years_old" 
            : "children_up_to_" . $package_discount . "_years_old";
        
        
        $price_prefix = ($package_type === 2 || $package_type === 3) ? 'rental_prices_' : 'fixed_prices_';
        $price_suffix = $this->price_suffix();
        

        $price_chart = dy_utilities::get_hot_chat('package_price_chart');


        //base prices
        $package['pricing'][$price_prefix . $price_suffix] = $this->parse_price_chart($price_chart, 'price_chart', $children_key_prefix);


        $surcharges = $this->get_surcharges($package_type);

        if(!empty($surcharges))
        {
            $package = array_merge($package, $surcharges);
        }

        $post['package'] = $package;
    
        return $post;
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

        //$one_way_surcharges = package_field('package_one_way_surcharge');

        return $output;
    }

    public function parse_price_chart($price_chart, $price_chart_key, $children_key_prefix)
    {
        $output = [];

        if (is_array($price_chart) && isset($price_chart['price_chart'])) {
            foreach ($price_chart['price_chart'] as $index => $price_row) {
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

    public function package_type_label()
    {
        $package_type = intval(package_field('package_package_type'));
        $duration_unit = intval(package_field('package_length_unit'));
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
    
	public function price_suffix()
	{
        $package_type = intval(package_field('package_package_type'));
        $duration_unit = intval(package_field('package_length_unit'));
        $output = '';


        if($package_type === 0)
        {
            $output = 'per_person';
        }
        else if($package_type === 1)
        {
            if($duration_unit === 2)
            {
                $output = 'per_person_per_day';
            }
            else if($duration_unit === 3)
            {
                $output = 'per_person_per_night';
            }
            else if($duration_unit === 4)
            {
                $output = 'per_person_per_week';
            }
        }
        if($package_type === 2)
        {
            $output = 'per_person_per_day';
        }
        if($package_type === 3)
        {
            $output = 'per_person_per_hour';
        }
        if($package_type === 4)
        {
            $output = 'per_person_one_way';
        }

		return $output;
	}
    
    
}

?>