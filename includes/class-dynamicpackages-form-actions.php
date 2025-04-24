<?php


if ( !defined( 'WPINC' ) ) exit;

use Spipu\Html2Pdf\Html2Pdf;

#[AllowDynamicProperties]
class Dynamicpackages_Actions{

	private static $cache = [];

    public function __construct()
    {
		$this->valid_recaptcha = validate_recaptcha();
		add_action('wp', array(&$this, 'args'));
        add_filter('wp', array(&$this, 'send_data'), 100);
        add_filter('the_content', array(&$this, 'the_content'), 101);
        add_filter( 'pre_get_document_title', array(&$this, 'wp_title'), 101);
        add_filter( 'the_title', array(&$this, 'the_title'), 101);
		add_filter('get_the_excerpt', array(&$this, 'modify_excerpt'));
    }

	public function args()
	{
		$this->current_language = current_language();
		$this->plugin_dir_path_dir = plugin_dir_path(__DIR__);
		
	}

	public function is_request_submitted()
	{
		global $post;
		$output = false;
		
        if(is_checkout_page())
        {
			if($this->valid_recaptcha)
			{
				if(is_singular('packages'))
				{
					$output = true;
				}
				else
				{
					if(is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'package_contact'))
					{
						$output = true;
					}
				}
			}
        }

        return $output;
	}

    public function send_data()
    {
		$cache_key = 'dy_send_data';

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

		if($this->is_request_submitted())
		{
			if(dy_validators::validate_request())
			{

				$the_id = get_dy_id();

				if(isset($_REQUEST['add_ons']))
				{
					$add_ons_package_id = sanitize_key('dy_add_ons_' . $the_id);
					$add_ons = sanitize_text_field($_REQUEST['add_ons']);
					setcookie($add_ons_package_id, $add_ons, time() + 3600);
				}
				
				

				$data = $_POST;
				unset($data['CCNum']);
				unset($data['ExpMonth']);
				unset($data['ExpYear']);
				unset($data['CVV2']);
				unset($data['g-recaptcha-response']);
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

				$payload = json_encode($webhook_args);

				dy_utilities::webhook($webhook_option, $payload);
				$this->send_email();

				//store output in $cache
				self::$cache[$cache_key] = true;
			}
		}
    }
    public function the_content($content)
    {
        if($this->is_request_submitted())
        {               
            if(dy_validators::validate_request())
            {				
				if($_POST['dy_request'] == 'estimate_request' || $_POST['dy_request'] == 'contact')
				{
					$content = '<p class="minimal_success strong">'.esc_html( __('Thank you for contacting us. Our staff will be in touch with you soon.', 'dynamicpackages')).'</p>';
				}              
            }
        }

        return apply_filters('dy_request_the_content', $content);
    }

	public function doc_pdf($html, $filename)
	{
		$temp_path = wp_upload_dir()['basedir'];
		$temp_filename = '/temp_' . uniqid() .'.pdf';
		$doc_pdf = new Html2Pdf('P', 'A4');
		$doc_pdf->pdf->SetDisplayMode('fullpage');
		$doc_pdf->writeHTML($html);
		$pdf_path = $temp_path . $temp_filename;
		$doc_pdf->Output($pdf_path, 'F');

		return array("filename" => $filename, "pathname" => $pdf_path);
	}

    public function send_email()
    {

		$attachments = array();

		if(dy_validators::validate_quote())
		{
			$attachment_filename = apply_filters('dy_email_label_doc', __('Estimate', 'dynamicpackages')) . '.pdf';
			require_once $this->plugin_dir_path_dir . 'public/email-templates/estimates-pdf.php';
			$estimate = $this->doc_pdf($email_pdf, $attachment_filename);
			$attachments[$attachment_filename] = $estimate['pathname'];
			$terms_html = $this->get_term_condition_as_html();

			if(is_array($terms_html))
			{
				if(count($terms_html) > 0)
				{
					for($x = 0; $x < count($terms_html); $x++)
					{
						$term_html = $terms_html[$x]['html'];
						$term_filename = $terms_html[$x]['filename'];
						$term_pdf = $this->doc_pdf($term_html, $term_filename);
						$attachments[$term_pdf['filename']] = $term_pdf['pathname'];
					}
				}
			}

			require_once $this->plugin_dir_path_dir . 'public/email-templates/estimates.php';
			$message = $email_template;	
		}
		else
		{			
			$request = (isset($_POST['inquiry'])) ?  sanitize_text_field($_POST['inquiry']) : apply_filters('dy_description', null);
			$message = '<p>'.esc_html(apply_filters('dy_email_greeting', sprintf(__('Hello %s,', 'dynamicpackages'), sanitize_text_field($_POST['first_name'])))).'</p>';
			$message .= '<p>'.sprintf(__('Our staff will be in touch with you very soon with more information about your request: %s', 'dynamicpackages'), '<strong>'.esc_html($request).'</strong>').'</p>';
			
			if(get_option('dy_phone') && get_option('dy_email'))
			{
				$message .= '<p>'.esc_html(sprintf(__('Do not hesitate to call us at %s or email us at %s if you have any questions.', 'dynamicpackages'), esc_html(get_option('dy_phone')), sanitize_email(get_option('dy_email')))).'</p>';
			}


			$phone = sanitize_text_field($_POST['country_calling_code']).sanitize_text_field($_POST['phone']);
			$message .= '<p>'.esc_html(sprintf(__('When is a good time to call you at %s? Or do you prefer Whatsapp?', 'dynamicpackages'), $phone)).'</p>';			
		}
	

		$to = sanitize_text_field($_POST['email']);
		$subject = $this->subject();
		$body = $message;
		$headers = array('Content-Type: text/html; charset=UTF-8');

		wp_mail($to, $subject, $body, $headers,  $attachments);
    }
	
	public function subject()
	{
		if(dy_validators::validate_quote())
		{
			$output = sprintf(__('%s, %s has sent you an estimate for %s%s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), currency_symbol(), money(dy_utilities::total()), sanitize_text_field($_POST['title']));			
		}
		else
		{
			global $post;
			
			$request = (isset($post->post_title)) ? $post->post_title : __('General Inquiry', 'dynamicpackages');
			$output = sprintf(__('%s, thanks for your request: %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), $request);	
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
		$output = array();
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