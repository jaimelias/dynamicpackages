<?php

if ( !defined( 'WPINC' ) ) exit;

function is_booking_page()
{
	return dy_validators::is_booking_page();
}

function is_confirmation_page()
{
	return dy_validators::is_confirmation_page();
}

function has_package()
{
	return dy_validators::has_package();
}

function is_post_type_packages($the_id = null) : bool
{
	return dy_validators::is_post_type_packages($the_id);
}


#[AllowDynamicProperties]
class dy_validators
{
	private static $cache = [];

	public static function is_post_type_packages($the_id = null): bool
	{
		if($the_id === null) {
			$the_id = get_dy_id();
		}

		if(!is_numeric($the_id) || (int)$the_id <= 0) {
			return false;
		}

		$post = get_post((int)$the_id);

		return $post instanceof WP_Post
			&& $post->post_type === 'packages';
	}
	
	public static function validate_quote()
	{

		$output = false;
		$the_id = get_dy_id();
		$cache_key = 'dy_validate_quote_' . $the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(!self::validate_the_id($the_id)) {
			write_log('invalid the_id');
			return self::$cache[$cache_key] = false;
		}

		$total        = (float) dy_utilities::total();
		$min_persons  = (int) package_field('package_min_persons', $the_id);
		$max_persons  = (int) package_field('package_max_persons', $the_id);
		$pax_regular  = secure_request('pax_regular', 0, 'absint');
		$pax_discount = secure_request('pax_discount', 0, 'absint');
		$pax_free     = secure_request('pax_free', 0, 'absint');

		// Enforce documented constraints on package config
		if ($min_persons <= 0 || $max_persons <= 0 || $max_persons <= $min_persons) {
			return self::$cache[$cache_key] = false;
		}

		// Enforce documented constraint: pax_regular must be > 0
		if ($pax_regular <= 0) {
			return self::$cache[$cache_key] = false;
		}

		$sum_people = $pax_regular;

		if ($pax_discount > 0) {
			$sum_people += $pax_discount;
		}
		if ($pax_free > 0) {
			$sum_people += $pax_free;
		}

		if (
			$total > 0
			&& $pax_regular >= $min_persons
			&& $sum_people <= $max_persons
		) {
			$output = true;
		}

		self::$cache[$cache_key] = $output;

		return $output;
	}
	
	public static function validate_booking_date($the_id = null)
	{
		$output = false;
		$cache_key = 'dy_validate_booking_date_' . $the_id;
		

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}

