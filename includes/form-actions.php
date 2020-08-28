<?php


class dynamicpackages_Form_Actions{

    public function __construct()
    {
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

        if(isset($dy_valid_recaptcha) && $this->is_request_submitted() && dynamicpackages_Validators::is_request_valid())
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
            if(dynamicpackages_Validators::is_request_valid())
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

    public function send_quote_email()
    {
        $headers = array('Content-type: text/html');
        array_push($headers, 'Reply-To: '.sanitize_text_field($_POST['fname']).' '.sanitize_text_field($_POST['lastname']).' <'.sanitize_text_field($_POST['email']).'>');
        $body = __('New Request from', 'dynamicpackages');
        $body .= ' ';
        $body .= sanitize_text_field($_POST['fname']) .' '.sanitize_text_field($_POST['lastname']);
        $body .= ',<br/><br/>';
        $body .= sanitize_text_field($_POST['description']);
        $body .= '<br/><br/>';
        $body .= __('Name', 'dynamicpackages').': '.sanitize_text_field($_POST['fname']).' '.sanitize_text_field($_POST['lastname']);
        $body .= '<br/>';
        $body .= __('Email', 'dynamicpackages').': '.sanitize_text_field($_POST['email']);
        $body .= '<br/>';
        $body .= __('Phone', 'dynamicpackages').': '.sanitize_text_field($_POST['phone']);
        
        wp_mail(get_option('admin_email'), esc_html(sanitize_text_field($_POST['fname']).': '. sanitize_text_field($_POST['description'])), $body, $headers);
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
        if(is_singular('packages') && $this->is_request_submitted())
        {
            $title = esc_html(__('Thank you for your Inquiry', 'dynamicpackages'));
        }

        return $title;
    }
}

?>