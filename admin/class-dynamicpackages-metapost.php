<?php

if ( !defined( 'WPINC' ) ) exit;

class Dynamicpackages_Metapost
{

	static $valid_package_types = [0, 1, 2, 3, 4];
	static $post_id = null;

	public function __construct()
	{
		add_action('save_post', array($this, 'package_save'), 10, 3);
	}

	public static function package_save($post_id)
	{
		$post_id = absint($post_id);

		if($post_id === 0) return;
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if(wp_is_post_revision($post_id)) return;
		if(get_post_type($post_id) !== 'packages') return;
		if(!post_has('package_nonce')) return;

		$nonce = secure_post('package_nonce', '', 'sanitize_text_field');

		if(!is_string($nonce) || !wp_verify_nonce($nonce, '_package_nonce')) return;
		if(!current_user_can('edit_post', $post_id)) return;

		self::$post_id = $post_id;
		$languages = get_languages();
		$package_post = get_post($post_id);
		$is_child = (
			$package_post instanceof WP_Post
			&& (int) $package_post->post_parent > 0
		);

		dy_utilities::update_package_date_in_db($post_id);

		self::save_package_type_fields($post_id);

		self::save_simple_fields($post_id);
		self::save_duration_fields($post_id, $is_child);
		self::save_defaulted_numeric_fields($post_id);
		self::save_hot_fields($post_id, $is_child);
		self::save_parent_fields($post_id, $is_child);
		self::save_language_fields($post_id, $languages);
		self::save_week_day_fields($post_id);

		if(post_has('package_package_type'))
		{
			update_post_meta(
				$post_id,
				'package_starting_at',
				absint(dy_utilities::starting_at())
			);
		}
	}

	private static function get_package_type() {

		return self::get_posted_scalar(
			'package_package_type',
			null,
			'absint'
		) ?? absint(package_field('package_package_type', self::$post_id));
	}

	private static function save_simple_fields($post_id)
	{
		$fields = [
			'package_fixed_price'       => 'absint',
			'package_show_pricing'      => 'absint',
			'package_display'           => 'absint',
			'package_schema'            => 'absint',
			'package_trip_code'         => 'sanitize_text_field',
			'package_auto_booking'      => 'absint',
			'package_disabled_num'      => 'absint',
			'package_disabled_dates_api'=> 'esc_url',
			'package_booking_from'      => 'absint',
			'package_badge'             => 'absint',
			'package_badge_color'       => 'sanitize_key',
			'package_increase_persons'  => 'absint',
			'package_payment'           => 'absint',
			'package_num_seasons'       => 'absint',
			'package_by_hour'           => 'absint',
			'package_max_hour'          => 'sanitize_text_field',
			'package_min_hour'          => 'sanitize_text_field',
			'package_check_in_hour'     => 'sanitize_text_field',
			'package_start_hour'        => 'sanitize_text_field',
			'package_start_address'     => 'sanitize_textarea_field',
			'package_check_in_end_hour' => 'sanitize_text_field',
			'package_return_hour'       => 'sanitize_text_field',
			'package_return_address'    => 'sanitize_textarea_field',
			'package_redirect_page'     => 'absint',
			'package_max_coupons'       => 'absint',
		];

		foreach($fields as $key => $sanitizer)
		{
			self::update_posted_meta($post_id, $key, $sanitizer);
		}
	}

	private static function save_package_type_fields($post_id)
	{
		if(!post_has('package_package_type')) return;

		$package_type = self::get_posted_scalar(
			'package_package_type',
			null,
			'absint'
		);

		if($package_type === null) return;

		if(!in_array($package_type, self::$valid_package_types, true)) {
			write_log("Invalid package_type=$package_type detected in Dynamicpackages_Metapost::save_package_type_fields($post_id)");
			return;
		}

		$package_type = (int) $package_type;

		if($package_type === 2)
		{
			update_post_meta($post_id, 'package_length_unit', 2);
		}
		elseif($package_type === 3)
		{
			update_post_meta($post_id, 'package_length_unit', 1);
		}
		elseif(post_has('package_length_unit'))
		{
			$length_unit = self::get_posted_scalar(
				'package_length_unit',
				null,
				'absint'
			);

			if($length_unit !== null)
			{
				update_post_meta($post_id, 'package_length_unit', $length_unit);
			}
		}

		update_post_meta($post_id, 'package_package_type', $package_type);
	}

	private static function save_duration_fields($post_id, $is_child)
	{
		if(!post_has('package_duration') || $is_child) return;

		$duration = self::get_posted_scalar(
			'package_duration',
			null,
			'absint'
		);


		if($duration === null) return;

		$duration = max(1, $duration);

		update_post_meta($post_id, 'package_duration', $duration);

		$package_type = self::get_package_type();

		if(!in_array($package_type, self::$valid_package_types, true)) return;

		if(!in_array($package_type, [1, 2, 3], true))
		{
			delete_post_meta($post_id, 'package_duration_max');
			return;
		}

		if(!post_has('package_duration_max')) return;

		$duration_max = self::get_posted_scalar(
			'package_duration_max',
			null,
			'absint'
		);

		if($duration_max === null) return;

		if(
			intval($duration_max) >= 1
			&& intval($duration) > intval($duration_max)
		)
		{
			$duration_max = intval($duration) + 1;
		}

		update_post_meta($post_id, 'package_duration_max', $duration_max);
	}

