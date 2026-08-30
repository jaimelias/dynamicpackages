<?php


if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamicpackages_Actions{

	private $submission_is_valid = null;
	private $data_sent = false;
	private $plugin_dir_path_dir;

	public function __construct()
	{
		$this->plugin_dir_path_dir = plugin_dir_path(__DIR__);

		add_action('template_redirect', array($this, 'send_data'), 20);
		add_filter('the_content', array($this, 'the_content'), 101);
		add_filter('pre_get_document_title', array($this, 'wp_title'), 101);
		add_filter('the_title', array($this, 'the_title'), 101);
		add_filter('get_the_excerpt', array($this, 'modify_excerpt'));
	}

	public function is_request_submitted()
	{
		if(apply_filters('dy_skip_generic_form_submission', false))
		{
			return false;
		}

		if(
			strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST'
			|| !is_confirmation_page()
		)
		{
			return false;
		}

		global $post;

		if(is_singular('packages'))
		{
			return true;
		}

		return (
			$post instanceof WP_Post
			&& has_shortcode($post->post_content, 'package_contact')
		);
	}

	public function is_valid_submission()
	{
		if($this->submission_is_valid !== null)
		{
			return $this->submission_is_valid;
		}

		/*
		* Cheap/local checks run before the remote Turnstile request.
		*/
		$this->submission_is_valid = (
			$this->is_request_submitted()
			&& dy_validators::validate_request()
			&& validate_turnstile()
		);

		return $this->submission_is_valid;
	}

    public function send_data()
    {
		if($this->data_sent)
		{
			return true;
		}

		if(!$this->is_valid_submission())
		{
			return false;
		}

		$request_type = secure_post('dy_request', '', 'sanitize_key');

		$submission_context = (object) array(
			'accepted' => in_array(
				$request_type,
				array('estimate_request', 'contact'),
				true
			)
		);

		if($request_type !== '')
		{
			do_action(
				'dy_prepare_gateway_submission_' . $request_type,
				$submission_context
			);
		}

		if(!$submission_context->accepted)
		{
			return false;
		}

		$this->data_sent = true;

		$unique_tx_id = secure_post('unique_tx_id');
		$cache_key = 'dy_send_data_' . $unique_tx_id;

		if(get_transient($cache_key) === 'sent')
		{
			return true;
		}

		$the_id = get_dy_id();

		if(request_has('add_ons'))
		{
			$add_ons_package_id = sanitize_key('dy_add_ons_' . $the_id);
			$add_ons = secure_request('add_ons');
			setcookie($add_ons_package_id, $add_ons, time() + 3600);
		}
		
		$data = $_POST;
		unset($data['CCNum']);
		unset($data['ExpMonth']);
		unset($data['ExpYear']);
		unset($data['CVV2']);
		unset($data['cf-turnstile-response']);
		unset($data['dy_nonce']);

		//only in development
		//global $dy_orders;
		//$dy_orders->save_order($data);

		//write_log(json_encode($data));

		$by_hour = package_field('package_by_hour');
		$start_hour = package_field('package_start_hour');
		$return_hour = package_field('package_return_hour');
		$invertHours = $by_hour === '0' && $start_hour !== '' && $return_hour !== '';

		if($invertHours && array_key_exists('package_type', $data) && array_key_exists('route', $data))
		{
			if($data['package_type'] === 'transport' && $data['route'] === '1')
			{
				if(array_key_exists('booking_hour', $data) && array_key_exists('return_hour', $data))
				{
					list($data['booking_hour'], $data['return_hour']) = [$data['return_hour'], $data['booking_hour']];
				}
				
			}
		}

		$data['disabled_dates_api'] = package_field('package_disabled_dates_api', $the_id);

		$webhook_option = apply_filters('dy_webhook_option', 'dy_quote_webhook');
		$webhook_args = $data;
		$webhook_args['providers'] = apply_filters('dy_list_providers', array());
		$webhook_args['add_ons'] = apply_filters('dy_included_add_ons_arr', array());

		$payload = wp_json_encode($webhook_args);

		$this->queue_conversion_events(
			$request_type,
			$unique_tx_id
		);

		dy_utilities::webhook($webhook_option, $payload);
		$this->send_email();

		set_transient($cache_key, 'sent', DAY_IN_SECONDS);

		return true;
    }

	private function get_conversion_amount()
	{
		$value      = (float) dy_utilities::total();
		$raw        = get_option('dy_bidding_conversion_percentage', 15); // default 15
		$percentage = is_numeric($raw) ? max(1, min(100, (float) $raw)) : 15;

		return $value * ($percentage / 100);
	}

	private function queue_conversion_events($request_type, $unique_tx_id)
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
				$unique_tx_id,
				$value,
				$currency
			);
		}

		$purchase_gateways = array_unique(
			apply_filters('dy_purchase_event_gateways', array())
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
				$unique_tx_id,
				$value,
				$currency,
				array($item)
			);
		}
	}


    public function the_content($content)
    {
		$request_type = secure_post('dy_request', '', 'sanitize_key');
	
        if($this->data_sent && in_array($request_type, array('estimate_request', 'contact'), true))
        {               
			$content = '<p class="minimal_success strong">'.esc_html( __('Thank you for contacting us. Our staff will be in touch with you soon.', 'dynamicpackages')).'</p>';
        }

        return apply_filters('dy_request_the_content', $content);
    }

    public function send_email()
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
			$request = (!empty(secure_post('inquiry'))) ?  secure_post('inquiry') : apply_filters('dy_description', null);
			$message = '<p>'.esc_html(apply_filters('dy_email_greeting', sprintf(__('Hello %s,', 'dynamicpackages'), secure_post('first_name')))).'</p>';
			$message .= '<p>'.sprintf(__('Our staff will be in touch with you very soon with more information about your request: %s', 'dynamicpackages'), '<strong>'.esc_html($request).'</strong>').'</p>';
			
			if(get_option('dy_phone') && get_option('dy_email'))
			{
				$message .= '<p>'.esc_html(sprintf(__('Do not hesitate to call us at %s or email us at %s if you have any questions.', 'dynamicpackages'), esc_html(get_option('dy_phone')), sanitize_email(get_option('dy_email')))).'</p>';
			}


			$phone = secure_post('country_calling_code').secure_post('phone');
			$message .= '<p>'.esc_html(sprintf(__('When is a good time to call you at %s? Or do you prefer Whatsapp?', 'dynamicpackages'), $phone)).'</p>';			
		}
	

		$to = secure_post('email', '', 'sanitize_email');
		$subject = $this->subject();
		$body = $message;
		$headers = array('Content-Type: text/html; charset=UTF-8');

		wp_mail($to, $subject, $body, $headers,  $attachments);
    }
	
	public function subject()
	{
		if(dy_validators::validate_quote())
		{
			$output = sprintf(__('%s, %s has sent you an estimate for %s - %s', 'dynamicpackages'), secure_post('first_name'), get_bloginfo('name'), wrap_money_full(dy_utilities::total()), secure_post('title'));			
		}
		else
		{
			global $post;
			
			$request = (isset($post->post_title)) ? $post->post_title : __('General Inquiry', 'dynamicpackages');
			$output = sprintf(__('%s, thanks for your request: %s', 'dynamicpackages'), secure_post('first_name'), $request);	
		}

			
		return apply_filters('dy_email_subject', $output);
	}

    public function wp_title($title)
    {
        if($this->is_request_submitted())
        {
			$title = esc_html(__('Thank You for Your Request', 'dynamicpackages')).' | '.esc_html(get_bloginfo( 'name' ));
        }

        return $title;
    }
	
	public function modify_excerpt($excerpt)
	{
        if($this->is_request_submitted())
        {
			$excerpt = apply_filters('dy_description', null);
        }

        return $excerpt;
	}

    public function the_title($title)
    {	
		if(in_the_loop() && $this->is_request_submitted())
		{
			$title = esc_html(__('Thank You for Your Request', 'dynamicpackages'));
		}

        return apply_filters('dy_request_the_title', $title);
    }
	
	public function get_term_condition_as_html()
	{		
		$output = [];
		$terms_conditions = dy_utilities::get_taxonomies('package_terms_conditions');
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
					$html = '<style type="text/css">p{line-height: 1.25;}ul{line-height: 1.25;}ol{line-height: 1.25;}</style>';
					$html .= '<page backcolor="#ffffff" style="font-size: 12pt;" backtop="10mm" backbottom="10mm" backleft="10mm" backright="10mm">';
					$html .= '<h1 style="text-align: center; margin: 0; padding: 0; font-size: 20pt;">'.esc_html($name).'</h1>';
					$html .= $Parsedown->text($terms_conditions[$x]->description);
					$html .= '</page>';		
					
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