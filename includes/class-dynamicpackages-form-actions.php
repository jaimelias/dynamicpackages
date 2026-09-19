<?php

if (!defined('WPINC')) exit;

class Dynamicpackages_Actions
{
	private static ?bool $submission_valid = null;
	private string $plugin_dir_path_dir;

	public function __construct()
	{
		$this->plugin_dir_path_dir = plugin_dir_path(__DIR__);
		add_action('template_redirect', [$this, 'submit'], 20);
	}

	/** Recognize checkout intent on the resolved destination; validation runs later. */
	public static function is_submission(): bool
	{
		if (!did_action('wp') || secure_server('REQUEST_METHOD') !== 'POST'
			|| is_admin() || wp_doing_ajax() || wp_doing_cron()
			|| (defined('REST_REQUEST') && REST_REQUEST)) {
			return false;
		}

		$request_type = secure_post('dy_request', '', 'sanitize_key');
		if (!in_array($request_type, dy_utilities::all_dy_request_types(), true)) {
			return false;
		}

		$post = get_queried_object();
		return $post instanceof WP_Post && $post->post_status === 'publish'
			&& ($post->post_type === 'packages' || has_shortcode($post->post_content, 'package_contact'));
	}

	public function submit(): void
	{
		if ($this->send_data()) {
			nocache_headers();
			wp_safe_redirect(trailingslashit(home_lang()) . 'dy-tx/' . rawurlencode((string) secure_post('tx_id')), 303);
			exit;
		}
	}

