<?php

if ( !defined( 'ABSPATH' ) ) exit;

if(!class_exists('Sendgrid_Mailer'))
{
	class Sendgrid_Mailer
	{
		
		public function __construct()
		{
			$this->api_key = get_option('sendgrid_api_key');
			$this->email = get_option('sendgrid_email');
			$this->name = (get_option('sendgrid_name')) ? get_option('sendgrid_name') : get_bloginfo('name');
			$this->smtp_api_key = get_option('sendgrid_smtp_api_key');
			$this->smtp_username = get_option('sendgrid_username');
			$this->host = 'smtp.sendgrid.net';
			$this->settings_title = 'Sendgrid API Mailer';
			$this->init();
		}
		
		public function is_transactional()
		{
			$output = (($this->api_key ||  ($this->smtp_api_key && $this->smtp_username)) && is_email($this->email)) ? true : false;
			
			return $output;
		}
		
		public function init()
		{
			add_action('admin_init', array(&$this, 'settings_init'), 1);
			add_action('admin_menu', array(&$this, 'add_settings_page'), 1);
			
			if($this->is_transactional())
			{
				add_action( 'phpmailer_init', array(&$this, 'disable_phpmailer'), 100, 1 );
				add_filter('wp_mail_from', array(&$this, 'from_email'), 100, 1);
				add_filter('wp_mail_from_name', array(&$this, 'from_name'), 100, 1);
				add_action( 'phpmailer_init', array(&$this, 'phpmailer'), 10, 1 );
				add_action( 'wp_mail_failed', array(&$this, 'phpmailer_failed'), 10, 1 );
			}
		}
		
		public function phpmailer($mailer)
		{
			$mailer->IsSMTP();
			$mailer->Host = $this->host;
			$mailer->Port = 587;
			$mailer->SMTPAuth = true;
			$mailer->CharSet  = "utf-8";
			$mailer->SMTPSecure = 'tls';
			$mailer->IsHTML(true);
			$mailer->Username = $this->smtp_username;
			$mailer->Password = $this->smtp_api_key;
			$mailer->SMTPDebug = 0;

			if(!$mailer->Send()) {
			   exit;
			}
		}		
		
		public function phpmailer_failed($mailer)
		{
			write_log($mailer->ErrorInfo);
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
				settings_fields( 'sendgrid_settings' );
				do_settings_sections( 'sendgrid_settings' );
				submit_button();
				?>			
			</form>
			
			<?php
		}		
		
		public function settings_init()
		{
			register_setting('sendgrid_settings', 'sendgrid_api_key', 'sanitize_user');
			register_setting('sendgrid_settings', 'sendgrid_email', 'sanitize_text_field');
			register_setting('sendgrid_settings', 'sendgrid_name', 'sanitize_text_field');
			
			register_setting('sendgrid_settings', 'sendgrid_smtp_api_key', 'sanitize_text_field');
			register_setting('sendgrid_settings', 'sendgrid_smtp_username', 'sanitize_text_field');

			add_settings_section(
				'sendgrid_settings_section', 
				$this->settings_title, 
				'', 
				'sendgrid_settings'
			);
			
			add_settings_field( 
				'sendgrid_api_key', 
				'API Key', 
				array(&$this, 'settings_input'), 
				'sendgrid_settings', 
				'sendgrid_settings_section',
				array('name' => 'sendgrid_api_key') 
			);

			add_settings_field( 
				'sendgrid_email', 
				'Email', 
				array(&$this, 'settings_input'), 
				'sendgrid_settings', 
				'sendgrid_settings_section',
				array('name' => 'sendgrid_email', 'type' => 'email') 
			);

			add_settings_field( 
				'sendgrid_name', 
				'Name', 
				array(&$this, 'settings_input'), 
				'sendgrid_settings', 
				'sendgrid_settings_section',
				array('name' => 'sendgrid_name') 
			);	

			add_settings_field( 
				'sendgrid_smtp_api_key', 
				'SMTP API Key', 
				array(&$this, 'settings_input'), 
				'sendgrid_settings', 
				'sendgrid_settings_section',
				array('name' => 'sendgrid_smtp_api_key') 
			);	
			add_settings_field( 
				'sendgrid_smtp_username', 
				'SMTP Username', 
				array(&$this, 'settings_input'), 
				'sendgrid_settings', 
				'sendgrid_settings_section',
				array('name' => 'sendgrid_smtp_username') 
			);
			
		}
		
		public function settings_input($arr){
				$name = $arr['name'];
				$url = (array_key_exists('url', $arr)) ? '<a href="'.esc_url($arr['url']).'">?</a>' : null;
				$type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
			?>
			<input type="<?php echo $type; ?>" name="<?php echo esc_html($name); ?>" id="<?php echo $name; ?>" value="<?php echo esc_html(get_option($name)); ?>" /> <span><?php echo $url; ?></span>

		<?php }		

		public function send($args)
		{
			$to = sanitize_email($args['to']);
			$subject = esc_html($args['subject']);
			$message = $this->minify_html($args['message']);
			
			if($this->api_key)
			{
				$email = new \SendGrid\Mail\Mail(); 
				$email->setFrom(sanitize_email($this->email), esc_html($this->name));
				$email->setSubject($subject);
				$email->addTo($to);
				$email->addContent('text/html', $message);
				$sendgrid = new \SendGrid(esc_html($this->api_key));
				
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
			else
			{
				$headers = array('Content-type: text/html');
				wp_mail($to, $subject, $message, $headers);
			}
		}
		
		public function disable_phpmailer($phpmailer)
		{
			$phpmailer->ClearAllRecipients();
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
		public function from_name()
		{
			return $this->name;
		}
		public function from_email($email)
		{
			return $this->email;
		}
	}
	
	
	
	$GLOBALS['SENDGRID_API_MAILER'] = new Sendgrid_Mailer();	
	
}


if(!function_exists('sg_mail'))
{
	function sg_mail($args)
	{
		global $SENDGRID_API_MAILER;
		
		$SENDGRID_API_MAILER->send($args);
		
	}
}


?>