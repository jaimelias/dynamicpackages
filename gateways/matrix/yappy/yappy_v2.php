<?php

if (!defined('WPINC')) exit;

require_once __DIR__ . '/yappy-v2-store.php';

/** Yappy V2 web-component checkout settled exclusively by a signed IPN. */
#[AllowDynamicProperties]
class yappy_v2
{
	private const API_URL = 'https://apipagosbg.bgeneral.cloud';
	private const SDK_URL = 'https://bt-cdn.yappy.cloud/v1/cdn/web-component-btn-yappy.js';
	private const PAYMENT_WINDOW = 300;

	private static array $cache = [];
	private static ?string $submission_status = null;

	private string $plugin_id;
	private string $id = 'yappy_v2';
	private string $name = 'Yappy V2';
	private string $merchant_id = '';
	private string $domain = '';
	private string $secret = '';
	private string $signing_key = '';
	private float $min = 0.01;
	private float $max = 0.0;
	private int $show = 0;
	private bool $enabled = false;

	public function __construct(string $plugin_id, string|int $version)
	{
		$this->plugin_id = $plugin_id;
		$this->version = $version;

		add_action('init', [$this, 'init']);
		add_action('admin_init', [$this, 'settings_init'], 1);
		add_action('admin_menu', [$this, 'add_settings_page'], 100);
		add_action('rest_api_init', [$this, 'register_rest_routes']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('dy_prepare_gateway_submission_' . $this->id, [$this, 'prepare_submission']);
		add_action('dy_process_gateway_submission_' . $this->id, [$this, 'process_submission']);

		add_filter('all_dy_request_types', [$this, 'register_dy_request_type']);
		add_filter('dy_list_gateways', [$this, 'add_gateway'], 4);
		add_filter('dy_request_the_content', [$this, 'the_content'], 101);
		add_filter('dy_request_the_title', [$this, 'the_title'], 101);
		add_filter('dy_defer_transaction_notifications', [$this, 'defer_notifications'], 10, 2);
		add_filter('dy_store_deferred_notifications', [$this, 'store_deferred_notifications'], 10, 3);
	}

	public function init(): void
	{
		$this->enabled = (string) dy_get_option($this->id . '_enabled', '0') === '1';
		$this->merchant_id = trim((string) dy_get_option($this->id . '_merchant_id', ''));
		$this->domain = $this->https_domain_url();
		$this->secret = trim((string) dy_get_option($this->id . '_secret', ''));
		$this->min = max(0.01, (float) dy_get_option($this->id . '_min', '0.01'));
		$this->max = max(0.0, (float) dy_get_option($this->id . '_max', '9999'));
		$this->show = (int) dy_get_option($this->id . '_show', '0');

		$decoded = base64_decode($this->secret, true);
		$this->signing_key = is_string($decoded)
			? (string) explode('.', $decoded, 2)[0]
			: '';
	}

	private function https_domain_url(): string
	{
		$domain_url = wp_parse_url(home_url('/'), PHP_URL_HOST);
		if (!is_string($domain_url)) return '';

		$domain_url = strtolower(rtrim($domain_url, '.'));
		if (!str_contains($domain_url, '.')
			|| filter_var($domain_url, FILTER_VALIDATE_IP) !== false
			|| filter_var($domain_url, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
			return '';
		}

		$https_domain = 'https://' . $domain_url;

		return filter_var($https_domain, FILTER_VALIDATE_URL) !== false
			? $https_domain
			: '';
	}

	public function register_dy_request_type(array $request_types): array
	{
		$request_types[] = $this->id;

		return array_values(array_unique($request_types));
	}

	public function prepare_submission(object $submission_context): void
	{
		if (!$this->is_current_submission()) return;

		if (!$this->is_active()) {
			dy_errors::add(__('Yappy is temporarily unavailable. Please choose another payment method.', 'dynamicpackages'));
			return;
		}

		$phone = (string) dy_tx::request_value('phone');
		$calling_code = ltrim((string) dy_tx::request_value('country_calling_code'), '+');

		if ($calling_code !== '507' || preg_match('/^[0-9]{8}$/D', $phone) !== 1) {
			dy_errors::add(__('Yappy requires a Panamanian phone number with eight digits.', 'dynamicpackages'));
			return;
		}

		$amount = round((float) dy_utilities::payment_amount(), 2);
		if (!$this->is_payment_amount_allowed($amount)) {
			dy_errors::add(__('Payment amount is outside the limits allowed by Yappy.', 'dynamicpackages'));
			return;
		}

		if (!in_array(strtoupper(currency_name()), ['USD', 'PAB'], true)) {
			dy_errors::add(__('Yappy only supports payments in USD or PAB.', 'dynamicpackages'));
			return;
		}

		if (!str_starts_with($this->domain, 'https://')
			|| !str_starts_with(rest_url('dy-core/yappy-v2/webhook'), 'https://')) {
			dy_errors::add(__('Yappy requires an HTTPS website and webhook URL.', 'dynamicpackages'));
			return;
		}

		if (!function_exists('openssl_encrypt') || !Dy_Yappy_V2_Store::install()) {
			dy_errors::add(__('Unable to prepare the Yappy payment. Please contact us.', 'dynamicpackages'), 503);
			return;
		}

		$this->register_paid_notification_filters();
		$submission_context->accepted = true;
	}

	public function process_submission(object $tx): void
	{
		if ((string) ($tx->dy_request ?? '') !== $this->id) return;

		self::$submission_status = 'processing';
		$tx->status = 'processing';

		$order_id = substr(hash('sha256', (string) $tx->tx_id), 0, 15);
		$private = Dy_Yappy_V2_Store::seal(['key' => $this->signing_key]);
		$record = [
			'order_id' => $order_id,
			'tx_id' => (string) $tx->tx_id,
			'state' => 'new',
			'merchant_id' => $this->merchant_id,
			'domain' => $this->domain,
			'credentials' => $private,
			'phone' => (string) dy_tx::request_value('phone'),
			'amount' => $this->amount_string((float) dy_utilities::payment_amount()),
			'expires_at' => 0,
			'launch' => '',
			'notification' => '',
			'notifications' => [],
			'retry_url' => $this->retry_url((int) $tx->dy_id),
			'approved_confirmation' => $this->approved_confirmation(),
			'messages' => $this->localized_messages(),
		];

		if ($private === '' || !Dy_Yappy_V2_Store::save($record, true)) {
			self::$submission_status = 'error';
			$tx->status = 'error';
		}
	}

	public function defer_notifications(bool $defer, object $tx): bool
	{
		return (string) ($tx->dy_request ?? '') === $this->id ? true : $defer;
	}

	public function store_deferred_notifications(
		bool $stored,
		object $tx,
		array $notifications
	): bool {
		if ((string) ($tx->dy_request ?? '') !== $this->id) return $stored;

		$record = Dy_Yappy_V2_Store::find_by_tx_id((string) $tx->tx_id);
		if ($record === null) return false;

		$record['notifications'] = $notifications;

		return Dy_Yappy_V2_Store::save($record);
	}

	private function register_paid_notification_filters(): void
	{
		add_filter('dy_email_message', [$this, 'email_message']);
		add_filter('dy_email_subject', [$this, 'email_subject']);
		add_filter('dy_email_intro', [$this, 'email_intro']);
		add_filter('dy_email_notes', [$this, 'email_notes']);
		add_filter('dy_totals_area', [$this, 'totals_area']);
		add_filter('dy_confirmation_message', [$this, 'confirmation_message']);
		add_filter('dy_order_status', static fn(): string => 'paid');
		add_filter('dy_webhook_option', static fn(): string => 'dy_webhook');
		add_filter('dy_email_label_doc', static fn(): string => esc_html(__('Invoice', 'dynamicpackages')));
	}

	public function email_subject(mixed $output = ''): string
	{
		$payment = dy_validators::has_deposit()
			? __('Deposit', 'dynamicpackages')
			: __('Payment', 'dynamicpackages');

		return '✔️ ' . sprintf(
			__('%1$s, thank you for your %2$s of %3$s: %4$s', 'dynamicpackages'),
			(string) dy_tx::request_value('first_name'),
			$payment,
			wrap_money_full((float) dy_utilities::payment_amount()),
			(string) secure_post('title')
		);
	}

	public function email_intro(mixed $output = ''): string
	{
		$payment = dy_validators::has_deposit()
			? __('Deposit', 'dynamicpackages')
			: __('Payment', 'dynamicpackages');

		return '✔️ ' . sprintf(
			__('Thank you for your %1$s of %2$s: %3$s', 'dynamicpackages'),
			$payment,
			wrap_money_full((float) dy_utilities::payment_amount()),
			(string) secure_post('title')
		);
	}

	public function email_message(mixed $output = ''): string
	{
		$message = '<p>⚠️ ' . esc_html(__('To complete this reservation we require images of the passports or valid identity documents of each participant. The documents will be compared against the originals at the meeting point.', 'dynamicpackages')) . '</p>';
		$message .= '<p>❌ ' . esc_html(__('It is not allowed to book for third parties.', 'dynamicpackages')) . '</p>';

		return $message;
	}

	public function email_notes(mixed $output = ''): string
	{
		$message = (string) package_field('package_confirmation_message');
		$details = (string) apply_filters('dy_details', '');

		return $details . ($message !== '' ? '<br/><br/>' . esc_html($message) : '');
	}

	public function totals_area(mixed $output = ''): string
	{
		$output = is_string($output) ? $output : '';
		if (!dy_validators::has_deposit()) return $output;

		return $output
			. '<br/><strong style="color: #666666;">' . esc_html(__('Paid', 'dynamicpackages')) . '</strong><br/> -'
			. wrap_money_full((float) dy_utilities::payment_amount())
			. '<br/><strong style="color: #666666;">' . esc_html(__('Amount Due', 'dynamicpackages')) . '</strong><br/> '
			. wrap_money_full((float) dy_utilities::outstanding_amount());
	}

	public function confirmation_message(): string
	{
		global $post;

		if (!$post instanceof WP_Post) return '';

		$the_id = $post->post_parent > 0 ? $post->post_parent : $post->ID;
		$current_language = current_language();
		$default_language = default_language();
		$value = (string) package_field('package_confirmation_message_' . $current_language, $the_id)
			?: (string) package_field('package_confirmation_message_' . $default_language, $the_id);

		if ($value === '') return '';

		$parsedown = new Parsedown();

		return $parsedown->text($value);
	}

	private function approved_confirmation(): array
	{
		$payment = dy_validators::has_deposit()
			? __('deposit', 'dynamicpackages')
			: __('payment', 'dynamicpackages');
		$content = '<p class="minimal_success strong"><span class="dashicons dashicons-yes"></span> '
			. esc_html(sprintf(
				__('Thank you for your %1$s of %2$s.', 'dynamicpackages'),
				$payment,
				wrap_money_full((float) dy_utilities::payment_amount())
			))
			. '</p>';
		$content .= '<div class="bottom-20">' . (string) apply_filters('dy_description', '') . '</div>';
		$content .= '<div class="bottom-20">' . $this->email_message() . '</div>';
		$content .= '<p class="minimal_success strong"><span class="dashicons dashicons-email"></span> '
			. esc_html(sprintf(
				__('We sent more details and the booking confirmation to %s.', 'dynamicpackages'),
				(string) dy_tx::request_value('email')
			))
			. '</p>';

		$calendar = apply_filters('dy_add_to_calendar', null);
		if (is_string($calendar) && $calendar !== '') {
			$content .= '<div class="text-center">' . $calendar . '</div>';
		}

		return [
			'title' => __('Payment Approved', 'dynamicpackages'),
			'content' => $content,
			'excerpt' => (string) apply_filters('dy_description', ''),
			'events' => [],
		];
	}

	private function localized_messages(): array
	{
		return [
			'cancelled_title' => __('Payment Cancelled', 'dynamicpackages'),
			'declined_title' => __('Payment Declined', 'dynamicpackages'),
			'expired_title' => __('Payment Expired', 'dynamicpackages'),
			'error_title' => __('Checkout Error', 'dynamicpackages'),
			'cancelled' => __('The Yappy payment was cancelled. No charge was made.', 'dynamicpackages'),
			'declined' => __('The Yappy payment was declined. No charge was made.', 'dynamicpackages'),
			'expired' => __('The time available to complete your Yappy payment has expired. Return to the package to try again.', 'dynamicpackages'),
			'error' => __('Yappy could not process this payment. No charge was confirmed.', 'dynamicpackages'),
			'waiting' => __('Waiting for Yappy to confirm your payment.', 'dynamicpackages'),
			'unavailable' => __('Yappy is temporarily unavailable. Please try again shortly.', 'dynamicpackages'),
			'uncertain' => __('The payment request may already exist. Please wait for confirmation or contact us before trying again.', 'dynamicpackages'),
			'retry' => __('Retry payment', 'dynamicpackages'),
		];
	}

	public function the_content(mixed $content = ''): string
	{
		$content = is_string($content) ? $content : '';
		if (!$this->is_current_submission()) return $content;

		if (self::$submission_status === 'error') {
			return '<p class="minimal_alert strong">'
				. esc_html(__('Yappy could not prepare the payment. Please contact us before trying again.', 'dynamicpackages'))
				. '</p>';
		}

		$amount = wrap_money_full((float) dy_utilities::payment_amount());

		return '<div id="dy-yappy-v2">'
			. '<p class="minimal_warning strong">'
			. esc_html(sprintf(__('Complete your Yappy payment of %s.', 'dynamicpackages'), $amount))
			. '</p>'
			. '<p>' . esc_html(__('Press the Yappy button and approve the payment in the Yappy app. You will have five minutes after the order is created.', 'dynamicpackages')) . '</p>'
			. '<div id="dy-yappy-v2-button"></div>'
			. '<p id="dy-yappy-v2-timer" class="large strong" hidden><span aria-hidden="true">⏱️</span> <span id="dy-yappy-v2-timer-text">05:00</span></p>'
			. '<p role="status" aria-live="polite"></p>'
			. '</div>';
	}

	public function the_title(mixed $title = ''): string
	{
		$title = is_string($title) ? $title : '';

		return $this->is_current_submission()
			? __('Complete Your Yappy Payment', 'dynamicpackages')
			: $title;
	}

	private function is_current_submission(): bool
	{
		return Dynamicpackages_Submit::is_submission()
			&& secure_post('dy_request', '', 'sanitize_key') === $this->id;
	}

	public function is_active(): bool
	{
		return $this->enabled
			&& $this->merchant_id !== ''
			&& $this->signing_key !== ''
			&& filter_var($this->domain, FILTER_VALIDATE_URL) !== false
			&& str_starts_with($this->domain, 'https://')
			&& $this->max >= $this->min;
	}

	public function show(): bool
	{
		$cache_key = $this->id . '_show';
		if (array_key_exists($cache_key, self::$cache)) return self::$cache[$cache_key];

		return self::$cache[$cache_key] = is_singular('packages')
			&& $this->is_active()
			&& $this->is_valid();
	}

	private function is_valid(): bool
	{
		$payment = (int) package_field('package_payment');
		$total = is_booking_page() || Dynamicpackages_Submit::is_submission()
			? (float) dy_utilities::payment_amount()
			: (float) dy_utilities::starting_at();

		if (!is_booking_page()
			&& !Dynamicpackages_Submit::is_submission()
			&& (int) package_field('package_fixed_price') === 0) {
			$total *= (int) package_field('package_max_persons');
		}

		if (!$this->is_payment_amount_allowed($total)) return false;

		return ($payment === $this->show && $payment === 0)
			|| dy_validators::has_deposit();
	}

	private function is_payment_amount_allowed(float $amount): bool
	{
		$amount = round($amount, 2);

		return $this->max >= $this->min
			&& $amount >= round($this->min, 2)
			&& $amount <= round($this->max, 2);
	}

	public function add_gateway(array $gateways): array
	{
		if (!$this->show()) return $gateways;

		$add = is_singular('packages');
		$request_type = secure_post('dy_request', '', 'sanitize_key');
		$failed_gateway = apply_filters('dy_fail_checkout_gateway_name', null);

		if (Dynamicpackages_Submit::is_submission()
			&& in_array($request_type, ['estimate_request', $failed_gateway], true)
			&& dy_validators::validate_request()) {
			$add = true;
		}

		if (!$add) return $gateways;

		$asset_url = plugin_dir_url(__DIR__) . 'assets/';
		$gateways[$this->id] = [
			'id' => $this->id,
			'name' => $this->name,
			'type' => 'alt',
			'color' => '#fff',
			'background_color' => '#013685',
			'brands' => [$this->name],
			'branding' => '<img src="' . esc_url($asset_url . 'yappy_direct.svg') . '" width="80" height="69" alt="Yappy" />',
			'icon' => '<img src="' . esc_url($asset_url . 'yappy_direct_icon.svg') . '" width="21" height="12" alt="Yappy" />',
			'gateway_coupon' => 'YAPPYV2',
		];

		return $gateways;
	}

	public function register_rest_routes(): void
	{
		register_rest_route('dy-core', '/yappy-v2/start', [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [$this, 'start_endpoint'],
			'permission_callback' => '__return_true',
		]);
		register_rest_route('dy-core', '/yappy-v2/check', [
			'methods' => WP_REST_Server::READABLE,
			'callback' => [$this, 'check_endpoint'],
			'permission_callback' => '__return_true',
		]);
		register_rest_route('dy-core', '/yappy-v2/webhook', [
			'methods' => WP_REST_Server::READABLE,
			'callback' => [$this, 'webhook_endpoint'],
			'permission_callback' => '__return_true',
		]);
	}

	public function start_endpoint(WP_REST_Request $request): WP_REST_Response
	{
		$owned = $this->owned_record($request);
		if ($owned instanceof WP_Error) return $this->wp_error_response($owned);

		[$record, $tx] = $owned;
		$tx_id = (string) $record['tx_id'];

		if (!Dy_Yappy_V2_Store::lock($tx_id)) {
			return $this->error_response(__('The payment is already being processed. Please wait.', 'dynamicpackages'), 409, 'locked');
		}

		try {
			$record = Dy_Yappy_V2_Store::find_by_tx_id($tx_id);
			if ($record === null || Dy_Yappy_V2_Store::read_failed()) {
				return $this->error_response(__('Payment data is temporarily unavailable.', 'dynamicpackages'), 503, 'unavailable');
			}

			if ((string) $tx->status !== 'processing') {
				return $this->error_response(__('This payment is no longer pending.', 'dynamicpackages'), 409, (string) $record['state']);
			}

			$state = (string) ($record['state'] ?? '');
			$expires_at = (int) ($record['expires_at'] ?? 0);
			if ($state === 'ready' && $expires_at > time()) {
				$launch = Dy_Yappy_V2_Store::open((string) ($record['launch'] ?? ''));
				if ($launch !== null) {
					return $this->rest_response([
						'success' => true,
						'body' => $launch,
						'expiresAt' => $expires_at,
					]);
				}
			}

			if ($expires_at > 0 && $expires_at <= time()) {
				return $this->error_response((string) $record['messages']['expired'], 409, 'expired');
			}

			if ($state !== 'new') {
				return $this->error_response((string) $record['messages']['uncertain'], 409, $state);
			}

			if (!$this->is_active()) {
				return $this->error_response((string) $record['messages']['unavailable'], 503, 'unavailable');
			}

			$merchant = $this->api_request('/payments/validate/merchant', [
				'merchantId' => (string) $record['merchant_id'],
				'urlDomain' => (string) $record['domain'],
			]);

			if (!$merchant['success']
				|| !is_string($merchant['body']['token'] ?? null)
				|| !is_numeric($merchant['body']['epochTime'] ?? null)) {
				return $this->error_response($this->api_error_message((string) $merchant['code']), 502, 'api_error');
			}

			$payment_date = $this->normalize_epoch((int) $merchant['body']['epochTime']);
			$payment_date = abs($payment_date - time()) <= self::PAYMENT_WINDOW
				? $payment_date
				: time();
			$record['state'] = 'creating';
			$record['expires_at'] = $payment_date + self::PAYMENT_WINDOW;
			if (!Dy_Yappy_V2_Store::save($record)) {
				return $this->error_response(__('Unable to store the payment request.', 'dynamicpackages'), 503, 'unavailable');
			}

			$launch = $this->api_request('/payments/payment-wc', [
				'merchantId' => (string) $record['merchant_id'],
				'orderId' => (string) $record['order_id'],
				'domain' => (string) $record['domain'],
				'paymentDate' => (int) $merchant['body']['epochTime'],
				'aliasYappy' => (string) $record['phone'],
				'ipnUrl' => rest_url('dy-core/yappy-v2/webhook'),
				'discount' => '0.00',
				'taxes' => '0.00',
				'subtotal' => (string) $record['amount'],
				'total' => (string) $record['amount'],
			], (string) $merchant['body']['token']);

			if (!$launch['success']) {
				$record['state'] = in_array($launch['code'], ['', 'network', 'E007'], true)
					? 'uncertain'
					: 'error';
				Dy_Yappy_V2_Store::save($record);

				if ($record['state'] === 'error') {
					$this->update_transaction($record, 'error');
				}

				return $this->error_response(
					$this->api_error_message((string) $launch['code']),
					502,
					(string) $record['state']
				);
			}

			$body = $launch['body'];
			$reference = $body['transactionId'] ?? null;
			$valid = (is_string($reference) || is_int($reference)) && (string) $reference !== '';
			foreach (['token', 'documentName'] as $field) {
				if (!is_string($body[$field] ?? null) || $body[$field] === '') $valid = false;
			}

			if (!$valid) {
				$record['state'] = 'uncertain';
				Dy_Yappy_V2_Store::save($record);

				return $this->error_response((string) $record['messages']['uncertain'], 502, 'uncertain');
			}

			$public_body = array_intersect_key($body, array_flip(['transactionId', 'token', 'documentName']));
			$record['launch'] = Dy_Yappy_V2_Store::seal($public_body);
			$record['state'] = 'ready';

			if ($record['launch'] === '' || !Dy_Yappy_V2_Store::save($record)) {
				return $this->error_response((string) $record['messages']['uncertain'], 503, 'uncertain');
			}

			return $this->rest_response([
				'success' => true,
				'body' => $public_body,
				'expiresAt' => (int) $record['expires_at'],
			]);
		} finally {
			Dy_Yappy_V2_Store::lock($tx_id, true);
		}
	}

	public function check_endpoint(WP_REST_Request $request): WP_REST_Response
	{
		$owned = $this->owned_record($request);
		if ($owned instanceof WP_Error) return $this->wp_error_response($owned);

		[$record, $tx] = $owned;
		$state = (string) ($record['state'] ?? '');
		$expires_at = (int) ($record['expires_at'] ?? 0);

		if (in_array($state, ['ready', 'creating'], true)
			&& $expires_at > 0
			&& $expires_at <= time()) {
			$state = 'expired';
		}

		return $this->rest_response([
			'success' => true,
			'status' => (string) $tx->status,
			'paymentStatus' => $state,
			'expiresAt' => $expires_at,
			'retryUrl' => (string) ($record['retry_url'] ?? ''),
		]);
	}

	public function webhook_endpoint(WP_REST_Request $request): WP_REST_Response
	{
		$params = $request->get_query_params();
		foreach (['orderId', 'status', 'domain'] as $field) {
			if (!is_string($params[$field] ?? null)) {
				return $this->rest_response(['success' => false], 400);
			}
		}

		$hash = $params['Hash'] ?? $params['hash'] ?? null;
		if (!is_string($hash)
			|| preg_match('/^[a-fA-F0-9]{64}$/D', $hash) !== 1
			|| (isset($params['Hash'], $params['hash']) && $params['Hash'] !== $params['hash'])
			|| preg_match('/^[a-zA-Z0-9]{1,15}$/D', $params['orderId']) !== 1
			|| !in_array($params['status'], ['E', 'R', 'C', 'X'], true)) {
			return $this->rest_response(['success' => false], 400);
		}

		$record = Dy_Yappy_V2_Store::find_by_order_id($params['orderId']);
		if (Dy_Yappy_V2_Store::read_failed()) return $this->rest_response(['success' => false], 503);

		$credentials = $record === null
			? null
			: Dy_Yappy_V2_Store::open((string) ($record['credentials'] ?? ''));
		$expected = $credentials === null
			? ''
			: hash_hmac(
				'sha256',
				$params['orderId'] . $params['status'] . $params['domain'],
				(string) ($credentials['key'] ?? '')
			);

		if ($record === null
			|| $params['domain'] !== (string) ($record['domain'] ?? '')
			|| $expected === ''
			|| !hash_equals($expected, strtolower($hash))) {
			return $this->rest_response(['success' => false], 403);
		}

		$tx_id = (string) $record['tx_id'];
		if (!Dy_Yappy_V2_Store::lock($tx_id)) return $this->rest_response(['success' => false], 503);

		try {
			$record = Dy_Yappy_V2_Store::find_by_tx_id($tx_id);
			$tx = dy_tx::get_stored_tx($tx_id);
			if ($record === null || $tx === null || !$this->valid_transaction($tx)) {
				return $this->rest_response(['success' => false], 409);
			}
			if ((string) ($record['state'] ?? '') === 'new') {
				return $this->rest_response(['success' => false], 409);
			}

			$state = match ($params['status']) {
				'E' => 'approved',
				'R' => 'declined',
				'C' => 'cancelled',
				'X' => 'expired',
			};

			if ((string) $record['state'] !== 'approved'
				&& ($state === 'approved'
					|| !in_array((string) $record['state'], ['declined', 'cancelled', 'expired'], true))) {
				$record['state'] = $state;
				$record['yappy_response'] = [
					'status' => $params['status'],
					'confirmationNumber' => sanitize_text_field((string) ($params['confirmationNumber'] ?? '')),
				];

				$status = $state === 'approved' ? 'success' : 'declined';
				if (!$this->update_transaction($record, $status)) {
					return $this->rest_response(['success' => false], 503);
				}

				if (!Dy_Yappy_V2_Store::save($record)) {
					return $this->rest_response(['success' => false], 503);
				}
			}

			if ((string) $record['state'] === 'approved'
				&& (string) ($record['notification'] ?? '') === '') {
				$record['notification'] = 'claimed';
				if (!Dy_Yappy_V2_Store::save($record)) {
					return $this->rest_response(['success' => false], 503);
				}

				Dynamicpackages_Submit::dispatch_notification_snapshot(
					is_array($record['notifications'] ?? null) ? $record['notifications'] : []
				);

				$record['notification'] = 'attempted';
				if (!Dy_Yappy_V2_Store::save($record)) {
					return $this->rest_response(['success' => false], 503);
				}
			}

			return $this->rest_response(['success' => true]);
		} finally {
			Dy_Yappy_V2_Store::lock($tx_id, true);
		}
	}

	private function owned_record(WP_REST_Request $request): array|WP_Error
	{
		$tx_id = $request->get_param('tx_id');
		$token = $request->get_header('x-dy-yappy-token');

		if (!is_string($tx_id) || !wp_is_uuid($tx_id, 4)
			|| !is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
			return new WP_Error('yappy_forbidden', __('Invalid payment access.', 'dynamicpackages'), ['status' => 403]);
		}

		$record = Dy_Yappy_V2_Store::find_by_tx_id($tx_id);
		if (Dy_Yappy_V2_Store::read_failed()) {
			return new WP_Error('yappy_unavailable', __('Payment data is temporarily unavailable.', 'dynamicpackages'), ['status' => 503]);
		}

		$tx = dy_tx::get_stored_tx($tx_id);
		if ($record === null || $tx === null || !$this->valid_transaction($tx)
			|| !hash_equals($this->access_token($tx), $token)) {
			return new WP_Error('yappy_forbidden', __('Invalid or expired payment access.', 'dynamicpackages'), ['status' => 403]);
		}

		return [$record, $tx];
	}

	private function valid_transaction(object $tx): bool
	{
		return (string) ($tx->dy_request ?? '') === $this->id
			&& dy_tx::validate((string) ($tx->tx_id ?? ''), get_object_vars($tx));
	}

	private function access_token(object $tx): string
	{
		return hash_hmac(
			'sha256',
			(string) $tx->tx_id . '|' . (string) $tx->secret_tx_id,
			wp_salt('nonce')
		);
	}

	private function update_transaction(array $record, string $status): bool
	{
		$tx = dy_tx::get_stored_tx((string) $record['tx_id']);
		if ($tx === null || !$this->valid_transaction($tx)) return false;

		$tx->status = $status;
		$tx->confirmation = $this->confirmation_for_state($record, $tx);

		return dy_tx::update($tx);
	}

	private function confirmation_for_state(array $record, object $tx): array
	{
		$state = (string) ($record['state'] ?? 'error');
		if ($state === 'approved') {
			$confirmation = is_array($record['approved_confirmation'] ?? null)
				? $record['approved_confirmation']
				: [];
			$confirmation['events'] = [$this->purchase_event($record, $tx)];

			return $confirmation;
		}

		$message = (string) ($record['messages'][$state] ?? $record['messages']['error'] ?? '');
		$content = '<p class="minimal_alert strong">' . esc_html($message) . '</p>';

		if ($state === 'expired') {
			$content .= '<div id="dy-yappy-v2"><p role="status" aria-live="polite"></p></div>';
		}

		$title_key = in_array($state, ['cancelled', 'declined', 'expired', 'error'], true)
			? $state . '_title'
			: 'error_title';

		return [
			'title' => (string) ($record['messages'][$title_key] ?? ''),
			'content' => $content,
			'excerpt' => '',
			'events' => [],
		];
	}

	private function purchase_event(array $record, object $tx): array
	{
		$payload = $record['notifications']['webhook']['payload'] ?? [];
		$payload = is_array($payload) ? $payload : [];
		$percentage = (float) dy_get_option('dy_bidding_conversion_percentage', '15');
		$percentage = max(1, min(100, $percentage));
		$value = max(0, (float) ($payload['amount'] ?? $record['amount'] ?? 0)) * ($percentage / 100);
		$currency = strtoupper(sanitize_text_field((string) ($payload['currency_name'] ?? currency_name())));
		$item = dy_gtag_build_item(
			(int) ($tx->dy_id ?? 0),
			(string) ($payload['title'] ?? ''),
			max(1, (int) ($payload['pax_num'] ?? 1)),
			$value
		);

		return [
			'name' => 'purchase',
			'params' => [
				'transaction_id' => (string) $tx->tx_id,
				'value' => round($value, 2),
				'currency' => preg_match('/^[A-Z]{3}$/D', $currency) === 1 ? $currency : 'USD',
				'items' => [$item],
			],
		];
	}

	private function api_request(string $path, array $body, string $token = ''): array
	{
		$headers = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
		];
		if ($token !== '') $headers['Authorization'] = $token;

		$response = wp_remote_post(self::API_URL . $path, [
			'headers' => $headers,
			'body' => wp_json_encode($body),
			'timeout' => 25,
			'redirection' => 0,
		]);

		if (is_wp_error($response)) {
			return ['success' => false, 'body' => [], 'code' => 'network'];
		}

		$status = wp_remote_retrieve_response_code($response);
		$data = json_decode(wp_remote_retrieve_body($response), true);
		$code = is_array($data) && is_scalar($data['status']['code'] ?? null)
			? (string) $data['status']['code']
			: '';
		$valid = $status >= 200
			&& $status < 300
			&& is_array($data)
			&& is_array($data['body'] ?? null)
			&& !str_starts_with($code, 'E');

		return [
			'success' => $valid,
			'body' => $valid ? $data['body'] : [],
			'code' => $code,
		];
	}

	private function api_error_message(string $code): string
	{
		return match ($code) {
			'E005' => __('This phone number is not registered with Yappy.', 'dynamicpackages'),
			'E007' => __('This Yappy order may already exist. Please wait for confirmation or contact us.', 'dynamicpackages'),
			'E009' => __('Yappy rejected the order identifier.', 'dynamicpackages'),
			'E010' => __('Yappy rejected the payment amount.', 'dynamicpackages'),
			default => __('Yappy is temporarily unavailable. Please try again shortly.', 'dynamicpackages'),
		};
	}

	private function normalize_epoch(int $epoch): int
	{
		return $epoch > 9999999999 ? intdiv($epoch, 1000) : $epoch;
	}

	private function amount_string(float $amount): string
	{
		return number_format(round($amount, 2), 2, '.', '');
	}

	private function retry_url(int $post_id): string
	{
		$base_url = (string) secure_post('url', '', 'esc_url_raw');
		$fallback = get_permalink($post_id);
		$base_url = wp_validate_redirect($base_url, is_string($fallback) ? $fallback : home_url('/'));
		$args = ['enable_payment' => 'true'];

		foreach ([
			'pax_regular', 'pax_discount', 'pax_free', 'transport_type', 'route',
			'start_date', 'start_hour', 'end_date', 'end_hour', 'additional_time',
			'coupon_code', 'force_availability',
		] as $field) {
			$value = dy_tx::request_value($field);
			if ($value !== '' && $value !== null && $value !== false) $args[$field] = $value;
		}

		return add_query_arg($args, $base_url);
	}

	private function rest_response(array $data, int $status = 200): WP_REST_Response
	{
		$response = new WP_REST_Response($data, $status);
		$headers = wp_get_nocache_headers();
		unset($headers['Last-Modified']);
		$headers['Pragma'] = 'no-cache';
		$response->set_headers($headers);

		return $response;
	}

	private function error_response(string $message, int $status, string $code): WP_REST_Response
	{
		return $this->rest_response([
			'success' => false,
			'code' => sanitize_key($code),
			'message' => $message,
		], $status);
	}

	private function wp_error_response(WP_Error $error): WP_REST_Response
	{
		$data = $error->get_error_data();
		$status = is_array($data) ? (int) ($data['status'] ?? 500) : 500;

		return $this->error_response($error->get_error_message(), $status, $error->get_error_code());
	}

	public function enqueue_scripts(): void
	{
		if (!Dy_Confirmation_Page::is_confirmation_page()) return;

		$tx_id = (string) get_query_var(DY_CORE_CONFIRMATION_PAGE_SLUG);
		$tx = dy_tx::get_stored_tx($tx_id);
		if ($tx === null || (string) ($tx->dy_request ?? '') !== $this->id) return;

		$record = Dy_Yappy_V2_Store::find_by_tx_id($tx_id);
		if ($record === null) return;

		$state = (string) ($record['state'] ?? '');
		if ((string) $tx->status !== 'processing' && $state !== 'expired') return;

		$messages = is_array($record['messages'] ?? null)
			? $record['messages']
			: $this->localized_messages();

		wp_enqueue_script(
			'dynamicpackages-yappy-v2',
			plugin_dir_url(__FILE__) . 'yappy-v2.js',
			['jquery'],
			$this->version,
			true
		);
		wp_localize_script('dynamicpackages-yappy-v2', 'dyYappyV2', [
			'txId' => $tx_id,
			'token' => $this->access_token($tx),
			'sdkUrl' => self::SDK_URL,
			'startUrl' => rest_url('dy-core/yappy-v2/start'),
			'checkUrl' => rest_url('dy-core/yappy-v2/check'),
			'expiresAt' => (int) ($record['expires_at'] ?? 0),
			'paymentStatus' => $state,
			'retryUrl' => (string) ($record['retry_url'] ?? ''),
			'waiting' => (string) ($messages['waiting'] ?? ''),
			'unavailable' => (string) ($messages['unavailable'] ?? ''),
			'uncertain' => (string) ($messages['uncertain'] ?? ''),
			'expired' => (string) ($messages['expired'] ?? ''),
			'retry' => (string) ($messages['retry'] ?? ''),
		]);
	}

	public function settings_init(): void
	{
		$group = $this->id . '_settings';

		foreach ([
			$this->id . '_enabled' => 'intval',
			$this->id . '_merchant_id' => 'sanitize_text_field',
			$this->id . '_secret' => 'sanitize_text_field',
			$this->id . '_min' => 'floatval',
			$this->id . '_max' => 'floatval',
			$this->id . '_show' => 'intval',
		] as $option => $sanitizer) {
			register_setting($group, $option, $sanitizer);
		}

		add_settings_section(
			$this->id . '_credentials',
			__('Yappy V2 Production Credentials', 'dynamicpackages'),
			'__return_false',
			$group
		);
		add_settings_section(
			$this->id . '_controls',
			__('General Settings', 'dynamicpackages'),
			'__return_false',
			$group
		);

		add_settings_field($this->id . '_enabled', __('Enabled', 'dynamicpackages'), ['dy_select_option', 'custom'], $group, $this->id . '_controls', [
			'key' => $this->id . '_enabled',
			'options' => [0 => __('No', 'dynamicpackages'), 1 => __('Yes', 'dynamicpackages')],
		]);
		add_settings_field($this->id . '_min', __('Min. Amount', 'dynamicpackages'), ['dy_input_option', 'price'], $group, $this->id . '_controls', [
			'key' => $this->id . '_min',
			'value' => dy_get_option($this->id . '_min', '0.01'),
			'append' => currency_symbol(),
		]);
		add_settings_field($this->id . '_max', __('Max. Amount', 'dynamicpackages'), ['dy_input_option', 'price'], $group, $this->id . '_controls', [
			'key' => $this->id . '_max',
			'value' => dy_get_option($this->id . '_max', '9999'),
			'append' => currency_symbol(),
		]);
		add_settings_field($this->id . '_show', __('Show', 'dynamicpackages'), ['dy_select_option', 'custom'], $group, $this->id . '_controls', [
			'key' => $this->id . '_show',
			'options' => [
				0 => __('Full Payments and Deposits', 'dynamicpackages'),
				1 => __('Only Deposits', 'dynamicpackages'),
			],
		]);

		foreach ([
			$this->id . '_merchant_id' => __('Merchant ID', 'dynamicpackages'),
			$this->id . '_secret' => __('Secret Key (Base64)', 'dynamicpackages'),
		] as $key => $label) {
			add_settings_field($key, $label, ['dy_input_option', 'text'], $group, $this->id . '_credentials', [
				'key' => $key,
				'klass' => 'regular-text',
				'autocomplete' => 'off',
			]);
		}
	}

	public function add_settings_page(): void
	{
		add_submenu_page(
			$this->plugin_id,
			$this->name,
			'💸 ' . $this->name,
			'manage_options',
			$this->id,
			[$this, 'settings_page']
		);
	}

	public function settings_page(): void
	{
		?>
		<div class="wrap">
			<h1><?php echo esc_html($this->name); ?></h1>
			<p>
				<?php echo esc_html__('This webhook URL is sent automatically to Yappy as the "IPN" param when each payment order is created; it does not need to be entered in the Yappy portal:', 'dynamicpackages'); ?>
				<br/><code><?php echo esc_html(rest_url('dy-core/yappy-v2/webhook')); ?></code>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: HTTPS domain URL registered in the Yappy Portal */
					esc_html__('Credentials issued in the Yappy V2 Portal are assigned to a single domain. Make sure the domain registered there matches this one: %s', 'dynamicpackages'),
					'<code>' . esc_html($this->domain) . '</code>'
				);
				?>
			</p>
			<p>
				<?php echo esc_html__('Yappy V2 is not available for sandbox testing, and it does not work in localhost environments.', 'dynamicpackages'); ?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields($this->id . '_settings');
				do_settings_sections($this->id . '_settings');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
