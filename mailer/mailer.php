<?php

class dynamicpackages_Mailer
{
	
	public function __construct()
	{
		$this->api_key = get_option('sendgrid_api_key');
		$this->username = get_option('sendgrid_username');
		$this->email = get_option('sendgrid_email');
		$this->host = 'smtp.sendgrid.net';
		$this->port = 587;
		$this->init();
	}
	
	public function is_transactional()
	{
		$output = ($this->api_key && $this->username && is_email($this->email)) ? true : false;
		
		return $output;
	}
	
	public function init()
	{
		if($this->is_transactional())
		{
			add_filter('wp_mail_from', array(&$this, 'from_email'), 10, 1);
			add_filter('wp_mail_from_name', array(&$this, 'from_name'), 10, 1);
			add_action( 'phpmailer_init', array(&$this, 'phpmailer'), 10, 1 );
			add_action( 'wp_mail_failed', array(&$this, 'phpmailer_failed'), 10, 1 );
		}
	}

	public function phpmailer($mailer)
	{
		$mailer->IsSMTP();
		$mailer->Host = $this->host;
		$mailer->Port = $this->port;
		$mailer->SMTPAuth = true;
		$mailer->CharSet  = "utf-8";
		$mailer->SMTPSecure = 'tls';
		$mailer->IsHTML(true);
		$mailer->Username = $this->username;
		$mailer->Password = $this->api_key;
		$mailer->SMTPDebug = 0;
		
		if(!$mailer->Send()) {
		   exit;
		}
	}
	
	public function phpmailer_failed($mailer)
	{
		write_log(json_encode($mailer));
	}
	
	public function from_name()
	{
		return esc_html(get_bloginfo('name').' Bot');
	}
	public function from_email($email)
	{
		
		$email = ($this->email != '') ? $this->email : $email;
		
		return $email;
	}
}

?>