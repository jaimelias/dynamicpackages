<?php

if ( !defined( 'WPINC' ) ) exit;

class pay_later{
	
	function __construct($plugin_id)
	{
		$this->plugin_id = $plugin_id;
		$this->id = 'pay_later';
		add_action('init', array(&$this, 'init'));
		add_action( 'admin_init', array(&$this, 'settings_init'), 1);
		add_action('admin_menu', array(&$this, 'add_settings_page'), 100);	
		add_filter('dy_request_the_content', array(&$this, 'filter_content'), 101);
		add_filter('wp_headers', array(&$this, 'send_data'));
		add_filter('gateway_buttons', array(&$this, 'button'), 3);
		add_filter('list_gateways', array(&$this, 'add_gateway'), 2);	
	}
	
	public function init()
	{
		$this->site_name = get_bloginfo('name');
		$this->valid_recaptcha = validate_recaptcha();
		$this->name = __('Buy Now & Pay Later', 'dynamicpackages');
		$this->type = 'financial';
		$this->user_name = get_option($this->id);
		$this->show = get_option($this->id . '_show');
		$this->min = get_option($this->id . '_min');
		$this->color = '#fff';
		$this->background_color = '#000';
		$this->plugin_dir_url = plugin_dir_url(__DIR__);
		$this->current_language = current_language();
		$this->languages = get_languages();

		for($x = 0; $x < count($this->languages); $x++)
		{
			$lang = $this->languages[$x];
			$message_field_name = $this->id . '_message_' . $lang;
			$message_field_value = get_option($message_field_name);

			if($message_field_value)
			{
				$this->$message_field_name = $message_field_value;

				if($this->current_language === $lang)
				{
					$this->message = $message_field_value;
				}				
			}
		}
	}

