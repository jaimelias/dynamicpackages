<?php 


if ( !defined( 'WPINC' ) ) exit;


function package_field($name, $this_id = null)
{
    try {

        return Dynamicpackages_Fields::get($name, $this_id);

    } catch (Throwable $e) {

        write_log('Dynamicpackages_Fields::get() - Error: ' . $e->getMessage(), true);

        return '';
    }
}

class Dynamicpackages_Fields
{
    private static $cache = [];

    public static function get($name, $this_id = null)
    {
        global $post;

        // Ensure global $post is available
        if ($this_id === null ) {

            $this_id = get_dy_id();

            if($this_id === null)
            {
                $err_message = "'this_id' can not be null if 'post' is undefined in class 'Dynamicpackages_Fields': $name, URL: " . $_SERVER['REQUEST_URI'];
                throw new Exception($err_message);
            }
        }

        // Fetch week days and languages with fallbacks
        $week_days = dy_utilities::get_week_days_abbr() ?? [];
        $languages = get_languages() ?? [];

        // Define base excluded fields
        $excludes = [
            'package_occupancy_chart',
            'package_price_chart',
            'package_min_persons',
            'package_max_persons',
            'package_disabled_dates',
            'package_disabled_num',
            'package_child_title',
            'package_free',
            'package_discount',
            'package_increase_persons',
            'package_disabled_dates_api',
            'package_redirect_page'
        ];

        // Add day-specific excludes
        foreach ($week_days as $day) {
            $excludes[] = "package_week_day_surcharge_$day";
            $excludes[] = "package_day_$day";
        }

        // Add language-specific excludes
        foreach ($languages as $lang) {
            $excludes[] = "package_child_title_$lang";
            $excludes[] = "package_redirect_url_$lang";
        }

        // Check if the current post has a parent and adjust $this_id
        $is_child = (
            $post instanceof WP_Post
            && property_exists($post, 'post_parent')
            && $post->post_parent > 0
        );

        $parent_id = $is_child ? (int) $post->post_parent : 0;
        $type_id   = $is_child ? $parent_id : $this_id;

        $is_transport = (int) get_post_meta(
            $type_id,
            'package_package_type',
            true
        );

        if ($is_transport === 4) {
            $excludes = array_merge($excludes, [
                'package_check_in_hour',
                'package_start_hour',
                'package_check_in_end_hour',
                'package_return_hour',
                'package_start_address',
                'package_return_address',
            ]);
        }

        if ($is_child && !in_array($name, $excludes, true)) {
            $this_id = $parent_id;
        }

        // Generate a unique cache key
        $cache_key = $name . '_' . $this_id;

        // Use cached value if available
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

        // Retrieve the field value
        $this_field = get_post_meta($this_id, $name, true);

        if(!is_string($this_field))
        {
            write_log(
                "Dynamicpackages_Fields::get() - Warning: Field value for '$name' in post '$this_id' is not a string. Value: " . print_r($this_field, true)
            );
            $this_field = '';
        }


        if(request_has('enable_payment') && $name === 'package_auto_booking')
        {
            $this_field = '1';
        }
        else if(request_has('force_availability'))
        {
            if($name === 'package_disabled_dates_api') $this_field = '';
            else if($name === 'package_booking_from') $this_field = '0';
            else if($name === 'package_booking_to') $this_field = '365';
            else if($name === 'package_day_mon') $this_field = '';
            else if($name === 'package_day_tue') $this_field = '';
            else if($name === 'package_day_wed') $this_field = '';
            else if($name === 'package_day_thu') $this_field = '';
            else if($name === 'package_day_fri') $this_field = '';
            else if($name === 'package_day_sat') $this_field = '';
            else if($name === 'package_day_sun') $this_field = '';
        }

        if(secure_request('route', null, 'absint') === 1)
        {
            if($name === 'package_payment') $this_field = '0';
            if($name === 'package_deposit') $this_field = '';
        }
        

        return self::$cache[$cache_key] = $this_field;
    }
}

?>