		if(get_has('booking_date'))
		{
			$booking_date = dy_utilities::booking_date();
			$min_range = dy_utilities::min_range($the_id);
			$max_range = dy_utilities::max_range($the_id);
						
			if($booking_date)
			{
				if($booking_date >= $min_range && $booking_date <= $max_range)
				{
					$output = true;
				}
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	public static function has_package()
	{

		if($_SERVER['REQUEST_METHOD'] !== 'GET') {
			return false;
		}

		$output = false;
		$cache_key = 'dy_has_package';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(is_singular('packages'))
		{
			$output = true;
		}
		else if(is_tax('package_category') ||is_tax('package_location') || is_post_type_archive('packages'))
		{
			$output = true;
		}
		else if(is_page())
		{
			global $post;
			
			if(is_object($post))
			{
				if(has_shortcode($post->post_content, 'packages'))
				{
					$output = true;
				}					
			}
		}	

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function validate_the_id($the_id = 0) {

		$cache_key = 'validate_the_id_' . $the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if (!is_numeric($the_id) || (int) $the_id <= 0 || (int) $the_id != $the_id) {
			return self::$cache[$cache_key] = false;
		}

		return self::$cache[$cache_key] = true;
	}

	public static function is_booking_page()
	{
		$output = false;
		$cache_key = 'dy_is_booking_page';

		if(
			($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
			|| is_admin()
			|| wp_doing_ajax()
			|| wp_doing_cron()
			|| (defined('REST_REQUEST') && REST_REQUEST)
			|| !is_singular('packages')
		) {
			return false;
		}

		if(array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if (!is_post_type_packages()) {
			return self::$cache[$cache_key] = false;
		}

		$missing_required_get_params = [];
		$required_get_params = [
			'booking_date',
			'pax_regular'
		];

		$count_required_get_params = count($required_get_params);

		for ($x = 0; $x < $count_required_get_params; $x++) {
			if (!get_has($required_get_params[$x])) {
				$missing_required_get_params[] = $required_get_params[$x];
			}
		}
		
		$count_missing_required_get_params = count($missing_required_get_params);
		
		//if all the required_get_params it means that this is a standard package page

		if(
			$count_missing_required_get_params
			=== $count_required_get_params
		) {
			return self::$cache[$cache_key] = false;
		}

		if($count_missing_required_get_params > 0) {
			dy_errors::add(array_map(
				static function($param) {
					return sprintf(
						__(
							'Missing required request parameter: %s.',
							'dynamicpackages'
						),
						$param
					);
				},
				$missing_required_get_params
			));

			return self::$cache[$cache_key] = false;
		}

		$invalid_required_get_params = [];
		$the_id = get_dy_id();

		if (!self::validate_booking_date($the_id)) {
			$invalid_required_get_params[] = 'booking_date';
		}
		if (!self::validate_pax_regular($the_id)) {
			$invalid_required_get_params[] = 'pax_regular';
		}

		if(count($invalid_required_get_params) > 0) {

			dy_errors::add(array_map(
				function($param) {
					return sprintf(__('Invalid request parameter: %s.'), $param);
				},
				$invalid_required_get_params
			));
			
			$output = false;
		} else {
			$output = true;
		}
        
        

		return self::$cache[$cache_key] = $output;
	}	
	
	public static function validate_pax_regular($the_id = 0) {


		$cache_key = 'dy_validate_pax_regular_' . $the_id;

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}

		$pax_regular = secure_request('pax_regular', 0, 'absint');

		if (!is_int($pax_regular) || $pax_regular <= 0) {
			dy_errors::add(__('Invalid param: pax_regular.', 'dynamicpackages'));
			return self::$cache[$cache_key] = false;
		}

		$pax_discount = secure_request('pax_discount', 0, 'absint');
		$pax_free = secure_request('pax_free', 0, 'absint');
		$pax_sum = $pax_regular + $pax_discount + $pax_free;

		$package_min_persons = absint(package_field('package_min_persons'));
		$package_max_persons = absint(package_field('package_max_persons'));
		$package_increase_persons = absint(package_field('package_increase_persons'));
		$hard_max_persons = $package_max_persons + $package_increase_persons;

		if (!is_int($package_min_persons) || $package_min_persons <= 0) {
			dy_errors::add(
				__('No valid package_min_persons configured — treat as invalid rather than auto-passing.', 'dynamicpackages'),
				500
			);
			return self::$cache[$cache_key] = false;
		}

		if (!is_int($package_max_persons) || $package_max_persons <= 0) {
			dy_errors::add(
				__('No valid package_max_persons configured — treat as invalid rather than auto-passing.', 'dynamicpackages'),
				500
			);
			return self::$cache[$cache_key] = false;
		}

		if (!is_int($package_increase_persons) || $package_increase_persons < 0) {
			dy_errors::add(
				__('No valid package_increase_persons configured — treat as invalid rather than auto-passing.', 'dynamicpackages'),
				500
			);
			return self::$cache[$cache_key] = false;
		}

		if($pax_sum < $package_min_persons) {

			dy_errors::add(__('You have not reached the minimum number of participants required for this package.', 'dynamicpackages'));
			return self::$cache[$cache_key] = false;
		}

		if($pax_sum > $hard_max_persons) {

			dy_errors::add(__('You have exceeded the maximum number of participants allowed for this package', 'dynamicpackages'));
			return self::$cache[$cache_key] = false;
		}

		return self::$cache[$cache_key] = true;
	}

	public static function is_confirmation_page()
	{

		if($_SERVER['REQUEST_METHOD'] !== 'POST') {
			return false;
		}

		$output = false;
		$cache_key = 'dy_is_checkout_page';
	
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if (
			is_admin()
			|| wp_doing_ajax()
			|| wp_doing_cron()
			|| (defined('REST_REQUEST') && REST_REQUEST)
		) {
			return self::$cache[$cache_key] = false;
		}

		$the_id = get_dy_id();

		if($the_id === null) {
			return self::$cache[$cache_key] = false;
		}

		if(!empty(secure_post('dy_request')))
		{
			$post = get_post($the_id);

			$has_shortcode = ($post instanceof WP_Post) && has_shortcode( $post->post_content, 'package_contact');

			if(is_post_type_packages() || $has_shortcode) {
				$output = true;
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}

	public static function validate_request()
	{
		$output = false;
		$cache_key = 'dy_validate_request';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(self::is_confirmation_page())
		{

			if(!self::validate_unique_tx_id()) {
				dy_errors::add(
					__('Invalid unique_tx_id.', 'dynamicpackages')
				);

				return self::$cache[$cache_key] = false;
			}

			if(self::validate_contact_details() && self::validate_booking_details())
			{
				if (!validate_turnstile()) {
					return self::$cache[$cache_key] = false;
				}

				$submission_valid = self::validate_submission_rate_limits();
				$post_valid = self::validate_post_id_rate_limits();
				$gateway_valid = self::validate_gateway_rate_limits();

				$output = $submission_valid && $post_valid && $gateway_valid;
			}
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}

	public static function is_white_listed_from_rate_limits() : bool {
		$the_id = secure_post('dy_id', 0, 'absint');

		if($the_id > 0 && current_user_can('edit_post', $the_id)) {
			return true;
		}

		$host = strtolower(
			trim(
				(string) wp_parse_url(get_option('home'), PHP_URL_HOST),
				'[] .'
			)
		);

		return $host === 'localhost';
	}
	
	public static function validate_submission_rate_limits(): bool
	{
		if(self::is_white_listed_from_rate_limits()) {
			return true;
		}

		$ip = get_ip_address();
		$host = strtolower(trim((string) wp_parse_url(get_option('home'), PHP_URL_HOST), '[] .'));


		$cache_key = 'dy_validate_submission_rate_limits';

		if(array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			return self::$cache[$cache_key] = true;
		}

		self::$cache[$cache_key] = false;

		// write_log() mutates $_POST; omit the payload and always restore it.
		$log = static function($details) {
			$submitted = $_POST;

			try {
				$_POST = [];
				write_log($details);
			} finally {
				$_POST = $submitted;
			}
		};

		$reject = static function(
			$reason,
			$http_status = 500
		) use ($log) {
			dy_errors::add(
				__('Unable to validate this submission. Please try again later.', 'dynamicpackages'),
				$http_status
			);

			$log([
				'message' => 'Submission rate limit validation failed',
				'reason'  => $reason,
			]);

			return false;
		};

		if($host === '') {
			return $reject('Invalid configured site hostname', 500);
		}

		$required = ['dy_request', 'dy_id', 'phone', 'country_calling_code', 'email', 'first_name', 'lastname'];

		foreach($required as $param) {
			if(!post_has($param) || secure_post($param) === '') {
				return $reject('Missing or invalid parameter: ' . $param, 400);
			}
		}

		$dy_request = secure_post('dy_request', '', 'sanitize_key');
		$dy_id = secure_post('dy_id', 0, 'absint');
		$calling_code = preg_replace('/\D+/', '', secure_post('country_calling_code'));
		$phone = preg_replace('/\D+/', '', secure_post('phone'));
		$email = strtolower(secure_post('email', '', 'sanitize_email'));
		$normalize_name = static function($name) {
			$name = trim((string) preg_replace('/\s+/u', ' ', $name));
			return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
		};
		$first_name = $normalize_name(secure_post('first_name'));
		$lastname = $normalize_name(secure_post('lastname'));

		if($dy_request === '' || $dy_id <= 0 || $calling_code === '' || $phone === '' || !is_email($email) || $first_name === '' || $lastname === '') {
			return $reject('Invalid normalized submission parameters', 400);
		}

		$phone = '+' . $calling_code . $phone;
		

		if(!filter_var($ip, FILTER_VALIDATE_IP)) {
			return $reject('Unable to determine a valid client IP', 500);
		}

		$subjects = [
			'request_phone' => [$dy_request, $phone],
			'package_phone' => [$dy_id, $phone],
			'request_email' => [$dy_request, $email],
			'package_email' => [$dy_id, $email],
			'ip' => [$ip],
			'request_name' => [$dy_request, $first_name, $lastname],
			'package_name' => [$dy_id, $first_name, $lastname]
		];
		$windows = [
			'd' => [DAY_IN_SECONDS, 10, DAY_IN_SECONDS],
			'h' => [HOUR_IN_SECONDS, 5, 2 * HOUR_IN_SECONDS]
		];
		$blocked_until = 0;
		$ban_ip = false;
		$new_blocks = [];
		$storage_failed = false;
		$salt = wp_salt('auth');

		global $wpdb;

		// A transient-based lock would have the same read/write race as the counters.
		$lock_name = 'dy_rl_' . substr(hash('sha256', $wpdb->prefix . $salt), 0, 56);

		if((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 1)', $lock_name)) !== '1') {
			return $reject('Unable to acquire submission rate limit lock', 503);
		}

		try {
			$now = time();
			$external_cache = wp_using_ext_object_cache();

			if(!$external_cache) {
				wp_cache_delete('notoptions', 'options');
			}

			foreach($subjects as $dimension => $parts) {
				$digest = hash_hmac('sha256', wp_json_encode([$dimension, $parts]), $salt);

				foreach($windows as $period => [$window, $limit, $duration]) {
					$key = 'dy_rl_v1_' . $period . '_' . $digest;

					if(!$external_cache) {
						// Discard options cached before this request acquired the lock.
						wp_cache_delete('_transient_' . $key, 'options');
						wp_cache_delete('_transient_timeout_' . $key, 'options');
					}

					$state = get_transient($key);

					if($state === false) {
						$state = ['attempts' => [], 'blocked_until' => 0];
					}

					if($state['blocked_until'] <= $now) {
						$state['attempts'] = array_values(array_filter(
							$state['attempts'],
							static function($timestamp) use ($now, $window) {
								return $timestamp > $now - $window;
							}
						));
						$state['attempts'][] = $now;
						$state['blocked_until'] = 0;

						if(count($state['attempts']) >= $limit) {
							$state['blocked_until'] = $now + $duration;
							$new_blocks[] = $dimension . ':' . $period;
						}

						$ttl = max($window, $state['blocked_until'] - $now);

						if(!set_transient($key, $state, $ttl)) {
							$storage_failed = true;
						}
					}

					if($state['blocked_until'] > $now) {
						$blocked_until = max($blocked_until, $state['blocked_until']);

						if($period === 'd') {
							$ban_ip = true;
							break;
						}
					}
				}
			}
		} finally {
			$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
		}

		if($new_blocks) {
			$log([
				'message' => 'Submission rate limit reached',
				'limits' => $new_blocks,
				'blocked_until' => $blocked_until
			]);
		}

		if($ban_ip) {
			// Failure to ban remotely must never allow a locally blocked submission.
			cloudflare_ban_ip_address('DynamicPackages submission rate limit: 10 attempts in 24 hours');
		}

		if($storage_failed) {
			return $reject('Unable to persist submission rate limit state', 503);
		}

		if($blocked_until > 0) {
			dy_errors::add(
				__('Too many submissions. Please try again later.', 'dynamicpackages'),
				429
			);
			return false;
		}

		return self::$cache[$cache_key] = true;
	}

	public static function validate_post_id_rate_limits(): bool
	{
		$the_id = secure_post('dy_id', 0, 'absint');

		return $the_id === 0 
			? false 
			: self::validate_rate_limit_bucket('dy_id', $the_id);
	}

	public static function validate_gateway_rate_limits(): bool
	{
		// Gateway IDs are names (for example, paguelo_facil_on), not integers.
		$dy_request = secure_post('dy_request', '', 'sanitize_key');

		return self::validate_rate_limit_bucket('gateway', $dy_request);
	}

	/**
	 * Count each POST once per request, with independent rolling windows.
	 * Longer windows keep counting while a shorter cooldown is active.
	 */
	private static function validate_rate_limit_bucket($scope, $suffix): bool
	{
		if(self::is_white_listed_from_rate_limits() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			return true;
		}

		$cache_key = 'dy_validate_rate_limits_' . $scope . '_' . $suffix;

		if(array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		self::$cache[$cache_key] = false;

		$reject = static function($seconds, $reason = '', $http_status = 429) use ($scope) {
			$seconds = max(1, (int) $seconds);
			$minutes = (int) ceil($seconds / MINUTE_IN_SECONDS);
			$cooldown = $seconds >= MINUTE_IN_SECONDS
				? sprintf(_n('%d minute', '%d minutes', $minutes, 'dynamicpackages'), $minutes)
				: sprintf(_n('%d second', '%d seconds', $seconds, 'dynamicpackages'), $seconds);
			$message = sprintf(
				__('We cannot accept this submission right now. Please try again in %s.', 'dynamicpackages'),
				$cooldown
			);

			dy_errors::add($message, $http_status);

			if($reason !== '') {
				write_log(['message' => 'Rate limit validation failed', 'scope' => $scope, 'reason' => $reason]);
			}

			return false;
		};

		if($suffix === '' || $suffix === 0) {
			return $reject(MINUTE_IN_SECONDS, 'Missing or invalid rate limit identifier', 400);
		}

		// Change thresholds and cooldowns here for both public validators.
		$rules = [
			'30s' => ['window' => 30, 'limit' => 5, 'cooldown' => MINUTE_IN_SECONDS],
			'5m' => ['window' => 5 * MINUTE_IN_SECONDS, 'limit' => 15, 'cooldown' => 10 * MINUTE_IN_SECONDS],
			'1h' => ['window' => HOUR_IN_SECONDS, 'limit' => 30, 'cooldown' => HOUR_IN_SECONDS]
		];
		$blocked_until = 0;
		$new_blocks = [];
		$storage_failed = false;

		global $wpdb;

		// Serialize updates to this bucket so concurrent requests cannot lose attempts.
		$lock_name = 'dy_rl_' . substr(hash('sha256', $wpdb->prefix . $cache_key), 0, 56);

		if((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 1)', $lock_name)) !== '1') {
			return $reject(MINUTE_IN_SECONDS, 'Unable to acquire rate limit lock', 503);
		}

		try {
			$now = time();
			$external_cache = wp_using_ext_object_cache();

			if(!$external_cache) {
				wp_cache_delete('notoptions', 'options');
			}

			foreach($rules as $period => $rule) {
				$key = 'dy_rl_v1_' . $scope . '_' . $period . '_' . $suffix;

				if(!$external_cache) {
					wp_cache_delete('_transient_' . $key, 'options');
					wp_cache_delete('_transient_timeout_' . $key, 'options');
				}

				$state = get_transient($key);

				if($state === false) {
					$state = ['attempts' => [], 'blocked_until' => 0];
				}

				if($state['blocked_until'] <= $now) {
					$state['attempts'] = array_values(array_filter(
						$state['attempts'],
						static function($timestamp) use ($now, $rule) {
							return $timestamp > $now - $rule['window'];
						}
					));
					$state['attempts'][] = $now;
					$state['blocked_until'] = 0;

					if(count($state['attempts']) >= $rule['limit']) {
						$state['blocked_until'] = $now + $rule['cooldown'];
						$new_blocks[] = $period;
					}

					$ttl = max($rule['window'], $state['blocked_until'] - $now);

					if(!set_transient($key, $state, $ttl)) {
						$storage_failed = true;
					}
				}

				$blocked_until = max($blocked_until, $state['blocked_until']);
			}
		} finally {
			$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
		}

		if($new_blocks) {
			write_log([
				'message' => 'Rate limit reached',
				'scope' => $scope,
				'key_suffix' => $suffix,
				'limits' => $new_blocks,
				'blocked_until' => $blocked_until
			]);
		}

		if($storage_failed) {
			return $reject(max(MINUTE_IN_SECONDS, $blocked_until - time()), 'Unable to persist rate limit state', 503);
		}

		if($blocked_until > time()) {
			return $reject($blocked_until - time());
		}

		return self::$cache[$cache_key] = true;
	}

	public static function validate_unique_tx_id() {

		$unique_tx_id = secure_post('unique_tx_id');
		$email = secure_post('email');

		if (!is_string($unique_tx_id) || $unique_tx_id === '') return false;
		if (!is_email($email)) return false;

		$transient_key = 'secret_tx_id_' . $unique_tx_id;
		$secret_tx_id = get_transient( $transient_key );
		$expected_secret_tx_id= hash_hmac('sha256', ($unique_tx_id . $email), wp_salt('auth'));

		return is_string($secret_tx_id) && hash_equals($expected_secret_tx_id, $secret_tx_id);
	}

	public static function validate_contact_details()
	{
		$cache_key = 'dy_validate_contact_details';

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}


		$first_name = secure_post('first_name', null);
		$lastname = secure_post('lastname', null);
		$phone = secure_post('phone', null);
		$country_calling_code = secure_post('country_calling_code', null);
		$email = secure_post('email', null, 'sanitize_email');
		$repeat_email = secure_post('repeat_email', null, 'sanitize_email');
		$inquiry = secure_post('inquiry', null, 'sanitize_textarea_field');
		$invalids = [];

		if(in_array(null, [
			$first_name,
			$lastname,
			$phone,
			$country_calling_code,
			$email,
			$repeat_email
		], true)) {
			$invalids[] = __('Invalid Request.', 'dynamicpackages');
		} else {
			if(!is_email($email)) {
				$invalids[] = __('Invalid email.', 'dynamicpackages');
			}

			if(!is_email($repeat_email)) {
				$invalids[] = __('Invalid repeated email.', 'dynamicpackages');
			}

			if($email !== $repeat_email) {
				$invalids[] = __('Email and repeated email are not equal.', 'dynamicpackages');
			}

			if(empty($first_name)) {
				$invalids[] = __('First name is empty.', 'dynamicpackages');
			}

			if(empty($lastname)) {
				$invalids[] = __('Lastname is empty.', 'dynamicpackages');
			}

			if(empty($phone)) {
				$invalids[] = __('Phone is empty.', 'dynamicpackages');
			}

			if(empty($country_calling_code)) {
				$invalids[] = __('Country Calling Code is empty.', 'dynamicpackages');
			}

			if($inquiry !== null && (empty($inquiry) || self::is_spam($inquiry))) {
				$invalids[] = __('Inquiry is empty.', 'dynamicpackages');
			}
		}

		$output = count($invalids) === 0;

		if(!$output) {
			dy_errors::add($invalids);
		}

		return self::$cache[$cache_key] = $output;
	}
	
	
	public static function validate_terms_conditions()
	{
		$output = true;
		$cache_key = 'dy_validate_terms_conditions';

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$auto_booking = (int) package_field('package_auto_booking');

		if ($auto_booking === 1) {
			$terms = (array) dy_utilities::get_taxonomies('package_terms_conditions');

			if (!empty($terms)) {

				foreach ($terms as $term) {
					$term_name = 'terms_conditions_' . $term->term_id;
					$accepted = secure_post($term_name, false);

					if (filter_var($accepted, FILTER_VALIDATE_BOOLEAN) === false) {
						$output = false;
						break;
					}
				}
				
			}
		}

		if ($output === false) {
			dy_errors::add(__('Please you must accept our Terms & Conditions before booking', 'dynamicpackages'));
		}

		return self::$cache[$cache_key] = $output;
	}
	
	public static function validate_booking_details()
	{
		$cache_key = 'dy_validate_booking_details';		
		

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(secure_post('dy_request') === 'contact') {
			return self::$cache[$cache_key] = true;
		}

		$output = true;
		$invalids = [];
		$booking_hour = secure_post('booking_hour');
		$resolved_hour = dy_utilities::booking_hour();


		if(!is_valid_date(secure_post('booking_date'))) {
			$invalids[] = __('Invalid booking_date.', 'dynamicpackages');
		}

		if($resolved_hour !== '' && !is_valid_time($booking_hour)) {
			$invalids[] = __('Invalid booking_hour.', 'dynamicpackages');
		}

		if(empty(secure_post('duration'))) {
			$invalids[] = __('Invalid duration.', 'dynamicpackages');
		}
		if(empty(secure_post('pax_num', 0, 'absint'))) {
			$invalids[] = __('Invalid pax_num.', 'dynamicpackages');
		}
		if(!self::validate_terms_conditions()) {
			$invalids[] = __('Invalid terms_conditions.', 'dynamicpackages');
		}

		if(count($invalids) > 0) {
			dy_errors::add($invalids);
			$output = false;
		}
        
		return self::$cache[$cache_key] = $output;
	}
	
	public static function has_coupon()
	{
		$cache_key = 'dy_has_coupon';

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		if (absint(package_field('package_max_coupons')) === 0) {
			return self::$cache[$cache_key] = false;
		}

		$coupons = dy_utilities::get_package_hot_chart('package_coupons');
		$first   = $coupons['coupons'][0] ?? null;

		$output = is_array($first) && !empty($first[0]) && !empty($first[1]);

		return self::$cache[$cache_key] = $output;
	}

	public static function validate_coupon()
	{
		$output = false;
		$cache_key = 'dy_validate_coupon';

		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$coupon_code = dy_utilities::normalize_coupon_code(secure_request('coupon_code'));
		
		if(!self::has_coupon() || $coupon_code === null) {
			return self::$cache[$cache_key] = false;
		}

		$coupon_params = dy_utilities::get_active_coupon_params();

		if(!$coupon_params) {
			return self::$cache[$cache_key] = false;
		}

		$stored_coupon_code = $coupon_params->code;
		$discount = $coupon_params->discount;

		if($discount <= 0.0 || $discount > 100.0) {

			return self::$cache[$cache_key] = false;
		}


		$package_type = dy_utilities::get_package_type();

		$duration = absint(dy_utilities::get_min_nights());
		$booking_date = secure_request('booking_date');
		$booking_date_to = date('Y-m-d', strtotime($booking_date . " +$duration days"));
		$booking_dates_range = dy_utilities::get_date_range($booking_date, $booking_date_to, false);
		
		if($stored_coupon_code === $coupon_code)
		{
			$coupon_expiration = $coupon_params->expiration;
			$coupon_min_duration = $coupon_params->min_duration;
			$coupon_max_duration = $coupon_params->max_duration;
			$coupon_bookings_after_expires = $coupon_params->bookings_after_expires;
			$valid_expiration = false;

			if(($duration < $coupon_min_duration && $coupon_min_duration > 0) || ($duration > $coupon_max_duration && $coupon_max_duration > 0) )
			{
				self::$cache[$cache_key] = false;
				return false;
			}
			if(empty($coupon_expiration))
			{
				self::$cache[$cache_key] = true;
				return true;
			}

			//expiration
			$expiration_stamp = new DateTime($coupon_expiration);
			$expiration_stamp->setTime(0,0,0);
			$expiration_stamp = $expiration_stamp->getTimestamp();

			//booking
			$booking_date_stamp = new DateTime($booking_date);
			$booking_date_stamp->setTime(0,0,0);
			$booking_date_stamp = $booking_date_stamp->getTimestamp();

			if($expiration_stamp > dy_strtotime('today midnight'))
			{
				if($package_type !== 'transport' && $package_type !== 'one-day')
				{
					$arr_valid_expiration = [];

					for ($x = 0; $x < count($booking_dates_range); $x++) {
						$range_date = new DateTime($booking_dates_range[$x]);
						$range_date->setTime(0, 0, 0);
						$range_date = $range_date->getTimestamp();
						
						if ($range_date > $expiration_stamp && $coupon_bookings_after_expires === false) 
						{
							$arr_valid_expiration[] = false;
						} 
						else
						{
							$arr_valid_expiration[] = true;
						}
					}

					if (!in_array(false, $arr_valid_expiration)) {
						$valid_expiration = true;
					}
				}
				else
				{
					if($booking_date_stamp >= $expiration_stamp)
					{
						if($coupon_bookings_after_expires === true)
						{
							$valid_expiration = true;
						}
					}
					else
					{
						$valid_expiration = true;
					}									
				}	
			}	
									
			$output = $valid_expiration;
		}

		return self::$cache[$cache_key] = $output;
	}
	
	
	public static function validate_category_location()
	{
		
		$cache_key = 'dy_validate_category_location';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		$output = false;
		$package_location = '';
		$package_category = '';
		$location = '';
		$category = '';
		$sort_by = '';
		$search = '';
		
		if(get_has('location'))
		{
			$package_location = secure_get('location');
			
			if(!empty($package_location))
			{
				$location = get_term_by('slug', $package_location, 'package_location');
			}
		}
		
		if(get_has('category'))
		{
			$package_category = secure_get('category');
			
			if(!empty($package_category))
			{
				$category = get_term_by('slug', $package_category, 'package_category');
			}				
		}
		if(get_has('sort'))
		{
			$sort_by_arr = dy_utilities::sort_by_arr();
			$sort_by_value = secure_get('sort');

			if(!empty($sort_by_value) || $sort_by_value !== 'any')
			{
				if(in_array($sort_by_value, $sort_by_arr))
				{
					$sort_by = true;
				}
			}
		}

		$search = !empty(secure_get('keywords', ''));

		if(!empty($location) || !empty($category) || !empty($sort_by) || !empty($search))
		{
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}	
	
	public static function has_deposit()
	{
		$output = false;
		$cache_key = 'dy_has_deposit';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(package_field('package_auto_booking'))
		{
			if(package_field('package_payment') == 1 && package_field('package_deposit') > 0 && dy_utilities::total() > 0)
			{
				$output = true;
			}			
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}
	
	public static function is_child($the_id = null)
	{
		$output = false;

		if($the_id)
		{
			$post = get_post($the_id);
		}
		else
		{
			global $post;
		}

		if($post instanceof WP_Post)
		{
			if(property_exists($post, 'ID') && self::validate_the_id($post->ID))
			{
				$cache_key = $post->ID.'_is_child';

				if (array_key_exists($cache_key, self::$cache)) {
					return self::$cache[$cache_key];
				}

				if(property_exists($post, 'post_parent') && $post->post_parent > 0)
				{
					$output = true;				
				}

				//store output in $cache
				self::$cache[$cache_key] = $output;
			}
		}
		
		return $output;
	}

	public static function has_children($the_id = 0): bool
	{
		$output = false;

		if($the_id)
		{
			$post = get_post($the_id);
		}
		else
		{
			global $post;
		}

		if($post instanceof WP_Post)
		{
			if(property_exists($post, 'ID') && self::validate_the_id($post->ID))
			{
				$cache_key = 'dy_has_children_' . $post->ID;

				if(array_key_exists($cache_key, self::$cache)) {
					return self::$cache[$cache_key];
				}

				$children = get_posts([
					'post_type'      => 'packages',
					'post_parent'    => $post->ID,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]);

				$output = !empty($children);
				self::$cache[$cache_key] = $output;
			}
		}

		return $output;
	}

	public static function is_parent_with_no_child($the_id = null)
	{
		$output = false;

		if(!$the_id) {
			$the_id = get_dy_id();
		}
		
		$cache_key = 'dy_is_parent_with_no_child_' . $the_id;
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}
		
		if(!self::has_children($the_id) && !self::is_child($the_id))
		{
			$output = true;
		}        
		
		return self::$cache[$cache_key] = $output;
	}
	
	public static function is_valid_schema($the_id = 0)
	{
		$output = false;
		$cache_key = 'dy_is_valid_schema_' . $the_id;
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!self::validate_the_id($the_id)) {
			return self::$cache[$cache_key] = false;
		}

		if(get_comments_number() > 0)
		{
			if(is_singular('packages'))
			{
				if(dy_utilities::starting_at() > 0)
				{
					$output = true;
				}
			}
			else
			{
				if(dy_utilities::starting_at_archive() > 0)
				{
					$output = true;
				}
			}	
		}
		
        //store output in $cache
        self::$cache[$cache_key] = $output;
		
		return $output;
	}

	public static function is_spam($str) {

		$str = html_entity_decode(sanitize_text_field($str));
		$emailRegex = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/';
		$domainRegex = '/\b(?:https?:\/\/)?(?:www\.)?([A-Za-z0-9.-]+\.[A-Za-z]{2,})\b/';
		$urlRegex = '/\bhttps?:\/\/[^\s]+\b/';

		return (
			preg_match($emailRegex, $str) ||
			preg_match($domainRegex, $str) ||
			preg_match($urlRegex, $str)
		);
	}

}




?>