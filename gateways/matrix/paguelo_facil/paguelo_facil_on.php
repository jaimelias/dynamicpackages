<?php

class paguelo_facil_on{
	
	function __construct()
	{
		$this->init();
	}
	
	public function init()
	{
		add_action('init', array(&$this, 'args'));
		
		if(is_admin())
		{
			add_action( 'admin_init', array(&$this, 'settings_init'), 1);
			add_action('admin_menu', array(&$this, 'add_settings_page'), 101);			
		}
		else
		{
			add_action('init', array(&$this, 'checkout'));
			add_filter('wp_headers', array(&$this, 'send_data'));
			add_filter('dy_request_the_content', array(&$this, 'filter_content'), 102);
			add_filter('dy_request_the_title', array(&$this, 'title'), 102);
			add_filter('get_the_excerpt', array(&$this, 'filter_excerpt'), 102);
			add_filter('gateway_buttons', array(&$this, 'button'), 1);
			add_filter('list_gateways', array(&$this, 'add_gateway'), 1);
			add_action('wp_enqueue_scripts', array(&$this, 'scripts'), 102);
		}
	}
	
	public function args()
	{
		$this->gateway_name = 'paguelo_facil_on';
		$this->gateway_short_title = __('Paguelo Facil', 'dynamicpackages');
		$this->gateway_title = __('Paguelo Facil On-site', 'dynamicpackages');
		$this->gateway_methods_o = __('Visa or Mastercard', 'dynamicpackages');
		$this->gateway_methods_c = __('Visa, Mastercard', 'dynamicpackages');
		$this->cclw = get_option($this->gateway_name);
		$this->show = get_option($this->gateway_name . '_show');
		$this->min = get_option($this->gateway_name . '_min');
	}	
	
	public function checkout()
	{
		global $dy_valid_recaptcha;
		global $dy_checkout_success;
		
		if(!isset($dy_checkout_success))
		{
			if(dy_Validators::validate_checkout())
			{
				if(isset($dy_valid_recaptcha))
				{
					$GLOBALS['dy_checkout_success'] = 2;
				}
			}			
		}		
	}	

	public function send_data()
	{
		global $dy_valid_recaptcha;
		global $dy_checkout_success;
		
		if(dy_Validators::is_request_valid() && $this->is_valid_request() && isset($dy_valid_recaptcha) && isset($dy_checkout_success))
		{
			if($dy_checkout_success === 2)
			{
				add_filter('dy_email_notes', array(&$this, 'message'));
				add_filter('dy_email_label_notes', array(&$this, 'label_notes'));				
			}
		}
	}
	
	public function message($output)
	{
		global $dy_checkout_success;
		$output = null;
		
		if(isset($dy_checkout_success))
		{
			if($dy_checkout_success === 2)
			{
				$output = 'MESSAGE: PAYMENT APPROVED';
			}
			else if($dy_checkout_success === 1)
			{
				$output = 'MESSAGE: PAYMENT DECLINED';
			}
			else
			{
				$output = 'MESSAGE: ERROR DECLINED';
			}
		}
		
		return $output;
	}	
	
	public function label_notes($output)
	{
		global $dy_checkout_success;
		$output = null;
		
		if(isset($dy_checkout_success))
		{
			if($dy_checkout_success === 2)
			{
				$output = 'LABEL_NOTES: PAYMENT APPROVED';
			}
			else if($dy_checkout_success === 1)
			{
				$output = 'LABEL_NOTES: PAYMENT DECLINED';
			}
			else
			{
				$output = 'LABEL_NOTES: ERROR DECLINED';
			}
		}
		
		return $output;
	}

