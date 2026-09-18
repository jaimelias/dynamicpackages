<?php

if (!defined('WPINC')) exit;

/** Classify the main frontend request independently of its input validity. */
final class Dynamicpackages_Resolver
{
	public const NONE = 'none';
	public const PACKAGE = 'package';
	public const BOOKING = 'booking';
	public const SUBMISSION = 'submission';
	public const CONFIRMATION = 'confirmation';

	private static ?string $state = null;

	public function __construct()
	{
		add_action('wp', [$this, 'resolve'], 0, 0);
	}

	public function resolve(): void
	{
		self::current();
	}

	/**
	 * Available from wp onward, after the main query and globals are resolved.
	 * Early calls return NONE without caching an incomplete query's state.
	 * BOOKING and SUBMISSION describe intent; their validators run separately.
	 */
	public static function current(): string
	{
		if (!did_action('wp')) {
			return self::NONE;
		}

		if (self::$state !== null) {
			return self::$state;
		}

		if (
			is_admin()
			|| wp_doing_ajax()
			|| wp_doing_cron()
			|| (defined('REST_REQUEST') && REST_REQUEST)
		) {
			return self::$state = self::NONE;
		}

		$method = secure_server('REQUEST_METHOD');

		if ($method === 'POST') {
			return self::$state = Dynamicpackages_Actions::is_submission()
				? self::SUBMISSION
				: self::NONE;
		}

		if ($method !== 'GET') {
			return self::$state = self::NONE;
		}

		// Includes invalid/expired IDs: confirmation owns its error response too.
		if (Dy_Confirmation_Page::is_confirmation_page()) {
			return self::$state = self::CONFIRMATION;
		}

		$post = get_queried_object();

		if (
			!is_singular('packages')
			|| !$post instanceof WP_Post
			|| $post->post_type !== 'packages'
			|| $post->post_status !== 'publish'
		) {
			return self::$state = self::NONE;
		}

		// Empty or incomplete booking parameters still represent a booking attempt.
		return self::$state = get_has('start_date') || get_has('pax_regular')
			? self::BOOKING
			: self::PACKAGE;
	}
}
