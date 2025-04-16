<?php 


if ( !defined( 'WPINC' ) ) exit;

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
        ];

        // Add transport-specific excludes if applicable
        if (dy_validators::package_type_transport()) {
            $excludes = array_merge($excludes, [
                'package_check_in_hour',
                'package_start_hour',
                'package_check_in_end_hour',
                'package_return_hour',
                'package_start_address',
                'package_return_address',
            ]);
        }

        // Add day-specific excludes
        foreach ($week_days as $day) {
            $excludes[] = "package_week_day_surcharge_$day";
            $excludes[] = "package_day_$day";
        }

        // Add language-specific excludes
        foreach ($languages as $lang) {
            $excludes[] = "package_child_title_$lang";
        }

        // Check if the current post has a parent and adjust $this_id
        if (isset($post) && property_exists($post, 'post_parent') && $post->post_parent > 0) {
            if (!in_array($name, $excludes)) {
                $this_id = $post->post_parent;
            }
        }

        // Generate a unique cache key
        $cache_key = $name . '_' . $this_id;

        // Use cached value if available
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        // Retrieve the field value
        $this_field = get_post_meta($this_id, $name, true);

        if(isset($_REQUEST['enable_payment']) && $name === 'package_auto_booking')
        {
            $this_field = '1';
        }

        elseif(isset($_REQUEST['force_availability']))
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


        // Store the value in the cache
        self::$cache[$cache_key] = $this_field;

        return $this_field;
    }

    /**
     * Clear the cache for specific fields or all fields.
     *
     * @param string|null $name Specific field name to clear or null to clear all.
     */
    public static function clearCache($name = null)
    {
        if ($name === null) {
            self::$cache = [];
        } else {
            foreach (self::$cache as $key => $value) {
                if (strpos($key, $name . '_') === 0) {
                    unset(self::$cache[$key]);
                }
            }
        }
    }
}

function package_field($name, $this_id = null)
{
    return Dynamicpackages_Fields::get($name, $this_id);
}


?>