	public function is_active()
	{
		$output = false;
		global $paguelo_facil_on_is_active;
		
		if(isset($paguelo_facil_on_is_active))
		{
			$output = true;
		}
		else
		{
			if($this->cclw != '')
			{
				$GLOBALS[$this->gateway_name . '_is_active'] = true;
				$output = true;
			}
		}
		return $output;
	}
	public function show()
	{
		$output = false;
		global $paguelo_facil_on_show;
		
		if(isset($paguelo_facil_on_show))
		{
			$output = true;
		}
		else
		{
			if(is_singular('packages') && $this->is_active())
			{
				if($this->is_valid())
				{
					$GLOBALS[$this->gateway_name . '_show'] = true;
					$output = true;
				}
			}			
		}
		return $output;
	}
	public function is_valid_request()
	{
		$output = false;
		global $paguelo_facil_on_is_valid_request;
		
		if(isset($paguelo_facil_on_is_valid_request))
		{
			$output = true;
		}
		else
		{
			if(isset($_POST['dy_request']) && isset($_POST['total']))
			{
				if($_POST['dy_request'] == $this->gateway_name && intval($_POST['total']) > 1)
				{
					$GLOBALS['paguelo_facil_on_is_valid_request'] = true;
					$output = true;
				}
			}		
		}
		
		return $output;
	}
	public function filter_excerpt($output)
	{
		global $dy_checkout_success;
		$output = null;
		
		if(isset($dy_checkout_success) && in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			if($dy_checkout_success === 2)
			{
				$output = 'EXCERPT: PAYMENT APPROVED';
			}
			else if($dy_checkout_success === 1)
			{
				$output = 'EXCERPT: PAYMENT DECLINED';
			}
			else
			{
				$output = 'EXCERPT: ERROR DECLINED';
			}
		}		

		return $output;
	}
	
	public function filter_content($output)
	{
		global $dy_valid_recaptcha;
		global $dy_checkout_success;
		$output = null;	
		
		if(isset($dy_checkout_success) && in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			if(isset($dy_valid_recaptcha))
			{
				$output = $this->message(null);
			}
			else
			{
				$output = '<p class="minimal_alert"><strong>'.esc_html( __('Invalid Recaptcha', 'dynamicpackages')).'</strong></p>';
			}	
		}
		return $output;
	}
		
	public function title($output)
	{
		global $dy_checkout_success;
		$output = null;
		
		if(isset($dy_checkout_success) && in_the_loop() && dy_Validators::is_request_valid() && $this->is_valid_request())
		{
			if($dy_checkout_success === 2)
			{
				$output = 'EXCERPT: PAYMENT APPROVED';
			}
			else if($dy_checkout_success === 1)
			{
				$output = 'EXCERPT: PAYMENT DECLINED';
			}
			else
			{
				$output = 'EXCERPT: ERROR DECLINED';
			}
		}
		return $output;
	}

	public function is_valid()
	{
		$output = false;
		global $paguelo_facil_on_is_valid;
		
		if(isset($paguelo_facil_on_is_valid))
		{
			return true;
		}
		else
		{
			if($this->is_active() && !isset($_GET['quote']))
			{
				$min = floatval($this->min);
				$show = intval($this->show);
				$payment = package_field('package_payment');
				$deposit = floatval(dy_utilities::get_deposit());
				
				if(is_booking_page())
				{
					$total = dy_utilities::total();
				}
				else
				{
					$total = floatval(dy_utilities::starting_at());
					
					if(package_field('package_starting_at_unit') == 0)
					{
						$total = $total * intval(package_field('package_max_persons'));
					}
					
				}

				if(dy_Validators::has_deposit())
				{
					$total = $total * ($deposit/100);
				}
				
				if($total >= $min)
				{
					if($payment == $show && $payment == 0)
					{
						$output = true;
					}
					else
					{
						if(dy_Validators::has_deposit())
						{
							$output = true;
						}
					}
				}
			}
			
			if($output == true){
				$GLOBALS[$this->gateway_name . '_is_valid'] = true;
			}
		}
		return $output;
	}

