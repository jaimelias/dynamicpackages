<?php 


if ( !defined( 'WPINC' ) ) exit;


function package_field($name, $the_id = null) : string
{
    try {

        return Dynamicpackages_Fields::get($name, $the_id);

    } catch (Throwable $e) {

        write_log(
            [
                'message'   => 'Dynamicpackages_Fields::get() failed.',
                'exception' => get_class($e),
                'error'     => $e->getMessage()
            ],
            true,
            false,
            'ERROR'
        );

        return '';
    }
}

class Dynamicpackages_Fields
{
    private static $cache = [];
    private static $week_days = [];
    private static $languages = [];
    private static $is_valid_migration_arr = false;
    private static $migration_arr = [
        [
            "old" => ['package', 'return', 'hour'],
            "new" => ['package', 'end', 'hour']
        ],
        [
            "old" => ['package', 'package', 'type'],
            "new" => ['package', 'type']
        ],
        [
            "old" => ['package', 'duration', 'max'],
            "new" => ['package', 'max', 'duration']
        ],
    ];
    private static $migrations_in_progress = [];

    private static function get_migration_arr() {

        if(self::$is_valid_migration_arr) {
            return self::$migration_arr;
        }

        $is_sequential_array =  function ($arr) {
            return array_keys($arr) === range(0, count($arr) - 1);
        };

        foreach (self::$migration_arr as $row) {

            if (
                !is_array($row)
                || !array_key_exists('old', $row)
                || !array_key_exists('new', $row)
                || !is_array($row['old'])
                || !is_array($row['new'])
                || empty($row['old'])
                || empty($row['new'])
                || !$is_sequential_array($row['old'])
                || !$is_sequential_array($row['new'])
            ) {
                self::migration_schema_error($row);
            }

            foreach (array_merge($row['old'], $row['new']) as $part) {
                if (!is_string($part) || $part === '') {
                    self::migration_schema_error($row);
                }
            }
        }

        self::$is_valid_migration_arr = true;
        return self::$migration_arr;
    }

    private static function migration_schema_error($row) {

        write_log(
            [
                'message'  => 'Invalid migration array schema.',
                'row_type' => get_debug_type($row),
                'row'      => $row
            ],
            true,
            false,
            'ERROR'
        );

        wp_die(
            'Internal Server Error',
            'Internal Server Error',
            ['response' => 500]
        );
    }
    
    private static function migrate_field($name, $the_id): array
    {
        $this_field = '';
        $is_migrated = false;
        $migration_arr = self::get_migration_arr();

        for ($x = 0; $x < count($migration_arr); $x++) {
            $row = $migration_arr[$x];
            $old = implode("_", $row['old']);
            $new = implode("_", $row['new']);

            if (in_array($name, [$new, $old], true)) {
                $migration_key = $the_id . ':' . $new;

                if (isset(self::$migrations_in_progress[$migration_key])) {
                    $value = get_post_meta($the_id, $new, true);

                    if ($value === '') {
                        $value = get_post_meta($the_id, $old, true);
                    }

                    return [$value, $value !== ''];
                }

                $this_field = get_post_meta($the_id, $new, true);

                if ($this_field !== '') {
                    return [$this_field, true];
                }

                $this_field = get_post_meta($the_id, $old, true);

                if ($this_field !== '') {
                    self::$migrations_in_progress[$migration_key] = true;

                    try {
                        $is_updated = update_post_meta(
                            $the_id,
                            $new,
                            $this_field
                        );

                        if ($is_updated !== false) {
                            delete_post_meta($the_id, $old);
                        }
                    } finally {
                        unset(self::$migrations_in_progress[$migration_key]);
                    }

                    return [$this_field, true];
                }
            }
        }

        return [$this_field, $is_migrated];
    }

    public static function get(string $name, null|int $the_id = null) : string
    {
        global $post;

        // Ensure global $post is available
        if ($the_id === null) {

            $the_id = get_dy_id();

            if($the_id === null)
            {
                $request_uri = secure_server('REQUEST_URI');
                $err_message = "'the_id' can not be null if 'post' is undefined in class 'Dynamicpackages_Fields': $name, URL: " . $request_uri;
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

        [$this_field, $is_migrated] = self::migrate_field($name, $the_id);

        if(!$is_migrated) {
            $this_field = get_post_meta($the_id, $name, true);
        }
        

        if(!is_string($this_field))
        {
            write_log(
                [
                    'message'    => 'Field value is not a string.',
                    'field'      => $name,
                    'post_id'    => $the_id,
                    'value_type' => get_debug_type($this_field)
                ],
                true,
                false,
                'WARNING'
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