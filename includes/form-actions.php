<?php


use Spipu\Html2Pdf\Html2Pdf;

class dy_Actions{

    public function __construct()
    {
        $this->init();
    }
    public function init()
    {
		add_action('init', array(&$this, 'args'));
        add_filter('wp_headers', array(&$this, 'send_data'));
        add_filter('the_content', array(&$this, 'the_content'), 101);
        add_filter( 'pre_get_document_title', array(&$this, 'wp_title'), 101);
        add_filter( 'the_title', array(&$this, 'the_title'), 101);
    }

	public function args()
	{
		$this->lang = substr(get_locale(), 0, -3);
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

        if(isset($dy_valid_recaptcha) && $this->is_request_submitted() && dy_Validators::is_request_valid())
        {
            $this->send_email();
            dy_utilities::webhook('dy_quote_webhook', json_encode($_POST));
        }   
    }
    public function the_content($content)
    {
        global $dy_valid_recaptcha;
		

        if(is_singular('packages') && $this->is_request_submitted())
        {               
            if(dy_Validators::is_request_valid())
            {
                if(isset($dy_valid_recaptcha))
                {
					if($_POST['dy_request'] == 'request')
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/email-templates/estimates-pdf.php';
		
		$doc_pdf = new Html2Pdf('P', 'A4', $this->lang);
		$doc_pdf->pdf->SetDisplayMode('fullpage');
		$doc_pdf->writeHTML($email_pdf);
		$doc_pdf_content = $doc_pdf->output('doc.pdf', 'S');
		return $doc_pdf_content;
	}

    public function send_email()
    {
		$args = array(
			'subject' => sanitize_text_field($this->subject()),
			'to' => sanitize_text_field($_POST['email'])
		);

		if(dy_utilities::total() > 0)
		{
			$attachments = array();
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/email-templates/estimates.php';
			$filename = __('Estimate', 'dynamicpackages') . '.pdf';
			
			$attachments[] = array(
				'filename' => $filename,
				'data' => $this->doc_pdf()
			);
			
			$terms_pdf = $this->get_terms_conditions_pages();

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
			$message = '<p>'.esc_html(apply_filters('dy_email_greeting', sprintf(__('Hello %s,', 'dynamicpackages'), sanitize_text_field($_POST['first_name'])))).'</p>';
			$message .= '<p>'.sprintf(__('Our staff will be in touch with you very soon with more information about your request: %s', 'dynamicpackages'), '<strong>'.esc_html(apply_filters('dy_package_description', null)).'</strong>').'</p>';
			
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
		if(dy_utilities::total() > 0)
		{
			$output = sprintf(__('%s, %s has sent you an estimate for %s%s - %s', 'dynamicpackages'), $_POST['first_name'], get_bloginfo('name'), dy_utilities::currency_symbol(), dy_utilities::payment_amount(), $_POST['title']);			
		}
		else
		{
			$output = sprintf(__('%s, thanks for your request: %s', 'dynamicpackages'), $_POST['first_name'], $_POST['title']);	
		}

			
		return apply_filters('dy_email_subject', $output);
	}

    public function wp_title($title)
    {
        if(is_singular('packages') && $this->is_request_submitted())
        {
            $title = esc_html(__('Thank You For Your Request', 'dynamicpackages')).' | '.esc_html(get_bloginfo( 'name' ));
        }

        return $title;
    }

    public function the_title($title)
    {	
		if(in_the_loop())
		{
			if(is_singular('packages') && $this->is_request_submitted())
			{
				$title = esc_html(__('Thank you for your Inquiry', 'dynamicpackages'));
			}			
		}

        return apply_filters('dy_request_the_title', $title);
    }
	
	public function get_terms_conditions_pages()
	{		
		$output = array();
		$terms_conditions = dy_Public::get_terms_conditions(sanitize_text_field($_POST['post_id']));

		if(is_array($terms_conditions))
		{
			if(count($terms_conditions) > 0)
			{
				for($x = 0; $x < count($terms_conditions); $x++ )
				{
					$number = $x + 1;
					$name = $terms_conditions[$x]->name;
					
					//PAGE
					$page = '<style type="text/css">p {line-height: 2;}</style>';
					$page .= '<page backcolor="#ffffff" style="font-size: 12pt;" backtop="10mm" backbottom="10mm" backleft="10mm" backright="10mm">';
					$page .= '<h1 style="text-align: center; margin: 0; padding: 0; font-size: 20pt;">'.esc_html($name).'</h1>';
					$page .= wpautop($terms_conditions[$x]->description);
					$page .= '</page>';		
					
					//PDF
					$pdf = new Html2Pdf('P', 'A4', $this->lang);
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