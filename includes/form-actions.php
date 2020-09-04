<?php


use Spipu\Html2Pdf\Html2Pdf;

class dy_Actions{

    public function __construct()
    {
		$this->lang = substr(get_locale(), 0, -3);
        $this->init();
    }
    public function init()
    {
        add_filter('wp_headers', array(&$this, 'send_data'));
        add_filter('the_content', array(&$this, 'the_content'), 101);
        add_filter( 'pre_get_document_title', array(&$this, 'wp_title'), 101);
        add_filter( 'the_title', array(&$this, 'the_title'), 101);
    }

	public function is_request_submitted()
	{
		$output = false;
		
        if(isset($_POST['dy_request']))
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
                    $content = '<p class="minimal_success"><strong>'.esc_html( __('Thank you for contacting us. Our staff will be in touch with you soon.', 'dynamicpackages')).'</strong></p>';
                }
                else
                {
                    $content = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Recaptcha', 'dynamicpackages')).'</strong></p>';
                }                  
            }
            else
            {
                $content = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Request', 'dynamicpackages')).'</strong></p>';
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
		$attachments = array();
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/email-templates/estimates.php';
		
		$filename = __('Estimate', 'dynamicpackages') . '.pdf';
		
		$attachments[] = array(
			'filename' => $filename,
			'data' => $this->doc_pdf()
		);
		
		$terms_pdf = dy_PDF::get_terms_conditions_pages();

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
			
		$args = array(
			'subject' => sanitize_text_field($this->subject()),
			'to' => sanitize_text_field($_POST['email']),
			'message' => $email_template,
			'attachments' => $attachments
		);
		
		//die($email_template);
		
		sg_mail($args);
    }
	
	public function subject()
	{
		$calculate_total = ($_POST['amount'] > dy_utilities::total()) ? $_POST['amount'] : dy_utilities::total();

		$output = sprintf(__('%s, %s has sent you an estimate for %s%s - %s', 'dynamicpackages'), $_POST['first_name'], get_bloginfo('name'), dy_utilities::currency_symbol(), $calculate_total, $_POST['title']);
		
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
}

?>