	public function settings_init()
	{
		register_setting($this->gateway_name . '_settings', $this->gateway_name, 'sanitize_text_field');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_min', 'intval');
		register_setting($this->gateway_name . '_settings', $this->gateway_name . '_show', 'sanitize_text_field');
		
		add_settings_section(
			$this->gateway_name . '_control_section', 
			esc_html(__( 'General Settings', 'dynamicpackages' )), 
			'', 
			$this->gateway_name . '_settings'
		);		
		
		add_settings_section(
			$this->gateway_name . '_settings_section', 
			esc_html(sprintf(__( '%s Settings', 'dynamicpackages' ), $this->gateway_title)), 
			'', 
			$this->gateway_name . '_settings'
		);
				
		add_settings_field( 
			$this->gateway_name, 
			esc_html(__( 'CCLW', 'dynamicpackages' )), 
			array(&$this, 'input_text'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_settings_section', $this->gateway_name
		);
		add_settings_field( 
			$this->gateway_name . '_min', 
			esc_html(__( 'Min. Amount', 'dynamicpackages' )), 
			array(&$this, 'input_number'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_control_section', $this->gateway_name . '_min'
		);
		add_settings_field( 
			$this->gateway_name . '_show', 
			esc_html(__( 'Show', 'dynamicpackages' )), 
			array(&$this, 'display_paguelo_facil_on_show'), 
			$this->gateway_name . '_settings', 
			$this->gateway_name . '_control_section'
		);
	}
	
	public function input_text($name){
		$option = get_option($name);
		?>
		<input type="text" name="<?php echo esc_html($name); ?>" id="<?php echo esc_html($name); ?>" value="<?php echo esc_html($option); ?>" />
		<?php
	}
	
	public function input_number($name){
		$option = get_option($name);
		?>
		<input type="number" name="<?php echo esc_html($name); ?>" id="<?php echo esc_html($name); ?>" value="<?php echo esc_html($option); ?>" /> #
		<?php
	}	
		
	public function display_paguelo_facil_on_show() { ?>
		<select name="<?php esc_html_e($this->gateway_name . '_show'); ?>">
			<option value="0" <?php selected(get_option($this->gateway_name . '_show'), 0); ?>><?php echo esc_html('Full Payments and Deposits', 'dynamicpackages'); ?></option>
			<option value="1" <?php selected(get_option($this->gateway_name . '_show'), 1); ?>><?php echo esc_html('Only Deposits', 'dynamicpackages'); ?></option>
		</select>
	<?php }	

	public function add_settings_page()
	{
		add_submenu_page( 'edit.php?post_type=packages', $this->gateway_title, $this->gateway_title, 'manage_options', $this->gateway_name, array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h1><?php esc_html_e($this->gateway_title); ?></h1>	
			<?php
			settings_fields( $this->gateway_name . '_settings' );
			do_settings_sections( $this->gateway_name . '_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	public function button($output)
	{
		if($this->show() && in_array($this->gateway_methods_c, $this->list_gateways_cb()))
		{
			$output .= ' <button class="pure-button bottom-20 pure-button-cc  withcc rounded" type="button"><i class="fas fa-credit-card"></i> '.esc_html($this->gateway_methods_o).'</button>';			
		}
		return $output;
	}
	public function list_gateways_cb()
	{
		return apply_filters('list_gateways', array());
	}
	public function add_gateway($array)
	{
		global $dy_valid_recaptcha;
		$add = false;
		
		if($this->show() && is_singular('packages') && package_field('package_auto_booking') > 0)
		{
			$add = true;
		}
		
		if(isset($dy_valid_recaptcha) && isset($_POST['dy_request']) && dy_Validators::is_request_valid())
		{
			if($_POST['dy_request'] == 'request')
			{
				$add = true;
			}	
		}		
		
		if($add)
		{
			$array[] = $this->gateway_methods_c;
		}
		
		return $array;	
	}
	public function scripts()
	{
		if($this->show())
		{
			wp_add_inline_style('minimalLayout', $this->css());
			wp_add_inline_script('dynamicpackages', $this->js(), 'before');	
		}
	}
	public function css()
	{
		ob_start();
		?>
			.pure-button.pure-button-cc, .pure-button-cc
			{
				background-color: #262626;
				color: #fff;
			}
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}
	public function js()
	{
		ob_start();
		?>
		$(function(){
			$('.withcc').click(function()
			{
				var paguelo_facil_on_logo = $('<p class="large"><?php echo esc_html(sprintf(__('Pay with %s thanks to', 'dynamicpackages'), $this->gateway_methods_o)); ?> <strong><?php echo esc_html($this->gateway_short_title); ?></strong></p>').addClass('text-muted');
				$('#dy_checkout_form').addClass('hidden');
				$('#dynamic_form').removeClass('hidden');
				$('#dy_form_icon').html(paguelo_facil_on_logo);
				$('#dynamic_form').find('input[name="name"]').focus();
				$('#dynamic_form').find('input[name="dy_request"]').val('<?php echo esc_html($this->gateway_name); ?>');
				
				//facebook pixel
				if(typeof fbq !== typeof undefined)
				{
					console.log('InitiateCheckout');
					fbq('track', 'InitiateCheckout');
				}

				//google analytics
				if(typeof ga !== typeof undefined)
				{
					var eventArgs = {};
					eventArgs.eventAction = 'Click';
					eventArgs.eventLabel = '<?php echo esc_html($this->gateway_name); ?>';
					eventArgs.eventCategory = 'Gateway';
					ga('send', 'event', eventArgs);	
				}				
				
			});
		});
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}	
}