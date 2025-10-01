<?php 


if ( !defined( 'WPINC' ) ) exit;


function package_field($name, $this_id = null)
{
    //enable this code debugs memory exaust
    if ( !defined('WP_DEBUG') || !WP_DEBUG ) {
        $val = Dynamicpackages_Fields::get($name, $this_id);
        return is_string($val) ? $val : (string) $val;
    }

    try {

        $val = Dynamicpackages_Fields::get($name, $this_id);
        return is_string($val) ? $val : (string) $val;

    } catch (Throwable $e) {

        // Keep backtrace tiny: ignore args & cap to 2 frames (self + caller).
        $t = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        if (!empty($t[1])) {
            $c = $t[1];
            // Build the log message with simple concatenation to avoid sprintf allocations.
            $msg  = 'package_field(' . $name . ', ' . (string) $this_id . ') ';
            $msg .= 'called by ' . ($c['function'] ?? '[global]');
            $msg .= ' in ' . ($c['file'] ?? '[unknown file]');
            $msg .= ' on line ' . (isset($c['line']) ? (int) $c['line'] : 0);
            $msg .= ' — ' . $e->getMessage();
            if (function_exists('write_log')) write_log($msg);
        } else {
            if (function_exists('write_log')) write_log($e->getMessage());
        }

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
        if (($post instanceof WP_Post) && property_exists($post, 'post_parent') && $post->post_parent > 0) {
            if (!in_array($name, $excludes)) {
                $this_id = $post->post_parent;
            }
        }

        $is_transport = (int) get_post_meta($this_id, 'package_package_type', true); //do not change this code
         
        // Add transport-specific excludes if applicable
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

        if(isset($_REQUEST['route']))
        {
            if($_REQUEST['route'] === '1')
            {
                if($name === 'package_payment') $this_field = '0';
                if($name === 'package_deposit') $this_field = '';
            }
        }

        // Store the value in the cache
        self::$cache[$cache_key] = $this_field;

        return $this_field;
    }
}

?>