	private static function save_defaulted_numeric_fields($post_id)
	{
		if(post_has('package_booking_to'))
		{
			$booking_to = self::get_posted_scalar(
				'package_booking_to',
				0,
				'absint'
			);

			if($booking_to !== null)
			{
				update_post_meta(
					$post_id,
					'package_booking_to',
					$booking_to > 0 ? $booking_to : 365
				);
			}
		}

		foreach(['package_min_persons', 'package_max_persons'] as $key)
		{
			if(!post_has($key)) continue;

			$value = self::get_posted_scalar($key, 0, 'absint');

			if($value !== null)
			{
				update_post_meta($post_id, $key, $value > 0 ? $value : 1);
			}
		}

		if(post_has('package_deposit'))
		{
			$deposit = self::get_posted_scalar(
				'package_deposit',
				0,
				'floatval'
			);

			if($deposit !== null)
			{
				update_post_meta(
					$post_id,
					'package_deposit',
					$deposit > 0 ? $deposit : 25
				);
			}
		}

		foreach(['package_free', 'package_discount'] as $key)
		{
			if(!post_has($key)) continue;

			$value = self::get_posted_scalar($key, 0, 'absint');

			if($value !== null)
			{
				update_post_meta($post_id, $key, $value > 0 ? $value : 0);
			}
		}
	}

	private static function save_hot_fields($post_id, $is_child)
	{
		$fields = [
			'package_disabled_dates'  => 'disabled_dates',
			'package_seasons_chart'   => 'seasons_chart',
			'package_price_chart'     => 'price_chart',
			'package_occupancy_chart' => 'occupancy_chart',
			'package_coupons'         => 'coupons',
		];

		foreach($fields as $key => $container)
		{
			self::update_hot_field($post_id, $key, $container);
		}

		if(!$is_child)
		{
			self::update_hot_field(
				$post_id,
				'package_enabled_dates',
				'enabled_dates'
			);
		}
	}

	private static function save_parent_fields($post_id, $is_child)
	{
		if($is_child) return;

		$fields = [
			'package_enabled_num'         => 'absint',
			'package_start_address_short' => 'sanitize_text_field',
			'package_return_address_short'=> 'sanitize_text_field',
			'package_training_data'       => 'absint',
			'package_one_way_surcharge'   => 'absint',
		];

		foreach($fields as $key => $sanitizer)
		{
			self::update_posted_meta($post_id, $key, $sanitizer);
		}
	}

	private static function save_language_fields($post_id, $languages)
	{
		if(!is_array($languages)) return;

		foreach($languages as $lang)
		{
			self::update_posted_meta(
				$post_id,
				'package_confirmation_message_' . $lang,
				'sanitize_textarea_field'
			);

			self::update_posted_meta(
				$post_id,
				'package_child_title_' . $lang,
				'sanitize_text_field'
			);

			self::update_posted_meta(
				$post_id,
				'package_redirect_url_' . $lang,
				'esc_url'
			);
		}
	}

	private static function save_week_day_fields($post_id)
	{
		$week_days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

		foreach($week_days as $day)
		{
			$day_key = 'package_day_' . $day;

			update_post_meta(
				$post_id,
				$day_key,
				post_has($day_key) ? 1 : ''
			);

			self::update_posted_meta(
				$post_id,
				'package_week_day_surcharge_' . $day,
				'absint'
			);
		}
	}

	private static function update_hot_field($post_id, $key, $container)
	{
		if(!post_has($key)) return;

		$value = self::get_posted_scalar(
			$key,
			null,
			'sanitize_text_field'
		);

		if($value === null) return;

		$decoded = json_decode(
			html_entity_decode(
				(string) $value,
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			),
			true
		);

		if(!is_array($decoded) || !array_key_exists($container, $decoded)) return;

		$encoded = wp_json_encode($decoded);

		if(!is_string($encoded)) return;

		update_post_meta($post_id, $key, $encoded);
	}

	private static function update_posted_meta(
		$post_id,
		$key,
		$sanitizer = 'sanitize_text_field'
	) {
		if(!post_has($key)) return;

		$value = self::get_posted_scalar($key, null, $sanitizer);

		if($value === null) return;

		update_post_meta($post_id, $key, $value);
	}

	private static function get_posted_scalar(
		$key,
		$default = '',
		$sanitizer = 'sanitize_text_field'
	) {
		$value = secure_post($key, $default, $sanitizer);

		return is_scalar($value) ? $value : null;
	}
}
