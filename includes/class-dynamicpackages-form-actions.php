<?php


if ( !defined( 'WPINC' ) ) exit;

use Spipu\Html2Pdf\Html2Pdf;

class Dynamicpackages_Actions{

    public function __construct()
    {
        $this->init();
    }
    public function init()
    {
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
		$this->providers = apply_filters('dy_list_providers', array());
	}

	public function is_request_submitted()
	{
		global $dy_request_invalids;
		$output = false;
		
        if(isset($_POST['dy_request']) && !isset($dy_request_invalids))
        {
			$output = true;
        }

        return $output;
	}    
	

    public function send_data()
    {
        global $dy_valid_recaptcha;

        if(isset($dy_valid_recaptcha) && $this->is_request_submitted() && dy_validators::validate_request())
        {
			if(isset($_REQUEST['add_ons']))
			{
				$add_ons_package_id = sanitize_key('dy_add_ons_' . get_the_ID());
				$add_ons = sanitize_text_field($_REQUEST['add_ons']);
				setcookie($add_ons_package_id, $add_ons, time() + 3600);
			}

			$this->send_email();

			$webhook_option = apply_filters('dy_webhook_option', 'dy_quote_webhook');
			$webhook_args = $_POST;
			$webhook_args['providers'] = $this->providers;

            dy_utilities::webhook($webhook_option, json_encode($webhook_args));
        }   
    }
    public function the_content($content)
    {
        global $dy_valid_recaptcha;
		
        if(dy_validators::has_form() && $this->is_request_submitted())
        {               
            if(dy_validators::validate_request())
            {
                if(isset($dy_valid_recaptcha))
                {
					if($_POST['dy_request'] == 'estimate_request' || $_POST['dy_request'] == 'contact')
					{
						 $content = '<p class="minimal_success strong">'.esc_html( __('Thank you for contacting us. Our staff will be in touch with you soon.', 'dynamicpackages')).'</p>';
					}  
                }                 
            }
        }

        return apply_filters('dy_request_the_content', $content);
    }

	public function doc_pdf()
	{
		require_once $this->plugin_dir_path_dir . 'public/email-templates/estimates-pdf.php';
		
		$doc_pdf = new Html2Pdf('P', 'A4', $this->current_language);
		$doc_pdf->pdf->SetDisplayMode('fullpage');
		$doc_pdf->writeHTML($email_pdf);
		$doc_pdf_content = $doc_pdf->output('doc.pdf', 'S');
		return $doc_pdf_content;
	}

    public function send_email()
    {
		$args = array(
			'subject' => $this->subject(),
			'to' => sanitize_text_field($_POST['email'])
		);

		if(dy_validators::validate_quote())
		{
			$attachments = array();
			require_once $this->plugin_dir_path_dir . 'public/email-templates/estimates.php';
			$filename = __('Estimate', 'dynamicpackages') . '.pdf';
			
			$attachments[] = array(
				'filename' => $filename,
				'data' => $this->doc_pdf()
			);
			
			$terms_pdf = $this->get_term_condition_attachment();

			if(is_array($terms_pdf))
			{
				if(count($terms_pdf) > 0)
				{
					for($x = 0; $x < count($terms_pdf); $x++)
					{
						$attachments[] = $terms_pdf[$x];
					}
				}
			}

			$args['message'] = $email_template;
			$args['attachments'] = $attachments;
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
			
			$message .= '<p>'.esc_html(sprintf(__('When is a good time to call you at %s? Or do you prefer Whatsapp?', 'dynamicpackages'), sanitize_text_field($_POST['phone']))).'</p>';
			
			$args['message'] = $message;
		}
	
		//die($args['message']);
		
		sg_mail($args);
    }
	
	public function subject()
	{
		if(dy_validators::validate_quote())
		{
			$output = sprintf(__('%s, %s has sent you an estimate for %s%s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), dy_utilities::currency_symbol(), dy_utilities::currency_format(dy_utilities::total()), sanitize_text_field($_POST['title']));			
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
        if(dy_validators::has_form() && $this->is_request_submitted())
        {
            $title = esc_html(__('Thank You for Your Request', 'dynamicpackages')).' | '.esc_html(get_bloginfo( 'name' ));
        }

        return $title;
    }
	
	public function modify_excerpt($excerpt)
	{
        if(dy_validators::has_form() && $this->is_request_submitted())
        {
            $excerpt = apply_filters('dy_description', null);
        }

        return $excerpt;
	}

    public function the_title($title)
    {	
		if(in_the_loop())
		{
			if(dy_validators::has_form() && $this->is_request_submitted())
			{
				$title = esc_html(__('Thank You for Your Request', 'dynamicpackages'));
			}			
		}

        return apply_filters('dy_request_the_title', $title);
    }
	
	public function get_term_condition_attachment()
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
					$page = '<style type="text/css">p{line-height: 1.25;}ul{line-height: 1.25;}ol{line-height: 1.25;}</style>';
					$page .= '<page backcolor="#ffffff" style="font-size: 12pt;" backtop="10mm" backbottom="10mm" backleft="10mm" backright="10mm">';
					$page .= '<h1 style="text-align: center; margin: 0; padding: 0; font-size: 20pt;">'.esc_html($name).'</h1>';
					$page .= $Parsedown->text($terms_conditions[$x]->description);
					$page .= '</page>';		
					
					//PDF
					$pdf = new Html2Pdf('P', 'A4', $this->current_language);
					$pdf->pdf->SetDisplayMode('fullpage');
					$pdf->writeHTML($page);
		
					//OUTPUT
					$filename = $name . '.pdf';
					
					$output[] = array(
						'filename' => $filename,
						'data' => $pdf->output($filename, 'S')
					);
				}		
			}
		}
		
		return $output;
	}	
	
}

?>