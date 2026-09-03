<?php 


if ( !defined( 'WPINC' ) ) exit;


function package_field($name, $the_id = null) : string
{
    try {

        return Dynamicpackages_Fields::get($name, $the_id);

    } catch (Throwable $e) {

        write_log('Dynamicpackages_Fields::get() - Error: ' . $e->getMessage(), true);

        return '';
    }
}

class Dynamicpackages_Fields
{
    private static $cache = [];
    private static $week_days = [];
    private static $languages = [];

    public static function get($name, $the_id = null) : string
{
        global $post;

        // Ensure global $post is available
        if (empty($the_id)) {

            $the_id = get_dy_id();

            if($the_id === null)
            {
                $err_message = "'the_id' can not be null if 'post' is undefined in class 'Dynamicpackages_Fields': $name, URL: " . $_SERVER['REQUEST_URI'];
                throw new Exception($err_message);
            }
        }

        // Fetch week days and languages with fallbacks

        if(empty(self::$week_days)) {
            self::$week_days = dy_utilities::get_week_days_abbr() ?? [];
        }
        
        if(empty(self::$languages)) {
            self::$languages = get_languages() ?? [];
        }
        
        // Define base excluded fields
        $fields_not_inherited_from_parent = [
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
        foreach (self::$week_days as $day) {
            $fields_not_inherited_from_parent[] = "package_week_day_surcharge_$day";
            $fields_not_inherited_from_parent[] = "package_day_$day";
        }

        // Add language-specific excludes
        foreach (self::$languages as $lang) {
            $fields_not_inherited_from_parent[] = "package_child_title_$lang";
            $fields_not_inherited_from_parent[] = "package_redirect_url_$lang";
        }

        // Check if the current post has a parent and adjust $the_id
        $is_child = (
            $post instanceof WP_Post
            && property_exists($post, 'post_parent')
            && $post->post_parent > 0
        );

        $parent_id = $is_child ? (int) $post->post_parent : 0;

        if ($is_child && !in_array($name, $fields_not_inherited_from_parent, true)) {
            $the_id = $parent_id;
        }

        // Generate a unique cache key
        $cache_key = $name . '_' . $the_id;

        // Use cached value if available
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

        // Retrieve the field value
        $this_field = get_post_meta($the_id, $name, true);

        if(!is_string($this_field))
        {
            write_log(
                "Dynamicpackages_Fields::get() - Warning: Field value for '$name' in post '$the_id' is not a string. Value: " . print_r($this_field, true)
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