	/** Serialize all side effects for a transaction, including concurrent POST retries. */
	public function send_data(): bool
	{
		if (!self::is_submission()) {
			return false;
		}
		if (!dy_validators::validate_tx_id()
			|| (int) secure_post('dy_id', 0, 'absint') !== (int) get_queried_object_id()) {
			dy_errors::add(__('Invalid tx_id.', 'dynamicpackages'));
			return false;
		}

		global $wpdb;
		$tx_id = (string) secure_post('tx_id');
		$lock = 'dy_submit_' . substr(hash('sha256', $wpdb->prefix . $tx_id), 0, 54);
		if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 1)', $lock)) !== '1') {
			dy_errors::add(__('This request is already being processed. Please try again shortly.', 'dynamicpackages'), 409);
			return false;
		}

		try {
			// Another worker may have completed while this request waited for the lock.
			if (!wp_using_ext_object_cache()) {
				wp_cache_delete('_transient_tx_id_' . $tx_id, 'options');
				wp_cache_delete('_transient_timeout_tx_id_' . $tx_id, 'options');
			}
			$tx = dy_tx::get_stored_tx($tx_id);
			if ($tx === null) {
				dy_errors::add(__('Invalid or expired transaction ID.', 'dynamicpackages'));
				return false;
			}
			// Never repeat charges or notifications, even after an interrupted response.
			if ($tx->status !== 'started') {
				return true;
			}
			if (!self::validate_request()) {
				return false;
			}

			$context = (object) ['accepted' => in_array($tx->dy_request, ['contact', 'estimate_request'], true)];
			do_action('dy_prepare_gateway_submission_' . $tx->dy_request, $context);
			if (!$context->accepted || dy_errors::has_errors()) {
				if (!dy_errors::has_errors()) {
					dy_errors::add(__('The selected payment method could not accept this request.', 'dynamicpackages'));
				}
				return false;
			}

			$tx->status = 'processing';
			if (!$this->store($tx)) {
				return false;
			}
			$tx->status = 'success';
			do_action('dy_process_gateway_submission_' . $tx->dy_request, $tx);
			foreach (dy_tx::get_sanitized_request_payload() as $section => $values) {
				$tx->{$section} = (object) $values;
			}
			$this->queue_conversion_events($tx->dy_request, $tx_id);
			$tx->confirmation = $this->confirmation_result($tx);
			// Persist the result before notifications; a failed response must not allow a second charge.
			if (!$this->store($tx)) {
				return false;
			}
			$this->send_notifications($tx);
			return true;
		} finally {
			$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
		}
	}

	private function store(object $tx): bool
	{
		if (dy_tx::update($tx)) {
			return true;
		}
		dy_errors::add(__('Unable to store the transaction result. Please contact us before submitting again.', 'dynamicpackages'), 503);
		return false;
	}

	private function confirmation_result(object $tx): array
	{
		$content = '<p class="minimal_success strong">' . esc_html(__('Thank you for contacting us. Our staff will be in touch with you soon.', 'dynamicpackages')) . '</p>';
		$title = __('Thank You for Your Request', 'dynamicpackages');
		$events = [];
		foreach (['generate_lead', 'purchase'] as $name) {
			$event = $GLOBALS['dy_gtag_server_events'][$name . '|' . $tx->tx_id] ?? null;
			if (is_array($event)) {
				$events[] = $event;
			}
		}
		return [
			'title' => (string) apply_filters('dy_request_the_title', $title),
			'content' => (string) apply_filters('dy_request_the_content', $content),
			'excerpt' => (string) apply_filters('dy_description', ''),
			'events' => $events,
		];
	}

	public static function validate_request(): bool
	{
		if (!self::is_submission()) return false;
		if (self::$submission_valid !== null) return self::$submission_valid;
		self::$submission_valid = false;

		if (!dy_validators::validate_tx_id()) {
			dy_errors::add(__('Invalid tx_id.', 'dynamicpackages'));
			return false;
		}
		if (!dy_validators::validate_contact_details() || !dy_validators::validate_booking_details()) {
			return false;
		}
		if (!validate_turnstile((string) secure_post('cf-turnstile-response'), 'submit-transaction')) {
			return false;
		}

		$submission_valid = dy_validators::validate_submission_rate_limits();
		$post_valid = dy_validators::validate_post_id_rate_limits();
		$gateway_valid = dy_validators::validate_gateway_rate_limits();
		return self::$submission_valid = $submission_valid && $post_valid && $gateway_valid;
	}

	private function send_notifications(object $tx): void
	{
		$the_id = (int) $tx->dy_id;
		if(request_has('add_ons'))
		{
			$add_ons_package_id = sanitize_key('dy_add_ons_' . $the_id);
			$add_ons = secure_request('add_ons');
			setcookie($add_ons_package_id, $add_ons, time() + 3600);
		}
		
		$data = [];
		// Match the scalar fields posted by populateCheckoutForm, excluding card credentials.
		foreach ([
			'post_id', 'description', 'coupon_discount', 'coupon_discount_amount', 'total',
			'duration', 'pax_num', 'package_code', 'title', 'package_type',
			'package_not_included', 'package_included', 'url', 'currency_name',
			'currency_symbol', 'outstanding', 'amount', 'regular_amount', 'payment_type',
			'deposit', 'enable_payment', 'dy_network', 'address', 'city', 'country',
		] as $field) {
			if (post_has($field)) {
				$data[$field] = secure_post($field);
			}
		}
		foreach ((array) dy_get_taxonomies('package_terms_conditions') as $term) {
			$field = 'terms_conditions_' . $term->term_taxonomy_id;
			if (post_has($field)) {
				$data[$field] = secure_post($field);
			}
		}
		$data['tx_id'] = $tx->tx_id;
		$data['dy_request'] = $tx->dy_request;
		$data['dy_id'] = $the_id;
		$data = array_merge($data, $this->flatten_transaction_payload($tx));


		$by_hour = package_field('package_by_hour');
		$start_hour = package_field('package_start_hour');
		$end_hour = package_field('package_end_hour');
		$invertHours = $by_hour === '0' && $start_hour !== '' && $end_hour !== '';

		if($invertHours && array_key_exists('package_type', $data) && array_key_exists('route', $data))
		{
			if($data['package_type'] === 'transport' && $data['route'] === '1')
			{
				if(array_key_exists('start_hour', $data) && array_key_exists('end_hour', $data))
				{
					list($data['start_hour'], $data['end_hour']) = [$data['end_hour'], $data['start_hour']];
				}
				
			}
		}

		$data['disabled_dates_api'] = package_field('package_disabled_dates_api', $the_id);

		$webhook_option = apply_filters('dy_webhook_option', 'dy_quote_webhook');
		$webhook_args = $data;
		$webhook_args['providers'] = apply_filters('dy_list_providers', []);
		$webhook_args['add_ons'] = apply_filters('dy_included_add_ons_arr', []);

		$payload = wp_json_encode($webhook_args);

		dy_utilities::webhook($webhook_option, $payload);
		$this->send_email();

	}

	private function flatten_transaction_payload(object|array $tx): array
	{
		$output = [];

		foreach (['booking_details', 'contact_details'] as $section) {
			$values = is_object($tx)
				? ($tx->{$section} ?? [])
				: ($tx[$section] ?? []);
			$values = is_object($values) ? get_object_vars($values) : $values;

			if (! is_array($values)) {
				continue;
			}

			foreach ($values as $field => $value) {
				if (is_scalar($value)) {
					$output[$field] = $value;
				}
			}
		}

		return $output;
	}

	private function get_conversion_amount(): float
	{
		$value      = (float) dy_utilities::total();
		$raw        = dy_get_option('dy_bidding_conversion_percentage', '15'); // default 15
		$percentage = is_numeric($raw) ? max(1, min(100, (float) $raw)) : 15;

		return $value * ($percentage / 100);
	}

	private function queue_conversion_events(string $request_type, string $tx_id): void
	{
		$value = $this->get_conversion_amount();

		$currency = currency_name();

		$lead_gateways = array_unique(
			apply_filters(
				'dy_lead_event_gateways',
				array('estimate_request', 'contact')
			)
		);

		if(in_array($request_type, $lead_gateways, true))
		{
			dy_gtag_queue_server_event(
				'generate_lead',
				$tx_id,
				$value,
				$currency
			);
		}

		$purchase_gateways = array_unique(
			apply_filters('dy_purchase_event_gateways', [])
		);

		if(in_array($request_type, $purchase_gateways, true))
		{
			$item = dy_gtag_build_item(
				secure_post('dy_id', 0, 'absint'),
				secure_post('title'),
				secure_post('pax_num', 1, 'absint'),
				$value
			);

			dy_gtag_queue_server_event(
				'purchase',
				$tx_id,
				$value,
				$currency,
				array($item)
			);
		}
	}

    public function send_email(): void
    {

		$attachments = [];

		if(dy_validators::validate_quote())
		{
			$attachment_filename = apply_filters('dy_email_label_doc', __('Estimate', 'dynamicpackages')) . '.pdf';
			require_once $this->plugin_dir_path_dir . 'public/email-templates/estimates-pdf.php';
			$estimate = cloudflare_html_to_pdf($email_pdf, $attachment_filename);

			if(is_array($estimate)) {
				$attachments[$attachment_filename] = $estimate['pathname'];
			}
			
			$terms_html = $this->get_term_condition_as_html();

			if(is_array($terms_html))
			{
				if(count($terms_html) > 0)
				{
					for($x = 0; $x < count($terms_html); $x++)
					{
						$term_html = $terms_html[$x]['html'];
						$term_filename = $terms_html[$x]['filename'];
						$term_pdf = cloudflare_html_to_pdf($term_html, $term_filename);

						if(is_array($term_pdf)) {
							$attachments[$term_pdf['filename']] = $term_pdf['pathname'];
						}
						
					}
				}
			}

			require_once $this->plugin_dir_path_dir . 'public/email-templates/estimates.php';
			$message = $email_template;	
		}
		else
		{			
			$request = (!empty(dy_tx::request_value('inquiry'))) ? dy_tx::request_value('inquiry') : apply_filters('dy_description', '');
			$message = '<p>'.esc_html(apply_filters('dy_email_greeting', sprintf(__('Hello %s,', 'dynamicpackages'), dy_tx::request_value('first_name')))).'</p>';
			$message .= '<p>'.sprintf(__('Our staff will be in touch with you very soon with more information about your request: %s', 'dynamicpackages'), '<strong>'.esc_html($request).'</strong>').'</p>';
			
			if(dy_get_option('dy_phone') && dy_get_option('dy_email'))
			{
				$message .= '<p>'.esc_html(sprintf(__('Do not hesitate to call us at %s or email us at %s if you have any questions.', 'dynamicpackages'), esc_html(dy_get_option('dy_phone')), dy_sanitize_email((string) dy_get_option('dy_email')))).'</p>';
			}


			$phone = dy_tx::request_value('country_calling_code') . dy_tx::request_value('phone');
			$message .= '<p>'.esc_html(sprintf(__('When is a good time to call you at %s? Or do you prefer Whatsapp?', 'dynamicpackages'), $phone)).'</p>';			
		}
	

		$to = dy_tx::request_value('email');
		$subject = $this->subject();
		$body = $message;
		$headers = array('Content-Type: text/html; charset=UTF-8');

		wp_mail($to, $subject, $body, $headers,  $attachments);
    }
	
	public function subject(): string
	{
		if(dy_validators::validate_quote())
		{
			$output = sprintf(__('%s, %s has sent you an estimate for %s - %s', 'dynamicpackages'), dy_tx::request_value('first_name'), get_bloginfo('name'), wrap_money_full(dy_utilities::total()), secure_post('title'));			
		}
		else
		{
			global $post;
			
			$request = (isset($post->post_title)) ? $post->post_title : __('General Inquiry', 'dynamicpackages');
			$output = sprintf(__('%s, thanks for your request: %s', 'dynamicpackages'), dy_tx::request_value('first_name'), $request);	
		}

			
		return apply_filters('dy_email_subject', $output);
	}
	public function get_term_condition_as_html(): array
	{		
		$output = [];
		$terms_conditions = dy_get_taxonomies('package_terms_conditions');
		$Parsedown = new Parsedown();
		
		if(is_array($terms_conditions))
		{
			if(count($terms_conditions) > 0)
			{
				for($x = 0; $x < count($terms_conditions); $x++ )
				{
					$number = $x + 1;
					$name = $terms_conditions[$x]->name;
					
					//PAGE
					$html = sprintf(
						'<h1 style="text-align: center; margin: 0; padding: 0; font-size: 20pt;">%s</h1>%s',
						esc_html($name),
						$Parsedown->text($terms_conditions[$x]->description)
					);
					
					//PDF
					$filename = $name . '.pdf';
					
					$output[] = array("html"=> $html, "filename" => $filename);
				}		
			}
		}
		
		return $output;
	}	
	
}

?>
