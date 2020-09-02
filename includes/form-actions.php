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
        add_filter( 'wp_title', array(&$this, 'wp_title'), 101);
        add_filter( 'the_title', array(&$this, 'the_title'), 101);
    }

	public function is_request_submitted()
	{
		$output = false;
		
        if(isset($_POST['dy_request']))
        {
            if($_POST['dy_request'] == 'request')
            {
                $output = true;
            }	
        }

        return $output;
	}    

    public function send_data()
    {
        global $dy_valid_recaptcha;

        if(isset($dy_valid_recaptcha) && $this->is_request_submitted() && dy_Validators::is_request_valid())
        {
            $this->send_quote_email();
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

        return $content;
    }

	public function estimate_pdf()
	{
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/email-templates/estimates-pdf.php';
		
		$estimate_pdf = new Html2Pdf('P', 'A4', $this->lang);
		$estimate_pdf->pdf->SetDisplayMode('fullpage');
		$estimate_pdf->writeHTML($email_pdf);
		$estimate_pdf_content = $estimate_pdf->output('estimate.pdf', 'S');
		return $estimate_pdf_content;
	}

    public function send_quote_email()
    {
		$attachments = array();
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/email-templates/estimates.php';
		
		
		$attachments[] = array(
			'filename' => 'Estimate',
			'data' => $this->estimate_pdf()
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
			'subject' => sanitize_text_field($_POST['description']),
			'to' => sanitize_text_field($_POST['email']),
			'message' => $email_template,
			'attachments' => $attachments
		);
		
		sg_mail($args);
    }

    public function wp_title($title)
    {

        if(is_singular('packages') && $this->is_request_submitted())
        {
            $title = esc_html(__('Quote', 'dynamicpackages')).' '.esc_html(get_the_title()).' | '.esc_html(get_bloginfo( 'name' ));
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

        return $title;
    }
}

?>