	public function send_data()
	{		
		if(dy_validators::validate_request() && $this->is_request_submitted())
		{
			

			if($this->valid_recaptcha)
			{
				add_filter('dy_email_notes', array(&$this, 'message'));
				add_filter('dy_email_label_notes', array(&$this, 'label_notes'));
				add_filter('dy_email_intro', array(&$this, 'subject'));
				add_filter('dy_email_subject', array(&$this, 'subject'));

				$total = dy_utilities::currency_format(dy_utilities::total());
				$link = '<a href="'.esc_html($_POST['package_url']).'">'.esc_html($_POST['title']).'</a>';


				$subject = $this->name . __(' in ', 'dynamicpackages') .$this->site_name.': ' . sanitize_text_field($_POST['first_name']) . ' ($' . $total . ')';
				$message = '<p>'.sprintf(__('Hello %s,', 'dynamicpackages'), $this->user_name).'</p>';
				$message .= '<p>'.sprintf(__('There is a new request for the program %s in %s.', 'dynamicpackages'), $this->name, $this->site_name).'</p>';
				$message .= '<p>'.sprintf(__('The requested package is %s for a total amount of %s (USD).', 'dynamicpackages'), $link, $total).'</p>';

				$message .= '<hr/><ul>';
				$message .= '<li>'.esc_html(sprintf(__('Name: %s %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), sanitize_text_field($_POST['lastname']))).'</li>';
				$message .= '<li>'.esc_html(sprintf(__('Phone: %s', 'dynamicpackages'), sanitize_text_field($_POST['phone']))).'</li>';
				$message .= '<li>'.esc_html(sprintf(__('Email: %s', 'dynamicpackages'), sanitize_text_field($_POST['email']))).'</li>';
				$message .= '</ul>';

				$args = array(
					'to' => sanitize_email($this->user_name),
					'subject' => $subject,
					'message' => $message
				);

				sg_mail($args);
			}
		}
	}

	public function subject()
	{
		return sprintf(__('%s, %s sent you a payment request for %s%s using %s - %s', 'dynamicpackages'), sanitize_text_field($_POST['first_name']), get_bloginfo('name'), dy_utilities::currency_symbol(), dy_utilities::currency_format(dy_utilities::total()), sanitize_text_field($this->name), sanitize_text_field($_POST['title']));
	}
	
	public function label_notes($notes)
	{
		return sprintf(__('%s Payment Instructions', 'dynamicpackages'), $this->name);
	}
	
	public function filter_content($content)
	{
		if(in_the_loop() && dy_validators::validate_request() && $this->is_request_submitted())
		{
			
			if($this->valid_recaptcha)
			{
				$content = $this->message(null);
			}			
		}
		return $content;
	}
	
	
	public function is_active()
	{
		$output = false;
		$which_var = $this->id.'_is_active';
		global $$which_var; 
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(!empty($this->user_name))
			{
				$output = true;
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}
	public function show()
	{
		$output = false;
		$which_var = $this->id.'_show';
		global $$which_var; 
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$output = true;
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}
	public function is_request_submitted()
	{
		$output = false;
		$which_var = $this->id . '_is_valid_request';
		global $$which_var;
		global $dy_request_invalids;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(isset($_POST['dy_request']) && !isset($dy_request_invalids))
			{
				if($_POST['dy_request'] === $this->id && dy_utilities::payment_amount() > 1)
				{
					$output = true;
	
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}
		
		return $output;
	}
	
	public function is_valid()
	{
		$output = false;
		$which_var = $this->id . '_is_valid';
		global $$which_var;
		
		if(isset($$which_var))
		{
			return $$which_var;
		}
		else
		{
			if($this->is_active() )
			{
				$min = floatval($this->min);
				$show = intval($this->show);
				
				if(is_booking_page() || is_checkout_page())
				{
					$total = dy_utilities::total();
				}
				else
				{
					$total = floatval(dy_utilities::starting_at());
					
					if(package_field('package_fixed_price') == 0)
					{
						$total = $total * intval(package_field('package_min_persons'));
					}
				}
				
				if($total >= $min)
				{
					$output = true;
				}
			}
			
			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}

	public function settings_init()
	{		
		register_setting($this->id . '_settings', $this->id, 'sanitize_email');
		register_setting($this->id . '_settings', $this->id . '_show', 'intval');
		register_setting($this->id . '_settings', $this->id . '_min', 'floatval');
		
		add_settings_section(
			$this->id . '_settings_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->id . '_settings'
		);
		
		add_settings_field( 
			$this->id, 
			esc_html(__( 'Agent Email', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id
		);

		add_settings_field( 
			$this->id . '_min', 
			esc_html(__( 'Min. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', $this->id . '_min'
		);
		
		$show_args = array(
			'name' => $this->id . '_show',
			'options' => array(
				array(
					'text' => __('Full Payments and Deposits', 'dynamicpackages'),
					'value' => 0
				),
				array(
					'text' => esc_html('Only Deposits', 'dynamicpackages'),
					'value' => 1
				),
			)
		);

		add_settings_field( 
			$this->id . '_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'select'), 
			$this->id . '_settings', 
			$this->id . '_settings_section', 
			$show_args
		);	
		
		//message start

		for($x = 0; $x < count($this->languages); $x++)
		{
			$lang = $this->languages[$x];
			$message_field_name = $this->id . '_message_' . $lang;

			register_setting($this->id . '_settings', $message_field_name, 'esc_textarea');

			add_settings_field( 
				$message_field_name, 
				sprintf(__('Message [%s]', 'dynamicpackages'), $lang), 
				array(&$this, 'input_textarea'), 
				$this->id . '_settings', 
				$this->id . '_settings_section', $message_field_name
			);
		}
		//message end


	}
	
	public function input_text($name){
		$option = get_option($name);
		?>
		<input type="text" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($option); ?>" />
		<?php
	}
	
	public function input_number($name){
		$option = get_option($name);
		?>
		<input type="number" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($option); ?>" /> #
		<?php
	}
	public function input_textarea($name){
		$option = get_option($name);
		?>
		<textarea rows="10" cols="50" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" ><?php echo esc_textarea($option); ?></textarea>
		<?php
	}

	public function select($args) {
		
		$name = $args['name'];
		$options = $args['options'];
		$value = intval(get_option($name));
		$render_options = '';
		
		for($x = 0; $x < count($options); $x++)
		{
			$this_value = intval($options[$x]['value']);
			$this_text = $options[$x]['text'];
			$selected = ($value === $this_value) ? ' selected ' : '';
			$render_options .= '<option value="'.esc_attr($this_value).'" '.esc_attr($selected).'>'.esc_html($this_text).'</option>';
		}

		?>
			<select name="<?php echo esc_attr($name); ?>">
				<?php echo $render_options; ?>
			</select>
		<?php 
	}	

	public function add_settings_page()
	{
		add_submenu_page( $this->plugin_id, $this->name, '💸 '.$this->name, 'manage_options', $this->id, array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html_e($this->name); ?></h1>	
			<?php
			settings_fields( $this->id . '_settings' );
			do_settings_sections( $this->id . '_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}	
	public function button($output)
	{
		if($this->show() && in_array($this->name, $this->list_gateways_cb()))
		{
			$output .= ' <button data-type="'.esc_attr($this->type).'"  data-id="'.esc_attr($this->id).'" data-branding="'.esc_attr($this->branding()).'" style="color: '.esc_attr($this->color).'; background-color: '.esc_attr($this->background_color).';" class="pure-button bottom-20 rounded" type="button">😃 '.esc_html($this->name).'</button>';
		}
		return $output;
	}
	public function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	
	public function add_gateway($array)
	{
		
		$add = false;
		
		if($this->show() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$add = true;
		}
		
		if($this->valid_recaptcha && isset($_POST['dy_request']) && dy_validators::validate_request())
		{
			if($_POST['dy_request'] == 'estimate_request' || $_POST['dy_request'] == apply_filters('dy_fail_checkout_gateway_name', null))
			{
				$add = true;
			}	
		}		
		
		if($add)
		{
			$array[] = $this->name;
		}
		
		return $array;	
	}


	public function branding()
	{
		//message start
		$output = '<p><img src="'.esc_url($this->plugin_dir_url.'assets/card/visa-mastercard.svg').'" width="250" height="50" /></p>';
		$output .= '<p class="large text-muted">'.esc_html($this->name).'</p>';

		if($this->message)
		{
			$Parsedown = new Parsedown();
			$message = $Parsedown->text($this->message);
			$output .= '<div class="text-left minimal_warning small">'.esc_html($message).'</div>';
		}

		return $output;
	}
	
	public function message($message)
	{
		return '<p class="large minimal_success strong">'.esc_html(sprintf(__('Hello %s, we have sent your request to the financial institution in charge of our program %s. You will be receiving an email from %s with futher instrucctions to complete this aplication.', 'dynamicpackages'), $_POST['first_name'], $this->name, $this->user_name)).'</p>';
	}	
}