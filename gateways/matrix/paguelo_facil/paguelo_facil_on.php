<?php

if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class paguelo_facil_on{
	
	private static $cache = [];
	private static $txt_status = null;

	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->id = 'paguelo_facil_on';

		add_action('dy_prepare_gateway_submission_' . $this->id, array($this, 'prepare_submission'));
		add_action('init', array($this, 'init'));
		add_action('admin_init', array($this, 'settings_init'), 1);
		add_action('admin_menu', array($this, 'add_settings_page'), 100);
		add_action('init', array($this, 'checkout'), 50);
		add_filter('dy_request_the_content', array($this, 'the_content'));
		add_filter('dy_request_the_title', array($this, 'the_title'));
		add_filter('dy_list_gateways', array($this, 'add_gateway'), 1);
		add_filter('dy_debug_instructions', array($this, 'debug_instructions'));
		add_filter('dy_purchase_event_gateways', array($this, 'purchase_event_gateways'));
	}
	

	public function init()
	{
		$this->order_status = 'paid';
		$this->restored_from_cache = false;
		
		$this->short_name = __('Paguelo Facil', 'dynamicpackages');
		$this->name = __('Paguelo Facil On-site', 'dynamicpackages');
		$this->type = 'card-on-site';
		$this->brands = ['Mastercard', 'Visa'];
		$this->cards_accepted = implode_last($this->brands, __('o', 'dynamicpackages'));
		$this->cclw = (string) get_option($this->id, '');
		$this->show = (int) get_option($this->id . '_show', 0);
		$this->min = (float) get_option($this->id . '_min', 0.0);
		$this->max = (float) get_option($this->id . '_max', 0.0);

		$this->color = '#fff';
		$this->background_color = '#262626';
		$this->dummy_cc = '4321502106746398';

		$debug_email = sanitize_email(
			(string) get_option($this->id . '_debug_email', '')
		);

		$this->debug_email = is_email($debug_email)
			? $debug_email
			: sanitize_email((string) get_option('admin_email', ''));


		$this->debug_mode = $this->debug();

		$this->production_url = 'https://secure.paguelofacil.com/rest/ccprocessing/';
		$this->sandbox_url = 'https://sandbox.paguelofacil.com/rest/ccprocessing/';
		$this->endpoint = ($this->debug_mode === -1)
			? $this->production_url
			: $this->sandbox_url;

		$this->plugin_dir_url = plugin_dir_url(__DIR__);
		$this->website_name = get_bloginfo('name');
		$this->icon = '<span class="dashicons dashicons-cart"></span>';
		$this->gateway_coupon = 'PAGUELOFACIL';
	}

	public function is_valid_cached_success($cached)
	{
		return (
			is_array($cached)
			&& isset($cached['sign'], $cached['status'])
			&& $cached['status'] === 2
			&& is_string($cached['sign'])
			&& hash_equals(
				$cached['sign'],
				$this->checkout_request_sign()
			)
		);
	}

	public function checkout_request_sign() {

		$cache_key = $this->id . '_checkout_request_sign_' . secure_post('unique_tx_id');

		if (array_key_exists($cache_key, self::$cache)) {
			return self::$cache[$cache_key];
		}

		$arr = [
			(string) secure_post('unique_tx_id'),
			(string) secure_post('dy_id'),
			strtolower((string) secure_post('email', '', 'sanitize_email')),
			(string) secure_post('booking_date'),
			(string) secure_post('booking_hour'),
			(string) secure_post('booking_extra'),
			(string) secure_post('pax_regular'),
			(string) secure_post('pax_discount'),
			(string) secure_post('pax_free'),
			(string) secure_post('pax_num'),
			(string) secure_post('transport_type'),
			(string) secure_post('route'),
			(string) secure_post('end_date'),
			(string) secure_post('return_hour'),
			(string) secure_post('coupon_code'),
			(string) secure_post('add_ons'),
			(string) secure_post('duration'),
			(string) currency_name(),
			(string) secure_post('cf-turnstile-response')
		];

		$text = implode('|', $arr);
		
		$hash = hash_hmac(
			'sha256',
			$text,
			wp_salt('auth')
		);

		return self::$cache[$cache_key] = $hash;
	}
	
	public function checkout()
	{

		if(secure_post('dy_request', '', 'sanitize_key') !== $this->id)
		{
			return true;
		}

		$unique_tx_id = secure_post('unique_tx_id');

		if(!dy_validators::validate_unique_tx_id($unique_tx_id))
		{
			return true;
		}

		$cached_success = get_transient('success_' . $unique_tx_id);

		if($cached_success !== false)
		{
			$this->skip_generic_submission();
			$this->restore_cached_success($cached_success);

			return true;
		}

		$amount = (float) dy_utilities::payment_amount();

		if(!$this->is_payment_amount_allowed($amount))
		{
			$GLOBALS['dy_request_invalids'] = array(
				__('Payment amount is outside the limits allowed by the selected gateway.', 'dynamicpackages')
			);

			$this->skip_generic_submission();

			return true;
		}

		if(dy_validators::validate_checkout($this->id) === false || validate_turnstile() === false) {
			return true;
		}

		$transient_is_processing_value = get_transient('is_processing_' . $unique_tx_id); //returns false if not found

		if($transient_is_processing_value === 'is_processing')
		{
			$this->skip_generic_submission();

			$cached_success = get_transient('success_' . $unique_tx_id);

			if($cached_success === false)
			{
				self::$txt_status = 0;
			}
			else
			{
				$this->restore_cached_success($cached_success);
			}

			return true;
		}

		set_transient('is_processing_' . $unique_tx_id, 'is_processing', 300);


		self::$txt_status = $this->resolve_checkout_status();

		if(isset($this->error_codes))
		{
			write_log($this->error_codes);
		}

		if(self::$txt_status === 2 && $this->debug_mode === -1)
		{

			$success_args = [
				'sign' => $this->checkout_request_sign(),
				'status' => 2
			];

			set_transient('success_' . $unique_tx_id, $success_args, DAY_IN_SECONDS);
		}

		delete_transient('is_processing_' . $unique_tx_id);

		return true;
	}

	public function purchase_event_gateways($gateways = array())
	{
		if(
			self::$txt_status === 2
			&& $this->debug_mode === -1
			&& !$this->restored_from_cache
		)
		{
			$gateways[] = $this->id;
		}

		return $gateways;
	}


	private function skip_generic_submission()
	{
		add_filter(
			'dy_skip_generic_form_submission',
			'__return_true',
			PHP_INT_MAX
		);
	}

	private function restore_cached_success($cached)
	{
		if(!$this->is_valid_cached_success($cached))
		{
			write_log('Gateway: cached transaction signature mismatch.');
			return;
		}

		self::$txt_status = 2;
		$this->restored_from_cache = true;

	}

	private function resolve_checkout_status()
	{
		if($this->debug_mode >= 0 && $this->debug_mode !== 3)
		{
			return $this->debug_mode;
		}

		$response = $this->process_request();

		if(!is_array($response))
		{
			$this->error_codes = array(
				'error' => 'invalid_response_format'
			);

			return 0;
		}

		if(array_key_exists('error', $response))
		{
			$this->error_codes = array(
				'error' => (string) $response['error']
			);

			return 0;
		}

		$status = isset($response['Status'])
			? (string) $response['Status']
			: '';

		if($status === 'Approved')
		{
			return 2;
		}

		if($status === 'Declined')
		{
			$this->error_codes = array(
				'RespText' => (string) ($response['RespText'] ?? ''),
				'RespCode' => (string) ($response['RespCode'] ?? '')
			);

			return 1;
		}

		$this->error_codes = array(
			'error' => 'unexpected_gateway_status'
		);

		return 0;
	}

	public function prepare_submission($submission_context)
	{
		if(self::$txt_status === null)
		{
			return;
		}

		$submission_context->accepted = true;

		add_filter('dy_email_message', array($this, 'message'));
		add_filter('dy_email_message', array($this, 'email_message_bottom'));
		add_filter('dy_email_subject', array($this, 'subject'));
		add_filter('dy_email_intro', array($this, 'intro'));
		add_filter('dy_email_notes', array($this, 'email_notes'));
		add_filter('dy_order_status', function(){
			return $this->order_status;
		});

		if(self::$txt_status === 2)
		{
			add_filter('dy_totals_area', array($this, 'totals_area'));

			add_filter('dy_webhook_option', function(){
				return 'dy_webhook';
			});

			add_filter('dy_confirmation_message', array($this, 'confirmation_message'));
			add_filter('dy_email_label_doc', function(){
				return esc_html(__('Invoice', 'dynamicpackages'));
			});
		}
		else
		{
			add_filter('dy_fail_checkout_gateway_name', function(){
				return $this->id;
			});
		}
	}
	
	public function subject($output)
	{		
		if(self::$txt_status !== null)
		{
			$first_name = secure_post('first_name');
			$title = secure_post('title');
			$payment_amount = dy_utilities::payment_amount();
			
			if(self::$txt_status === 2)
			{
				$payment = (dy_validators::has_deposit()) ? __('Deposit', 'dynamicpackages') : __('Payment', 'dynamicpackages');
				$output = '✔️ ' . sprintf(__('%s, Thank You for Your %s of %s: %s', 'dynamicpackages'), $first_name, $payment, wrap_money_full($payment_amount), $title);
			}
			else if(self::$txt_status === 1)
			{
				$output = '⚠️ ' . sprintf(__('%s, Your Payment to %s for %s was Declined', 'dynamicpackages'), $first_name, $this->website_name, wrap_money_full($payment_amount)) . ' ⚠️';
			}
			else if(self::$txt_status === 0)
			{
				$output = '⚠️ ' . sprintf(__('%s, %s is having problems processing your payment', 'dynamicpackages'), $first_name, $this->website_name) . ' ⚠️';
			}
		}
		
		return $output;
	}

	public function intro($output)
	{		
		if(self::$txt_status !== null)
		{
			$title = secure_post('title');
			$payment_amount = dy_utilities::payment_amount();
			
			if(self::$txt_status === 2)
			{
				$payment = (dy_validators::has_deposit()) ? __('Deposit', 'dynamicpackages') : __('Payment', 'dynamicpackages');
				$output = '✔️ ' . sprintf(__('Thank You for Your %s of %s: %s', 'dynamicpackages'), $payment, wrap_money_full($payment_amount), $title);
			}
			else if(self::$txt_status === 1)
			{
				$output = '⚠️ ' . sprintf(__('Your Payment to %s for %s was Declined', 'dynamicpackages'), $this->website_name, wrap_money_full($payment_amount)) . ' ⚠️';
			}
			else if(self::$txt_status === 0)
			{
				$output = '⚠️ ' . sprintf(__('%s is having problems processing your payment', 'dynamicpackages'), $this->website_name) . ' ⚠️';
			}
		}
		
		return $output;
	}
	
	public function email_message_bottom($output)
	{
		if(self::$txt_status !== null)
		{
			if(self::$txt_status === 2)
			{
				$terms_conditions = dy_utilities::get_taxonomies('package_terms_conditions');
				
				if(is_array($terms_conditions))
				{
					if(count($terms_conditions) > 0)
					{
						$output .= '<p>🗎 ' . __('The Terms & Conditions you accepted are attached to this email.', 'dynamicpackages') . '</p>';
					}
				}
				
				$output .= '<p>'. __('Take your time to review our invoice in detail.', 'dynamicpackages') . '</p>';
			}
		}
		
		return $output;
	}
	
	public function message($output)
	{
		if(self::$txt_status !== null)
		{
			if(self::$txt_status === 2)
			{	
				$output = '<p>⚠️ ' . __('To complete this reservation we require images of the passports (foreigners) or valid Identity Documents (nationals) of each participant. The documents you send will be compared against the originals at the meeting point.', 'dynamicpackages') . '</p>';
				$output .= $this->email_notes(null);
				$output .= '<p>❌ '. __('It is not allowed to book for third parties.', 'dynamicpackages') . '</p>';
			}
			else if(self::$txt_status === 1)
			{
				$output = '<p>☎️ ' . esc_html(__('Please contact your bank to authorize the transaction.', 'dynamicpackages')) . ' ☎️</p>';
				$output .= $this->get_errors();
			}
			else
			{
				$output = '<p>' . esc_html(__('Please try again in a few minutes. Our staff will be in touch with you very soon.', 'dynamicpackages')) . '</p>';
				$output .= $this->get_errors();
			}
		}
		
		return $output;
	}	

	public function is_active()
	{
		$output = false;
		$cache_key = $this->id . '_is_active';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if(!empty($this->cclw))
		{
			$output = true;
		}

        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	public function show()
	{
		$output = false;
		$cache_key = $this->id . '_show';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }
		
		if(is_singular('packages') && $this->is_active())
		{
			if($this->is_valid())
			{
				$output = true;
			}
		}
		
        //store output in $cache
        self::$cache[$cache_key] = $output;

		return $output;
	}
	public function is_request_submitted()
	{
		$output = false;
		$cache_key = $this->id . '_is_valid_request';

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		global $dy_request_invalids;

		if(is_confirmation_page() && ($this->restored_from_cache || !isset($dy_request_invalids)))
		{
			if( secure_post('dy_request') === $this->id && ($this->restored_from_cache || self::$txt_status !== null))
			{
				$output = true;
			}
		}


		return self::$cache[$cache_key] = $output;
	}
	
	public function the_content($output)
	{
		if(self::$txt_status !== null && in_the_loop() && $this->is_request_submitted())
		{
			if(self::$txt_status === 2)
			{
				$payment = (dy_validators::has_deposit()) ? __('deposit', 'dynamicpackages') : __('payment', 'dynamicpackages');
				
				$output = '<p class="minimal_success strong"><span class="dashicons dashicons-yes"></span> ' . sprintf(__('Thank you for your %s of %s.', 'dynamicpackages'), $payment, wrap_money_full(dy_utilities::payment_amount())) . '</p>';
				
				$output .= '<div class="bottom-20">' . apply_filters('dy_description', null) . '</div>';
				$output .= '<div class="bottom-20">' . $this->message(null) . '</div>';
				
				$output .= '<p class="minimal_success strong"><span class="dashicons dashicons-email"></span> '.esc_html(sprintf(__('We have sent you an email to %s with more details and the confirmation of this booking.', 'dynamicpackages'), secure_post('email', '', 'sanitize_email'))).'</p>';
				
				$add_to_calendar = apply_filters('dy_add_to_calendar', null);
				
				if($add_to_calendar)
				{
					$output .= '<div class="text-center">'. $add_to_calendar .'</div>';
				}					
			}
			else if(self::$txt_status === 1)
			{
				$output = '<p class="minimal_alert strong">' . esc_html(__('Please contact your bank to authorize the transaction.', 'dynamicpackages')) . '</p>';
				$output .= $this->get_errors();
			}
			else
			{
				$output = '<p class="minimal_alert strong">' . esc_html(__('Please try again in a few minutes. Our staff will be in touch with you very soon.', 'dynamicpackages')) . '</p>';
				$output .= $this->get_errors();
			}
		}
		return $output;
	}
		
		
	public function get_errors()
	{
		$output = '';

		if(isset($this->error_codes))
		{
			foreach($this->error_codes as $k => $v)
			{
				$output .= '<p class="minimal_alert strong">'.$k .': '.$v.'</p>';
			}			
		}
		
		return $output;
	}		
		
	public function the_title($output)
	{
		if(self::$txt_status !== null && in_the_loop() && $this->is_request_submitted())
		{
			if(self::$txt_status === 2)
			{
				$output = __('Payment Approved', 'dynamicpackages');
			}
			else if(self::$txt_status === 1)
			{
				$output = __('Payment Declined', 'dynamicpackages');
			}
			else
			{
				$output = __('Checkout Error', 'dynamicpackages');
			}
		}
		return $output;
	}

	public function email_notes($output)
	{
		if(self::$txt_status !== null)
		{
			if(self::$txt_status === 2)
			{
				$output = '';
				$message = package_field('package_confirmation_message');
				$details = apply_filters('dy_details', false); 
				$output .= ($details) ? $details : null;
				$output .= ($message) ? '<br/><br/>' . esc_html($message) : null;				
			}
		}
		
		return $output;
	}

	

	public function is_valid()
	{
		$output = false;
		$cache_key = $this->id . '_is_valid';
		
        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

		if($this->is_active() )
		{
			$payment = (int) package_field('package_payment');
			
			if(is_booking_page() || is_confirmation_page())
			{
				$total = (float) dy_utilities::payment_amount();
			}
			else
			{
				$total = (float) dy_utilities::starting_at();
				$fixed_price = (int) package_field('package_fixed_price');
				$max_persons = (int) package_field('package_max_persons');
				
				if($fixed_price === 0)
				{
					$total = $total * $max_persons;
				}
			}
			
			if($this->is_payment_amount_allowed($total))
			{
				if($payment === $this->show && $payment === 0)
				{
					$output = true;
				}
				else
				{
					if(dy_validators::has_deposit())
					{
						$output = true;
					}
				}
			}
		}
		
		//store output in $cache
		self::$cache[$cache_key] = $output;

		return $output;
	}

	public function settings_init()
	{
		register_setting($this->id . '_settings', $this->id, 'sanitize_text_field');
		register_setting($this->id . '_settings', $this->id . '_min', 'floatval');
		register_setting($this->id . '_settings', $this->id . '_max', 'floatval');
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_debug_email', 'sanitize_email');
		
		add_settings_section(
			$this->id . '_control_section', 
			__( 'General Settings', 'dynamicpackages' ), 
			'', 
			$this->id . '_settings'
		);		
		
		add_settings_section(
			$this->id . '_settings_section', 
			sprintf(__( '%s Settings', 'dynamicpackages' ), $this->name), 
			'', 
			$this->id . '_settings'
		);
				
		add_settings_field( 
			$this->id, 
			__( 'CCLW', 'dynamicpackages' ), 
			['dy_input_option', 'text'], 
			$this->id . '_settings', 
			$this->id . '_settings_section', 
			[
				'key' => $this->id
			]
		);

		add_settings_field( 
			$this->id . '_min', 
			__( 'Min. Amount', 'dynamicpackages' ), 
			['dy_input_option', 'price'], 
			$this->id . '_settings', 
			$this->id . '_control_section', 
			[
				'key' => $this->id . '_min',
				'append' => currency_symbol(),
			]
		);

		add_settings_field( 
			$this->id . '_max', 
			__( 'Max. Amount', 'dynamicpackages' ), 
			['dy_input_option', 'price']	, 
			$this->id . '_settings', 
			$this->id . '_control_section', 
			[
				'key' => $this->id . '_max',
				'append' => currency_symbol(),
			]
		);
		

		add_settings_field( 
			$this->id . '_show', 
			__( 'Show', 'dynamicpackages' ), 
			['dy_select_option', 'custom'], 
			$this->id . '_settings', 
			$this->id . '_control_section',
			[
				'key' => $this->id . '_show',
				'options' => [
					0 => __('Full Payments and Deposits', 'dynamicpackages'),
					1 => __('Only Deposits', 'dynamicpackages'),
				]
			]
		);
		
		add_settings_field( 
			$this->id . '_debug_email', 
			__( 'Debug Email', 'dynamicpackages' ), 
			['dy_input_option', 'email'],
			$this->id . '_settings', 
			$this->id . '_control_section', 
			[
				'key' => $this->id . '_debug_email'
			]
		);
	}

	public function add_settings_page()
	{
		add_submenu_page( $this->plugin_id, $this->name, '💸 '. $this->short_name, 'manage_options', $this->id, array($this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html_e($this->name); ?></h1>	
			<?php echo $this->debug_instructions(); ?>
			<?php
			settings_fields( $this->id . '_settings' );
			do_settings_sections( $this->id . '_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}

	public function add_gateway($array)
	{
		
		$add = false;

		if($this->show())
		{
			if(is_singular('packages'))
			{
				$add = true;
			}

			$request_type = secure_post('dy_request', '', 'sanitize_key');
			$failed_gateway = apply_filters('dy_fail_checkout_gateway_name', null);
			
			if(
				in_array(
					$request_type,
					array('estimate_request', $failed_gateway),
					true
				)
				&& is_confirmation_page()
				&& dy_validators::validate_request()
				&& validate_turnstile()
			)
			{
				$add = true;
			}	
		}
		
		if($add)
		{
            $array[$this->id] = array(
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'color' => $this->color,
                'background_color' => $this->background_color,
				'brands' => $this->brands,
				'branding' => $this->branding(),
				'icon' => $this->icon,
				'gateway_coupon' => $this->gateway_coupon
            );
		}
		
		return $array;	
	}
	
	public function totals_area($output)
	{
		if(dy_validators::has_deposit())
		{
			$outstanding = wrap_money_full(dy_utilities::outstanding_amount());
			$total =  wrap_money_full(dy_utilities::payment_amount());
			$date = secure_post('booking_date');
			
			$output .= '<br/><strong style="color: #666666;">'.__('Paid', 'dynamicpackages').'<br/><span class="sm-hide">('.$date.')</span></strong><br/> -'.$total;
			$output .= '<br/><strong style="color: #666666;">'.__('Amount Due', 'dynamicpackages').'</strong><br/> '.$outstanding;
		}
		
		return $output;
	}
	
	public function build_request()
	{
		// IP con preferencia a Cloudflare si existe
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}

		// Datos principales
		$CCNum  = secure_post('CCNum');
		$CVV2   = secure_post('CVV2');
		$email  = secure_post('email');
		$phone  = secure_post('country_calling_code') . secure_post('phone');

		// Hash secreto
		$hash = $CCNum . $CVV2 . $email;

		// Armar payload
		return [
			'CCLW'       => $this->cclw,
			'txType'     => 'SALE',
			'CMTN'       => round(dy_utilities::payment_amount(), 2),
			'CDSC'       => substr(apply_filters('dy_description', null), 0, 150),
			'CCNum'      => $CCNum,
			'ExpMonth'   => secure_post('ExpMonth'),
			'ExpYear'    => secure_post('ExpYear'),
			'CVV2'       => $CVV2,
			'Name'       => secure_post('first_name'),
			'LastName'   => secure_post('lastname'),
			'Email'      => $email,
			'Address'    => secure_post('address'),
			'Tel'        => $phone,
			'Ip'         => $ip,
			'SecretHash' => hash('sha512', $hash),
		];
	}

	
	public function process_request()
	{
		$params = http_build_query($this->build_request());
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $this->endpoint);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Accept: */*'));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		$result = curl_exec($ch);

		if($result === false) {
			$curl_error = curl_error($ch);
			curl_close($ch);
			return array("error" => 'curl_error: ' . $curl_error);
		}

		$decoded_result = json_decode($result, true);
		curl_close($ch);

		return $decoded_result;
	}

	public function debug()
	{
		if(
			post_has('CCNum')
			&& post_has('CVV2')
			&& post_has('email')
			&& secure_post('CCNum') === $this->dummy_cc
			&& $this->user_can_debug()
			&& secure_post('email') === $this->debug_email
		)
		{
			if(secure_post('CVV2') === '222')
			{
				return 2;
			}

			if(secure_post('CVV2') === '111')
			{
				return 1;
			}

			if(secure_post('CVV2') === '000')
			{
				return 0;
			}

			return 3;
		}

		return -1;
	}
	
	public function user_can_debug()
	{
		$output = false;
		
		if(is_user_logged_in())
		{
			if(current_user_can('editor') || current_user_can('administrator'))
			{
				$output = true;
			}
		}
		
		return $output;
	}
	
	
		
	public function debug_instructions()
	{
		if($this->user_can_debug())
		{
			return '<p style="line-height: 2; color: #696969; background-color: #ADD8E6; padding: 10px;">🤖 '.sprintf(__('Use the card %s together with the email %s to test Paguelo Facil Development Enviroment. Use the CVV code 222 to generate approved transactions, 111 to generate declined transaction and 000 to generate errors and any other number will retreive Paguelo Facil original response.', 'dynamicpackages'), '<strong>'.esc_html($this->dummy_cc).'</strong>', '<strong>'.esc_html($this->debug_email).'</strong>').'</p>';			
		}
	}

	public function confirmation_message()
	{
		
		global $post;
		$output = '';

		if($post instanceof WP_Post)
		{
			$the_id = $post->ID;
			
			if(property_exists($post, 'post_parent'))
			{
				$the_id = $post->post_parent;
			}
			
			$languages = get_languages();

			for($x = 0; $x < count($languages); $x++)
			{
				$lang = $languages[$x];
				$value = package_field('package_confirmation_message_'.$lang, $the_id);

				if($value)
				{
					$Parsedown = new Parsedown();
					$output = $Parsedown->text($value);
				}
			}
		}


		return $output;
	}

	public function branding()
	{
		$output = '<p><img src="'.esc_url($this->plugin_dir_url.'assets/visa-mastercard.svg').'" width="250" height="50" /></p>';
		$output .= '<p class="large text-muted">'.sprintf(__('Pay with %s thanks to %s', 'dynamicpackages'), $this->cards_accepted, $this->short_name).'</p>';
		return $output;
	}

	private function has_valid_limits()
	{
		return (
			$this->min > 0.0
			&& $this->max > 0.0
			&& $this->max >= $this->min
		);
	}

	private function is_payment_amount_allowed($amount) {

		if (!$this->has_valid_limits()) {
			return false;
		}

		$amount = round((float) $amount, 2);
		$min    = round((float) $this->min, 2);
		$max    = round((float) $this->max, 2);

		return $amount >= $min && $amount <= $max;
	}
	
}