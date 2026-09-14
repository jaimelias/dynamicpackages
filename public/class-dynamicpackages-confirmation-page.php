<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Confirmation_Page {

    public function __construct($version)
    {
        $this->version = $version;
        $this->plugin_dir_url_file = plugin_dir_url( __FILE__ );

        //fix bug
        add_action('init', array($this, 'set_post_on_checkout_page'));

        //scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function set_post_on_checkout_page(): void
    {
        if (
            secure_server('REQUEST_METHOD') !== 'POST'
            || is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
        ) {
            return;
        }


		$unique_tx_id = secure_post('unique_tx_id');

		if (! is_string($unique_tx_id) || $unique_tx_id === '') {
			return;
		}

		$transaction = DyTransactions::get(
			$unique_tx_id
		);

		if (
			$transaction === null
			|| ! DyTransactions::validate(
				$unique_tx_id,
				[
					$unique_tx_id,
					(string) secure_post('email', '', 'sanitize_email'),
					(string) secure_post('dy_request', '', 'sanitize_key'),
					(int) secure_post('dy_id', 0, 'absint'),
				]
			)
		) {
			return;
		}

		$dy_request = sanitize_key((string) ($transaction->dy_request ?? ''));
		$dy_id = absint($transaction->dy_id ?? 0);
		$all_dy_request_types = dy_utilities::all_dy_request_types();

		if (! in_array($dy_request, $all_dy_request_types, true)) {
			return;
		}

		if ($dy_id <= 0) {
			return;
		}

		$this->set_request_value('dy_request', $dy_request);
		$this->set_request_value('dy_id', $dy_id);

		if (($transaction->status ?? '') === 'success') {
			$this->hydrate_successful_transaction($transaction);
		}

        //do not use "global $post", use $GLOBALS['post'] for the guard and a separate local variable. 
        $current_post = $GLOBALS['post'] ?? null;

        if ($current_post instanceof WP_Post) {
            if ((int) $current_post->ID !== $dy_id) {
                write_log(
                    sprintf(
                        'Existing post %d does not match submitted post %d.',
                        (int) $current_post->ID,
                        $dy_id
                    ),
                    false,
                    false,
                    'WARNING'
                );

                wp_die(
                    'Invalid package request.',
                    '',
                    ['response' => 400]
                );
            }

            return;
        }

        $requested_post = get_post($dy_id);

        if (
            !($requested_post instanceof WP_Post)
            || $requested_post->post_type !== 'packages'
            || $requested_post->post_status !== 'publish'
        ) {
            return;
        }

		$GLOBALS['post'] = $requested_post;
	}

	private function hydrate_successful_transaction(object $transaction): void
	{
		$sections = [
			'booking_details' => [
				'pax_regular',
				'pax_discount',
				'pax_free',
				'transport_type',
				'route',
				'start_date',
				'start_hour',
				'end_date',
				'end_hour',
				'additional_time',
				'coupon_code',
				'force_availability',
			],
			'contact_details' => [
				'first_name',
				'lastname',
				'phone',
				'country_calling_code',
				'email',
				'repeat_email',
				'inquiry',
			],
		];

		foreach ($sections as $section => $fields) {
			$values = $transaction->{$section} ?? null;
			$values = is_object($values) ? get_object_vars($values) : (is_array($values) ? $values : []);

			foreach ($fields as $field) {
				if (! array_key_exists($field, $values) || ! is_scalar($values[$field])) {
					continue;
				}

				$this->set_request_value($field, $values[$field]);
			}
		}
	}

	private function set_request_value(string $key, string|int|float|bool $value): void
	{
		$_POST[$key] = $value;
		$_REQUEST[$key] = $value;
	}

    public function enqueue_scripts()
    {
        if(is_confirmation_page())
        {
			$strings = array(
				'textCopiedToClipBoard' => __('Copied to Clipboard!', 'dynamicpackages')
			);

            wp_enqueue_script('dynamicpackages-confirmation', $this->plugin_dir_url_file . 'js/dynamicpackages-confirmation-page.js', array( 'jquery'), $this->version, true );
			wp_localize_script('dynamicpackages-confirmation', 'dyPackageConfirmationArgs', $strings);
        }
    }
}

?>
