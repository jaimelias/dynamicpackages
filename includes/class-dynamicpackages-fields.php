<?php

if ( !defined( 'WPINC' ) ) exit;

new Dy_Core_Fields(
    'packages',
    [
        [
            'old' => ['package', 'return', 'hour'],
            'new' => ['package', 'end', 'hour']
        ],
        [
            'old' => ['package', 'package', 'type'],
            'new' => ['package', 'type']
        ],
        [
            'old' => ['package', 'duration', 'max'],
            'new' => ['package', 'max', 'duration']
        ],
    ]
);

function package_field(string $name, null|int $the_id = null): string
{

    static $cache = [];
    $cache_key = $name . '_' . $the_id;

    if(array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    try {
        if ($the_id === null) {
            $the_id = get_dy_id();

            if($the_id === null) {
                $request_uri = secure_server('REQUEST_URI');
                throw new Exception("'the_id' can not be null if 'post' is undefined in package_field(): $name, URL: $request_uri");
            }
        }

        static $week_days = [];
        static $languages = [];

        if(empty($week_days)) {
            $week_days = dy_get_week_days_abbr() ?? [];
        }

        if(empty($languages)) {
            $languages = get_languages() ?? [];
        }

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

        foreach ($week_days as $day) {
            $fields_not_inherited_from_parent[] = "package_week_day_surcharge_$day";
            $fields_not_inherited_from_parent[] = "package_day_$day";
        }

        foreach ($languages as $lang) {
            $fields_not_inherited_from_parent[] = "package_child_title_$lang";
            $fields_not_inherited_from_parent[] = "package_redirect_url_$lang";
        }

        global $post;

        $is_child = $post instanceof WP_Post && $post->post_parent > 0;
        $parent_id = $is_child ? (int) $post->post_parent : 0;

        if ($is_child && !in_array($name, $fields_not_inherited_from_parent, true)) {
            $the_id = $parent_id;
        }

        $this_field = Dy_Core_Fields::get($name, $the_id);

        if(request_has('enable_payment') && $name === 'package_auto_booking') {
            $this_field = '1';
        } elseif(
            request_has('force_availability')
            && is_user_logged_in()
            && current_user_can('edit_post', $the_id)
        ) {
            if($name === 'package_disabled_dates_api') $this_field = '';
            else if($name === 'package_booking_from') $this_field = '0';
            else if($name === 'package_booking_to') $this_field = '365';
            else if(in_array($name, [
                'package_day_mon',
                'package_day_tue',
                'package_day_wed',
                'package_day_thu',
                'package_day_fri',
                'package_day_sat',
                'package_day_sun'
            ], true)) $this_field = '';
        }

        if((int) dy_tx::request_value('route') === 1) {
            if($name === 'package_payment') $this_field = '0';
            if($name === 'package_deposit') $this_field = '';
        }

        return $cache[$cache_key] = $this_field;
    } catch (Throwable $e) {
        write_log(
            [
                'message'   => 'Dy_Core_Fields::get() failed.',
                'exception' => get_class($e),
                'error'     => $e->getMessage()
            ],
            true,
            false,
            'ERROR'
        );

        return $cache[$cache_key] = '';
    }
}
