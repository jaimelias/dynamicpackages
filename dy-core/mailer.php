<?php

if ( !defined( 'WPINC' ) ) exit;

use SendGrid\Mail\Attachment;

#[AllowDynamicProperties]
class Dy_Mailer
{
	
	public function __construct()
	{
		$this->email_limit = 10;
		$this->web_api_key = get_option('sendgrid_web_api_key');
		$this->email = get_option('sendgrid_email');
		$this->email_bcc = get_option('sendgrid_email_bcc');
		$this->name = (get_option('sendgrid_name')) ? get_option('sendgrid_name') : get_bloginfo('name');
		$this->smtp_api_key = get_option('sendgrid_smtp_api_key');
		$this->smtp_username = get_option('sendgrid_smtp_username');
		$this->host = 'smtp.sendgrid.net';
		$this->settings_title = 'Mailer Config';
		$this->init();
	}
	
	public function is_transactional()
	{
		$output = (($this->web_api_key ||  ($this->smtp_api_key && $this->smtp_username)) && is_email($this->email)) ? true : false;
		
		return $output;
	}
	
	public function init()
	{
		add_action('admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 1);
		add_filter('wp_mail_from', array(&$this, 'from_email'), 100, 1);
		add_filter('wp_mail_from_name', array(&$this, 'from_name'), 100, 1);
		
		if($this->is_transactional())
		{
			add_action( 'phpmailer_init', array(&$this, 'phpmailer'), 100, 1 );
			add_action( 'wp_mail_failed', array(&$this, 'phpmailer_failed'), 10, 1 );
		}
	}

	public function phpmailer($mailer)
	{
		if(!isset($this->email_sent))
		{
			$mailer->IsSMTP();
			$mailer->Host = $this->host;
			$mailer->Port = 587;
			$mailer->SMTPAuth = true;
			$mailer->CharSet  = 'utf-8';
			$mailer->SMTPSecure = 'tls';
			$mailer->IsHTML(true);
			$mailer->Username = $this->smtp_username;
			$mailer->Password = $this->smtp_api_key;
			$mailer->SMTPDebug = 0;
			$this->email_sent = true;				
		}
		else
		{
			exit;
		}
	}		
	
	public function phpmailer_failed($wp_error)
	{
		write_log($wp_error->get_error_message());
	}
	
	public function add_settings_page()
	{
		add_submenu_page( 'options-general.php', $this->settings_title, $this->settings_title, 'manage_options', 'sendgrid-api-mailer', array(&$this, 'settings_page'));
	}	

	public function settings_page()
	{ 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html($this->settings_title); ?></h1>	
			<?php
			settings_fields( 'mailer_settings' );
			do_settings_sections( 'mailer_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}		
	
	public function settings_init()
	{

		//mailer settings
		register_setting('mailer_settings', 'sendgrid_email', 'sanitize_text_field');
		register_setting('mailer_settings', 'sendgrid_email_bcc', 'sanitize_items_per_line');
		register_setting('mailer_settings', 'sendgrid_name', 'sanitize_text_field');		


		//sendgrid settings
		register_setting('mailer_settings', 'sendgrid_web_api_key', 'sanitize_user');
		register_setting('mailer_settings', 'sendgrid_smtp_api_key', 'sanitize_text_field');
		register_setting('mailer_settings', 'sendgrid_smtp_username', 'sanitize_text_field');

		add_settings_section(
			'mailer_settings_section', 
			$this->settings_title, 
			'', 
			'mailer_settings'
		);

		add_settings_section(
			'sendgrid_settings_section', 
			'Sendgrid', 
			'', 
			'mailer_settings'
		);		
		
		add_settings_field( 
			'sendgrid_email', 
			'Bot Email (From)', 
			array(&$this, 'settings_input'), 
			'mailer_settings', 
			'mailer_settings_section',
			array('name' => 'sendgrid_email', 'type' => 'email') 
		);
		
		add_settings_field( 
			'sendgrid_email_bcc', 
			'Inbox Email (Bcc)', 
			array(&$this, 'settings_textarea'), 
			'mailer_settings', 
			'mailer_settings_section',
			array('name' => 'sendgrid_email_bcc') 
		);			

		add_settings_field( 
			'sendgrid_name', 
			'From Name', 
			array(&$this, 'settings_input'), 
			'mailer_settings', 
			'mailer_settings_section',
			array('name' => 'sendgrid_name') 
		);	

		add_settings_field( 
			'sendgrid_web_api_key', 
			'Web API Key', 
			array(&$this, 'settings_input'), 
			'mailer_settings', 
			'sendgrid_settings_section',
			array('name' => 'sendgrid_web_api_key') 
		);

		add_settings_field( 
			'sendgrid_smtp_api_key', 
			'SMTP API Key', 
			array(&$this, 'settings_input'), 
			'mailer_settings', 
			'sendgrid_settings_section',
			array('name' => 'sendgrid_smtp_api_key') 
		);	
		add_settings_field( 
			'sendgrid_smtp_username', 
			'SMTP Username', 
			array(&$this, 'settings_input'), 
			'mailer_settings', 
			'sendgrid_settings_section',
			array('name' => 'sendgrid_smtp_username') 
		);
		
	}
	
	public function settings_input($arr){
			$name = $arr['name'];
			$url = (array_key_exists('url', $arr)) ? '<a href="'.esc_url($arr['url']).'">?</a>' : null;
			$type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
		?>
		<input type="<?php echo $type; ?>" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr(get_option($name)); ?>" /> <span><?php echo $url; ?></span>

	<?php }

	public function settings_textarea($arr){
		$name = $arr['name'];
		$url = (array_key_exists('url', $arr)) ? '<a href="'.esc_url($arr['url']).'">?</a>' : null;
	?>
		<span><?php echo $url; ?></span>
		<textarea rows="10" class="width-100" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>"><?php echo esc_textarea($this->sanitize_items_per_line(get_option($name))); ?></textarea>

	<?php }		



	public function send($args = array())
	{
		if(is_array($args))
		{
			if(count($args) > 0)
			{
				$subject = htmlspecialchars_decode($args['subject']);
				$message = $this->minify_html($args['message']);
				$attachments = (array_key_exists('attachments', $args)) ? $args['attachments'] : array();
				$emails = (is_array($args['to'])) 
					?  array_map('sanitize_email', array_unique($args['to'])) 
					:  $this->email_str_row_to_array($args['to']);
				$count_emails = count($emails);
				$invalid_emails = false;
									
				if($count_emails > 0)
				{
					if($this->is_transactional())
					{
						$email = new \SendGrid\Mail\Mail();
						$email->setFrom(sanitize_email($this->email), esc_html($this->name));
						$email->setSubject($subject);

						for($x = 0; $x < $count_emails; $x++)
						{	
							if($x < $this->email_limit)
							{
								if(is_email($emails[$x]))
								{
									$email->addTo($emails[$x]);
								}
								else
								{
									$invalid_emails = true;
									break;
								}
							}
						}

						$bcc = $this->email_str_row_to_array($this->email_bcc);
						$count_bcc = count($bcc);

						if($count_bcc > 0)
						{
							for($x = 0; $x < $count_bcc; $x++)
							{
								if($x >= 0 && $x < $this->email_limit)
								{
									if(is_email($bcc[$x]))
									{
										$email->addBcc($bcc[$x]);
									}
									else
									{
										$invalid_emails = true;
										break;
									}
								}
							}
						}							

						if(!$invalid_emails)
						{
							$email->addContent('text/html', $message);				
							
							if($this->has_attachments($attachments))
							{
								for($x = 0; $x < count($attachments); $x++)
								{						
									$attachment = new Attachment();
									$attachment->setContent($attachments[$x]['data']);
									$attachment->setType('application/pdf');
									$attachment->setFilename(wp_specialchars_decode($attachments[$x]['filename']));
									$attachment->setDisposition('attachment');
									$email->addAttachment($attachment);	
								}							
							}
							
							$sendgrid = new \SendGrid(esc_html($this->web_api_key));
							
							try {
								
								$response = $sendgrid->send($email);
								
								if($response->statusCode() >= 200 && $response->statusCode() <= 299)
								{
									return $args;
								}
								else
								{
									write_log($response->body());
								}
							} 
							catch(Exception $e)
							{
								write_log($e->getMessage());
							}
						}
					}
					else
					{
						$headers = array('Content-Type: text/html; charset=UTF-8');

						if($this->email_bcc)
						{
							$headers[] = 'Bcc: ' . $this->email_bcc;
						}

						for($x = 0; $x < $count_emails; $x++)
						{
							if($x > 0 && $x <= 10)
							{
								if(is_email($emails[$x]))
								{
									$headers[] = 'Cc: ' . $emails[$x];
								}
								else
								{
									$invalid_emails = true;
								}
							}
						}

						if(!$invalid_emails)
						{
							wp_mail($to, $subject, $message, $headers);
						}
					}						
				}
				else
				{
					write_log('$email is not an array');
				}
			}
		}
	}
	
	public function has_attachments($attachments)
	{
		$output = false;
		
		if(is_array($attachments))
		{
			if(count($attachments) > 0)
			{	
				for($x = 0; $x < count($attachments); $x++)
				{
					if(array_key_exists('filename', $attachments[$x]) && array_key_exists('data', $attachments[$x]))
					{
						$output = true;
					}
				}
			}
		}
		
		return $output;
	}
	
	public function minify_html($template)
	{
		$search = array(
			'/\>[^\S ]+/s',
			'/[^\S ]+\</s',
			'/(\s)+/s',
			'/<!--(.|\s)*?-->/'
		);

		$replace = array(
			'>',
			'<',
			'\\1',
			''
		);

		return preg_replace($search, $replace, $template);			
	}
	public function from_name($name)
	{
		return ($this->name) ? $this->name : get_bloginfo('name');
	}

	public function from_email($email)
	{
		return ($this->email) ? $this->email : $email;
	}


	public function email_str_row_to_array($str)
	{
		$output = array();

		if($str)
		{
			$emails = explode(PHP_EOL, html_entity_decode($str));		
			$output = array_slice(array_unique(array_filter(array_map('sanitize_email', $emails))), 0, 10);
		}


		return $output;
	}

	public function sanitize_items_per_line($str)
	{
		$decoded_str = html_entity_decode($str);
	
		if ($decoded_str === false) {
			// Handle decoding error, perhaps log or throw an exception
			return false;
		}
	
		// Normalize line endings
		$emails = explode(PHP_EOL, $decoded_str);
	
		$unique_emails = array_unique(array_filter(array_map('sanitize_email', $emails)));
	
		// Limit the result to a configurable number
		$arr = array_slice($unique_emails, 0, $this->email_limit);
	
		return implode(PHP_EOL, $arr);
	}
	
}


if(!function_exists('sg_mail'))
{
	function sg_mail($args)
	{
		$mailer = new Dy_Mailer();
		$mailer->send($args);
		return true;
	}
}


?>